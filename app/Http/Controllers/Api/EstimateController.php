<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BomProduct;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\PdfBuilderForm;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\Technology;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EstimateController extends Controller
{
    /**
     * Get paginated estimates list
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
            $query = Estimate::with(['customer', 'creator']);

            // Filter by search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('estimate_no', 'like', "%{$search}%")
                        ->orWhere('estimate_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by user visibility
            if (!$user->isAdmin()) {
                if ($request->filled('customer_id')) {
                    $userId = (int) $user->id;
                    $query->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                            ->orWhere('created_by', $userId);
                    });
                } else {
                    $query->where('user_id', $user->id);
                }
            }

            // Filter by customer_id
            if ($request->has('customer_id') && !empty($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }

            // Apply filter for staff users only
            if (!$user->isAdmin() && $filter === 'created_by_me') {
                // All records I created (regardless of assignment)
                $query->where('created_by', $user->id);
            } elseif (!$user->isAdmin() && $filter === 'assigned_to_me') {
                // Records assigned to me but NOT created by me
                $query->where('assigned_user_id', $user->id)
                      ->where('created_by', '!=', $user->id);
            }

        \App\Support\ListFilters::apply($query, $request, ['type', 'status'], ['estimate_date'], ['estimate_no']);
            $perPage = $request->input('per_page', 10);
            $estimates = $query->orderBy('estimate_id', 'desc')->paginate($perPage)->withQueryString();

            // Format the response to match expected structure
            $data = $estimates->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $data['data'],
                    'current_page' => $data['current_page'],
                    'from' => $data['from'],
                    'to' => $data['to'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'prev_page_url' => $data['prev_page_url'],
                    'next_page_url' => $data['next_page_url'],
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Estimate index error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error loading estimates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new estimate (from reference code logic)
     */
    public function store(Request $request)
    {
        $this->authorize('create', Estimate::class);
        $requestedPriceMode = $request->input('price_mode');
        $priceMode = in_array($requestedPriceMode, ['base', 'bom'], true)
            ? $requestedPriceMode
            : (Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom');
        $useBomPrice = $priceMode === 'bom';

        // Strict validation matching reference code
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'estimate_name' => 'required|string|min:1',
            'reference_no' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:10000',
            'bom_specifications' => 'nullable|array',
            'bom_specifications.*' => 'array:make,technical',
            'bom_specifications.*.make' => 'nullable|string|max:2000',
            'bom_specifications.*.technical' => 'nullable|string|max:10000',
            'type' => 'required|in:residential,commercial,industrial,common meter,ground mounted,ux template',
            'quantity' => 'required|numeric|gt:0',
            'price' => $useBomPrice ? 'required|numeric|min:0' : 'required|numeric|gt:0',
            'template_id' => 'required|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope,included',
            'estimate_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'estimate_name.required' => 'Please enter estimate name',
            'type.required' => 'Please select estimate type',
            'quantity.required' => 'Please enter valid quantity (kW)',
            'quantity.gt' => 'Please enter valid quantity (kW)',
            'price.required' => 'Please enter valid price',
            'price.gt' => 'Please enter valid price',
            'solar_meter_charges.required' => 'Please select solar meter charges',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();
            $customerId = $request->input('customer_id');
            $estimateName = $request->input('estimate_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = $useBomPrice ? 0 : (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $estimateDate = $request->input('estimate_date', now()->format('Y-m-d'));
            $comment = $request->input('comment', '');
            $discount = (float) ($request->input('discount') ?? 0);
            $subsidyAmount = (float) ($request->input('subsidy_amount') ?? 0);
            $applyCharges = (int) ($request->input('apply_gst') ?? 0);
            $gstPercent = (float) ($request->input('gst') ?? 0);
            if ($gstPercent > 0 && !$request->has('apply_gst')) {
                $applyCharges = 1;
            }
            if (!$applyCharges) {
                $gstPercent = 0;
            }

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('estimates', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON (from BOM), with fallback to raw form arrays.
            $products = $this->normalizeEstimateProducts($request);
            if (!$useBomPrice) {
                $products = array_map(function (array $product) {
                    $product['price'] = 0;
                    $product['tax_rate'] = 0;
                    return $product;
                }, $products);
            }
            $productsTotal = $this->calculateProductsTotal($products);

            // Validate at least one product selected
            $hasProduct = false;
            foreach ($products as $p) {
                if (!empty($p['product_id'])) {
                    $hasProduct = true;
                    break;
                }
            }

            if (!$hasProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one BOM',
                    'errors' => [
                        'products' => ['Please select at least one BOM'],
                    ],
                ], 422);
            }

            // Calculate subtotal and GST from the active tax settings.
            $basePrice = $price + $productsTotal;
            $subtotal = $basePrice + $solarStructureCharges;
            $gstBreakdown = $useBomPrice
                ? $this->buildProductGstBreakdown($products, (bool) $applyCharges)
                : $this->buildGlobalTaxBreakdown($subtotal, (float) $request->input('global_tax_rate', 0));
            $gstPercent = $applyCharges ? $gstBreakdown['tax_rate'] : 0;
            $gstAmount = $applyCharges ? $gstBreakdown['gst_amount'] : 0;

            $finalAmount = $subtotal + $gstAmount - $discount - $subsidyAmount;

            // Generate estimate number (6-digit padded)
            $lastEstimate = Estimate::orderBy('estimate_id', 'desc')->first();
            $nextNo = $lastEstimate ? ((int) $lastEstimate->estimate_no + 1) : 1;
            $estimateNo = str_pad($nextNo, 6, '0', STR_PAD_LEFT);

            // Generation data (ROI info from reference code)
            $monthlyBill = $request->input('monthly_electricity_bill', 3000);
            $unitRate = $request->input('unit_rate', 8);
            $generationData = [
                'monthly_electricity_bill' => (float) $monthlyBill,
                'unit_rate' => (float) $unitRate,
            ];

            // Create estimate
            $estimate = Estimate::create([
                'currency' => \App\Support\DocumentCurrency::defaultCode(),
                'customer_id' => $customerId,
                'user_id' => $userId,
                'product_id' => $request->input('product_id'),
                'estimate_no' => $estimateNo,
                'reference_no' => $request->input('reference_no'),
                'terms_conditions' => $request->input('terms_conditions'),
                'bom_specifications' => $request->input('bom_specifications'),
                'estimate_date' => $estimateDate,
                'estimate_name' => $estimateName,
                'type' => $type,
                'attach_file' => $attachFile,
                'quantity' => $quantity,
                'price' => $price,
                'price_mode' => $priceMode,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId,
                'product_name' => json_encode($products),
                'status' => 'pending',
                'comment' => $comment,
                'total' => $subtotal,
                'gst' => $gstPercent,
                'gst_amount' => $gstAmount,
                'gst_breakdown' => $applyCharges ? $gstBreakdown : null,
                'discount' => $discount,
                'subsidy_amount' => $subsidyAmount,
                'amount' => $finalAmount,
                'generation_data' => $generationData,
                'is_quotation' => 1,
            ]);

            // ── Email: Estimate Customer & Admin Notifications ────────────────
            if ($request->boolean('update_template_comment')) {
                $this->updateTemplateEstimateComment($templateId, $comment);
            }

            $estimate->loadMissing(['customer', 'creator']);
            send_estimate_view_notification($estimate);
            send_admin_notification('Estimate', 'Created', $estimate->estimate_no, []);

            return response()->json([
                'success' => true,
                'message' => 'Estimate created successfully',
                'estimate_id' => $estimate->estimate_id,
                'data' => [
                    'estimate_id' => $estimate->estimate_id,
                    'estimate_name' => $estimate->estimate_name,
                    'amount' => $estimate->amount,
                    'customer_id' => $estimate->customer_id,
                ],
                'redirect' => route('estimates.index')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Estimate creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating estimate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a single estimate
     */
    public function show(string $id)
    {
        $estimate = Estimate::with(['customer', 'creator'])->findOrFail($id);
        $this->authorize('view', $estimate);

        return response()->json([
            'success' => true,
            'data' => $estimate
        ]);
    }

    /**
     * Update an estimate (from reference code logic)
     */
    public function update(Request $request, string $id)
    {
        $estimate = Estimate::findOrFail($id);
        $this->authorize('update', $estimate);
        $requestedPriceMode = $request->input('price_mode');
        $priceMode = in_array($requestedPriceMode, ['base', 'bom'], true)
            ? $requestedPriceMode
            : $this->resolveEstimatePriceMode($estimate);
        $useBomPrice = $priceMode === 'bom';

        if (($estimate->status ?? '') === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Approved estimates cannot be edited.',
            ], 422);
        }

        // Strict validation matching reference code
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'estimate_name' => 'required|string|min:1',
            'reference_no' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:10000',
            'bom_specifications' => 'nullable|array',
            'bom_specifications.*' => 'array:make,technical',
            'bom_specifications.*.make' => 'nullable|string|max:2000',
            'bom_specifications.*.technical' => 'nullable|string|max:10000',
            'type' => 'required|in:residential,commercial,industrial,common meter,ground mounted,ux template',
            'quantity' => 'required|numeric|gt:0',
            'price' => $useBomPrice ? 'required|numeric|min:0' : 'required|numeric|gt:0',
            'template_id' => 'required|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope,included',
            'estimate_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'estimate_name.required' => 'Please enter estimate name',
            'type.required' => 'Please select estimate type',
            'quantity.required' => 'Please enter valid quantity (kW)',
            'quantity.gt' => 'Please enter valid quantity (kW)',
            'price.required' => 'Please enter valid price',
            'price.gt' => 'Please enter valid price',
            'solar_meter_charges.required' => 'Please select solar meter charges',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customerId = $request->input('customer_id');
            $estimateName = $request->input('estimate_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = $useBomPrice ? 0 : (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $estimateDate = $request->input('estimate_date', now()->format('Y-m-d'));
            $comment = $request->input('comment', '');
            $discount = (float) ($request->input('discount') ?? 0);
            $subsidyAmount = (float) ($request->input('subsidy_amount') ?? 0);
            $applyCharges = (int) ($request->input('apply_gst') ?? 0);
            $gstPercent = (float) ($request->input('gst') ?? 0);
            if ($gstPercent > 0 && !$request->has('apply_gst')) {
                $applyCharges = 1;
            }
            if (!$applyCharges) {
                $gstPercent = 0;
            }

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('estimates', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON, with fallback to raw form arrays.
            $products = $this->normalizeEstimateProducts($request);
            if (!$useBomPrice) {
                $products = array_map(function (array $product) {
                    $product['price'] = 0;
                    $product['tax_rate'] = 0;
                    return $product;
                }, $products);
            }
            $productsTotal = $this->calculateProductsTotal($products);

            // Validate at least one product selected
            $hasProduct = false;
            foreach ($products as $p) {
                if (!empty($p['product_id'])) {
                    $hasProduct = true;
                    break;
                }
            }

            if (!$hasProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one BOM',
                    'errors' => [
                        'products' => ['Please select at least one BOM'],
                    ],
                ], 422);
            }

            // Calculate subtotal and GST
            $basePrice = $price + $productsTotal;
            $subtotal = $basePrice + $solarStructureCharges;
            $gstBreakdown = $useBomPrice
                ? $this->buildProductGstBreakdown($products, (bool) $applyCharges)
                : $this->buildGlobalTaxBreakdown($subtotal, (float) $request->input('global_tax_rate', 0));
            $gstPercent = $applyCharges ? $gstBreakdown['tax_rate'] : 0;
            $gstAmount = $applyCharges ? $gstBreakdown['gst_amount'] : 0;

            $finalAmount = $subtotal + $gstAmount - $discount - $subsidyAmount;

            // Generation data
            $monthlyBill = $request->input('monthly_electricity_bill', 3000);
            $unitRate = $request->input('unit_rate', 8);
            $generationData = [
                'monthly_electricity_bill' => (float) $monthlyBill,
                'unit_rate' => (float) $unitRate,
            ];

            // Update estimate
            $updateData = [
                'customer_id' => $customerId,
                'product_id' => $request->input('product_id'),
                'estimate_date' => $estimateDate,
                'estimate_name' => $estimateName,
                'type' => $type,
                'quantity' => $quantity,
                'price' => $price,
                'price_mode' => $priceMode,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId,
                'product_name' => json_encode($products),
                'comment' => $comment,
                'total' => $subtotal,
                'gst' => $gstPercent,
                'gst_amount' => $gstAmount,
                'gst_breakdown' => $applyCharges ? $gstBreakdown : null,
                'discount' => $discount,
                'subsidy_amount' => $subsidyAmount,
                'amount' => $finalAmount,
                'generation_data' => $generationData,
            ];

            if (!empty($attachFile)) {
                $updateData['attach_file'] = $attachFile;
            }

            if ($request->exists('reference_no')) {
                $updateData['reference_no'] = $request->input('reference_no');
            }
            if ($request->exists('terms_conditions')) {
                $updateData['terms_conditions'] = $request->input('terms_conditions');
            }
            if ($request->exists('bom_specifications')) {
                $updateData['bom_specifications'] = $request->input('bom_specifications');
            }

            $updateData['currency'] = $estimate->currency ?: \App\Support\DocumentCurrency::defaultCode();
            $estimate->update($updateData);

            if ($request->boolean('update_template_comment')) {
                $this->updateTemplateEstimateComment($templateId, $comment);
            }

            send_admin_notification('Estimate', 'Updated', $estimate->estimate_no, []);

             return response()->json([
                'success' => true,
                'message' => 'Estimate updated successfully',
                'estimate_id' => $estimate->estimate_id,
                'redirect' => route('estimates.index')
            ]);

        } catch (\Exception $e) {
            \Log::error('Estimate update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating estimate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an estimate
     */
    public function destroy(string $id)
    {
        $estimate = Estimate::findOrFail($id);
        $this->authorize('delete', $estimate);

        try {
            $estimateName = $estimate->estimate_no;
            $estimate->delete();

            send_admin_notification('Estimate', 'Deleted', $estimateName ?? 'N/A', []);

            return response()->json([
                'success' => true,
                'message' => 'Estimate deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting estimate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update estimate status
     */
    public function equipment(string $id)
    {
        $estimate = Estimate::findOrFail($id);
        $this->authorize('update',$estimate);
        $ratio=(int) ($estimate->equipment_serials['panels_per_kw'] ?? 2);
        return response()->json(['quantity'=>(float) $estimate->quantity,'panels_per_kw'=>$ratio,'count'=>(int) ceil((float) $estimate->quantity*$ratio),'equipment'=>$estimate->equipment_serials]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $estimate = Estimate::findOrFail($id);
        $this->authorize('update', $estimate);

        $ratio=$request->input('equipment_serials.panels_per_kw',2);
        $count = max(0,(int) ceil((float) $estimate->quantity*(in_array($ratio,[1,'1'],true) ? 1 : 2)));
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected,completed',
            'equipment_serials'=>['sometimes','array:panels,inverters,notes,panels_per_kw'],
            'equipment_serials.panels_per_kw'=>['sometimes','required','integer','in:1,2'],
            'equipment_serials.panels'=>['nullable','array','max:'.$count],
            'equipment_serials.inverters'=>['nullable','array'],
            'equipment_serials.panels.*'=>['nullable','string','max:100'],
            'equipment_serials.inverters.*'=>['nullable','string','max:100'],
            'equipment_serials.notes'=>['nullable','string','max:4000'],
        ]);
        $validator->after(function ($validator) use ($request) {
            foreach (['panels','inverters'] as $type) {
                $values=$request->input('equipment_serials.'.$type,[]);
                if (!is_array($values)) continue;
                $serials=array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '',$values));
                if (count($serials) !== count(array_unique($serials))) $validator->errors()->add('equipment_serials.'.$type,'Each '.$type.' serial number must be unique.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $estimate = Estimate::whereKey($estimate->getKey())->lockForUpdate()->firstOrFail();
            $newStatus = $request->status;
            $isApproval = $newStatus === 'approved' && $estimate->status !== 'approved';
            
            if ($isApproval && !Invoice::where('estimate_id', $estimate->estimate_id)->exists()) {
                // Create an invoice from this estimate
                $invoiceDate = now()->format('Y-m-d');
                $invoice = Invoice::create([
                    'currency_id' => \App\Support\DocumentCurrency::idForCode($estimate->currency ?: \App\Support\DocumentCurrency::defaultCode()),
                    'customer_id' => $estimate->customer_id,
                    'user_id' => $estimate->user_id,
                    'estimate_id' => $estimate->estimate_id,
                    'product_id' => $estimate->product_id,
                    'invoice_no' => $this->generateNextInvoiceNumber(),
                    'invoice_date' => $invoiceDate,
                    'due_date' => $invoiceDate,
                    'invoice_name' => 'INV-'.$estimate->customer->name,
                    'type' => $estimate->type,
                    'quantity' => $estimate->quantity,
                    'price' => $estimate->price,
                    'solar_structure_charges' => $estimate->solar_structure_charges,
                    'solar_meter_charges' => $estimate->solar_meter_charges,
                    'product_name' => $estimate->product_name,
                    'status' => 'unpaid',
                    'comment' => $estimate->comment,
                    'total' => $estimate->total,
                    'gst' => $estimate->gst,
                    'gst_amount' => $estimate->gst_amount,
                    'gst_breakdown' => $estimate->gst_breakdown,
                    'discount' => $estimate->discount,
                    'subsidy_amount' => $estimate->subsidy_amount,
                    'amount' => $estimate->amount,
                    'generation_data' => $estimate->generation_data,
                    'customer_docs' => $estimate->customer_docs,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                app(\App\Services\UserLogService::class)->created($invoice, 'Created Invoice from Estimate ' . $estimate->estimate_no);
            } elseif ($newStatus === 'pending') {
                $hasFinance = Invoice::where('estimate_id', $estimate->estimate_id)
                    ->where(function ($query) {
                        $query->whereHas('financePlan')->orWhereHas('financeEvents')
                            ->orWhereIn('id', \App\Models\Payment::select('invoice_id'));
                    })->exists();
                if ($hasFinance) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'This estimate has an invoice with finance records and cannot be returned to pending.'], 422);
                }
                // Delete the invoice if it exists
                Invoice::where('estimate_id', $estimate->estimate_id)->delete();
            }
            
            $changes=['status'=>$newStatus];
            if ($newStatus === 'approved' && $request->has('equipment_serials')) $changes['equipment_serials']=$validator->validated()['equipment_serials'];
            $estimate->update($changes);
            if ($isApproval) app(\App\Services\EstimateFulfilmentService::class)->ensure($estimate);
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'estimate_id' => $estimate->estimate_id,
                    'status' => $newStatus,
                    'is_editable' => $newStatus !== 'approved',
                    'invoice_id' => isset($invoice) ? $invoice->id : null,
                ],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function customerDocuments(string $id)
    {
        $estimate = Estimate::with('customer')->findOrFail($id);
        abort_unless($this->canAccessEstimateDocuments($estimate), 403);

        return response()->json([
            'success' => true,
            'data' => [
                'estimate_id' => $estimate->estimate_id,
                'estimate_no' => $estimate->estimate_no,
                'customer_id' => $estimate->customer_id,
                'customer_name' => $estimate->customer?->name,
                'docs' => $this->normalizeCustomerDocs($estimate->customer_docs),
            ],
        ]);
    }

    public function uploadCustomerDocuments(Request $request, string $id)
    {
        $estimate = Estimate::findOrFail($id);
        abort_unless($this->canManageEstimateDocuments($estimate), 403);

        $existingDocs = $this->normalizeCustomerDocs($estimate->customer_docs);
        $validator = Validator::make($request->all(), \App\Support\CustomerDocumentFields::rules(), [
            'documents.required' => 'Please select documents to upload.',
            'documents.*.label.required' => 'Please enter a label for each extra document.',
            'documents.*.files.required' => 'Please select at least one file for each document.',
            'documents.*.files.*.mimes' => 'Only documents and image files are allowed.',
            'documents.*.files.*.max' => 'Each file must be 10MB or smaller.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $newDocs = [];

        foreach ($validator->validated()['documents'] as $document) {
            foreach ($document['files'] as $file) {
                $originalName = $file->getClientOriginalName();
                $storedName = uniqid('estimate_doc_', true) . '.' . $file->getClientOriginalExtension();
                $storedPath = $file->storeAs('estimates/customer-docs/' . $estimate->estimate_id, $storedName, 'public');

                $newDocs[] = [
                    'original_name' => $originalName,
                    'path' => $storedPath,
                    'uploaded_at' => now()->toDateTimeString(),
                    'category' => $document['category'],
                    'label' => \App\Support\CustomerDocumentFields::REQUIRED[$document['category']] ?? trim($document['label']),
                ];
            }
        }

        $updatedDocs = array_values(array_merge($existingDocs, $newDocs));
        $estimate->update(['customer_docs' => $updatedDocs]);

        return response()->json([
            'success' => true,
            'message' => 'Customer documents uploaded successfully.',
            'data' => [
                'docs' => $this->normalizeCustomerDocs($estimate->fresh()->customer_docs),
            ],
        ]);
    }

    public function deleteCustomerDocument(string $id, int $docIndex)
    {
        $estimate = Estimate::findOrFail($id);
        abort_unless($this->canManageEstimateDocuments($estimate), 403);

        $docs = $this->normalizeCustomerDocs($estimate->customer_docs);
        $doc = $docs[$docIndex] ?? null;

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        if (!empty($doc['path']) && Storage::disk('public')->exists($doc['path'])) {
            Storage::disk('public')->delete($doc['path']);
        }

        unset($docs[$docIndex]);
        $estimate->update(['customer_docs' => array_values($docs)]);

        return response()->json([
            'success' => true,
            'message' => 'Customer document deleted successfully.',
            'data' => [
                'docs' => $this->normalizeCustomerDocs($estimate->fresh()->customer_docs),
            ],
        ]);
    }

    private function normalizeCustomerDocs($docs): array
    {
        if (!is_array($docs)) {
            return [];
        }

        return array_values(array_map(function ($doc) {
            if (is_string($doc)) {
                return [
                    'original_name' => basename($doc),
                    'path' => $doc,
                    'uploaded_at' => null,
                ];
            }

            return [
                'original_name' => $doc['original_name'] ?? basename((string) ($doc['path'] ?? 'document')),
                'path' => $doc['path'] ?? null,
                'uploaded_at' => $doc['uploaded_at'] ?? null,
                'category' => $doc['category'] ?? null,
                'label' => $doc['label'] ?? 'Other document',
            ];
        }, array_filter($docs)));
    }

    private function updateTemplateEstimateComment(int $templateId, string $comment): void
    {
        if ($templateId <= 0) {
            return;
        }

        $template = PdfBuilderForm::find($templateId);
        if (!$template) {
            return;
        }

        $formData = is_array($template->form_data) ? $template->form_data : [];
        $existingComment = is_array($formData['estimate_comment'] ?? null) ? $formData['estimate_comment'] : [];

        $formData['estimate_comment'] = array_merge($existingComment, [
            'active' => 1,
            'content' => $comment,
        ]);

        $template->form_data = $formData;
        $template->save();
    }

    private function calculateProductsTotal(array $products): float
    {
        return array_reduce($products, function (float $total, array $product) {
            $quantity = (float) ($product['quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            return $total + ($quantity * $price);
        }, 0.0);
    }

    private function buildProductGstBreakdown(array $products, bool $applyTaxes): array
    {
        if (!$applyTaxes) {
            return [
                'tax_rate' => 0,
                'gst_amount' => 0,
                'groups' => [],
            ];
        }

        $lines = [];
        foreach ($products as $product) {
            $quantity = (float) ($product['quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            $rate = (float) ($product['tax_rate'] ?? 0);
            $label = trim((string) ($product['tax_label'] ?? 'GST'));
            $taxableAmount = $quantity * $price;

            if ($taxableAmount <= 0 || $rate <= 0) {
                continue;
            }

            $upperLabel = strtoupper($label);
            if (str_contains($upperLabel, 'CGST') && str_contains($upperLabel, 'SGST')) {
                $halfRate = $rate / 2;
                foreach (['CGST', 'SGST'] as $splitLabel) {
                    $key = $splitLabel . '|' . number_format($halfRate, 4, '.', '');
                    if (!isset($lines[$key])) {
                        $lines[$key] = [
                            'label' => $splitLabel,
                            'rate' => round($halfRate, 2),
                            'amount' => 0,
                        ];
                    }
                    $lines[$key]['amount'] += ($taxableAmount * $halfRate) / 100;
                }
            } else {
                $lineLabel = str_contains($upperLabel, 'IGST') ? 'IGST' : ($label !== '' ? $label : 'GST');
                $key = $lineLabel . '|' . number_format($rate, 4, '.', '');
                if (!isset($lines[$key])) {
                    $lines[$key] = [
                        'label' => $lineLabel,
                        'rate' => round($rate, 2),
                        'amount' => 0,
                    ];
                }
                $lines[$key]['amount'] += ($taxableAmount * $rate) / 100;
            }
        }

        $lines = array_values(array_map(function (array $line) {
            $line['amount'] = round((float) $line['amount'], 2);
            return $line;
        }, $lines));

        return [
            'tax_rate' => round(array_sum(array_column($lines, 'rate')), 2),
            'gst_amount' => round(array_sum(array_column($lines, 'amount')), 2),
            'groups' => $lines ? [[
                'tax_type' => 'bom_selected_tax',
                'lines' => $lines,
            ]] : [],
        ];
    }

    private function buildGlobalTaxBreakdown(float $taxableAmount, float $rate): array
    {
        if ($rate <= 0 || $taxableAmount <= 0) {
            return ['tax_rate' => 0, 'gst_amount' => 0, 'groups' => []];
        }

        $tax = Tax::active()->get()->first(function (Tax $tax) use ($rate) {
            return abs((float) $tax->rate - $rate) < 0.001;
        });

        if (!$tax) {
            return ['tax_rate' => 0, 'gst_amount' => 0, 'groups' => []];
        }

        $label = trim((string) $tax->name);
        $upperLabel = strtoupper($label);
        $lines = [];

        if (str_contains($upperLabel, 'CGST') && str_contains($upperLabel, 'SGST')) {
            $halfRate = $rate / 2;
            foreach (['CGST', 'SGST'] as $splitLabel) {
                $lines[] = [
                    'label' => $splitLabel,
                    'rate' => round($halfRate, 2),
                    'amount' => round(($taxableAmount * $halfRate) / 100, 2),
                ];
            }
        } else {
            $lines[] = [
                'label' => str_contains($upperLabel, 'IGST') ? 'IGST' : ($label ?: 'GST'),
                'rate' => round($rate, 2),
                'amount' => round(($taxableAmount * $rate) / 100, 2),
            ];
        }

        return [
            'tax_rate' => round($rate, 2),
            'gst_amount' => round(array_sum(array_column($lines, 'amount')), 2),
            'groups' => [[
                'tax_type' => 'global_tax',
                'taxable_amount' => round($taxableAmount, 2),
                'lines' => $lines,
            ]],
        ];
    }

    private function resolveEstimatePriceMode(Estimate $estimate): string
    {
        if (in_array($estimate->price_mode, ['base', 'bom'], true)) {
            return $estimate->price_mode;
        }

        $breakdown = is_array($estimate->gst_breakdown)
            ? $estimate->gst_breakdown
            : (json_decode((string) $estimate->gst_breakdown, true) ?: []);

        foreach (($breakdown['groups'] ?? []) as $group) {
            $taxType = (string) ($group['tax_type'] ?? '');
            if ($taxType === 'global_tax') {
                return 'base';
            }
            if ($taxType === 'bom_selected_tax') {
                return 'bom';
            }
        }

        $products = is_array($estimate->product_name)
            ? $estimate->product_name
            : (json_decode((string) $estimate->product_name, true) ?: []);
        $bomTotal = collect($products)->sum(function ($product) {
            return (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
        });

        return (float) $estimate->price > 0 && $bomTotal <= 0 ? 'base' : 'bom';
    }

    private function buildGstBreakdown(float $taxableAmount, bool $applyTaxes): array
    {
        if (!$applyTaxes) {
            return [
                'tax_rate' => 0,
                'gst_amount' => 0,
                'groups' => [],
            ];
        }

        $lines = $this->estimateTaxLines();
        $totalRate = array_sum(array_column($lines, 'rate'));

        foreach ($lines as $index => $line) {
            $lines[$index]['amount'] = round(($taxableAmount * (float) $line['rate']) / 100, 2);
        }

        return [
            'tax_rate' => round($totalRate, 2),
            'gst_amount' => round(array_sum(array_column($lines, 'amount')), 2),
            'groups' => $lines ? [[
                'tax_type' => 'settings_tax',
                'taxable_amount' => round($taxableAmount, 2),
                'lines' => $lines,
            ]] : [],
        ];
    }

    private function estimateTaxLines(): array
    {
        return Tax::active()
            ->orderBy('name')
            ->orderBy('rate')
            ->get()
            ->flatMap(function (Tax $tax) {
                $name = (string) $tax->name;
                $upperName = strtoupper($name);
                $rate = (float) $tax->rate;

                if (str_contains($upperName, 'CGST') && str_contains($upperName, 'SGST')) {
                    return [
                        ['label' => 'CGST', 'rate' => round($rate / 2, 2)],
                        ['label' => 'SGST', 'rate' => round($rate / 2, 2)],
                    ];
                }

                if (str_contains($upperName, 'IGST')) {
                    return [['label' => 'IGST', 'rate' => round($rate, 2)]];
                }

                return [['label' => $name, 'rate' => round($rate, 2)]];
            })
            ->values()
            ->all();
    }

    private function normalizeEstimateProducts(Request $request): array
    {
        $productsJson = $request->input('products', '[]');
        $products = is_string($productsJson) ? json_decode($productsJson, true) : $productsJson;

        if (is_array($products) && !empty($products)) {
            return array_values(array_filter(array_map(function ($product) {
                $productId = (string) ($product['product_id'] ?? '');
                if ($productId === '') {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'name' => (string) ($product['name'] ?? ''),
                    'description' => (string) ($product['description'] ?? ''),
                    'hsn_sac' => (string) ($product['hsn_sac'] ?? $product['hsn'] ?? ''),
                    'category_name' => (string) ($product['category_name'] ?? ''),
                    'quantity' => (float) ($product['quantity'] ?? 0),
                    'price' => (float) ($product['price'] ?? 0),
                    'tax_rate' => (float) ($product['tax_rate'] ?? 0),
                    'tax_label' => (string) ($product['tax_label'] ?? ''),
                    ...array_map(
                        fn ($value) => is_scalar($value) ? (string) $value : null,
                        array_intersect_key($product, array_flip(['technology_id', 'warranty_id', 'height', 'fitting_material', 'fitting_type', 'thickness', 'size_of_pipe', 'capacity', 'meter', 'nos']))
                    ),
                ];
            }, $products)));
        }

        $serviceIds = (array) $request->input('service', []);
        $makes = (array) $request->input('product_make', []);
        $descriptions = (array) $request->input('product_description', []);
        $hsnSacCodes = (array) $request->input('product_hsn_sac', []);
        $quantities = (array) $request->input('product_qty', []);
        $prices = (array) $request->input('product_price', []);
        $taxRates = (array) $request->input('product_tax_rate', []);

        $productIds = array_values(array_unique(array_filter($serviceIds, function ($value) {
            return (string) $value !== '';
        })));

        $productsById = BomProduct::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $normalized = [];
        foreach ($serviceIds as $index => $serviceId) {
            if ((string) $serviceId === '') {
                continue;
            }

            $product = $productsById->get((int) $serviceId);

            $normalized[] = [
                'product_id' => (string) $serviceId,
                'name' => (string) ($product->product_name ?? ''),
                'description' => (string) ($descriptions[$index] ?? $product->description ?? ''),
                'hsn_sac' => (string) ($hsnSacCodes[$index] ?? $product->hsn_sac ?? ''),
                'category_name' => (string) ($makes[$index] ?? ''),
                'quantity' => (float) ($quantities[$index] ?? 0),
                'price' => array_key_exists($index, $prices)
                    ? (float) ($prices[$index] ?? 0)
                    : (float) ($product->price ?? 0),
                'tax_rate' => (float) ($taxRates[$index] ?? 0),
                'tax_label' => '',
            ];
        }

        return $normalized;
    }

    private function canAccessEstimateDocuments(Estimate $estimate): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return \App\Models\Customer::query()
            ->visibleToUser($user)
            ->whereKey($estimate->customer_id)
            ->exists();
    }

    private function canManageEstimateDocuments(Estimate $estimate): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return (int) $estimate->user_id === (int) $user->id
            || (int) $estimate->created_by === (int) $user->id
            || $this->canAccessEstimateDocuments($estimate);
    }

    private function generateNextInvoiceNumber(): string
    {
        $numbers = Invoice::withTrashed()
            ->lockForUpdate()
            ->whereNotNull('invoice_no')
            ->pluck('invoice_no');

        $nextSequence = $this->extractHighestInvoiceSequence($numbers) + 1;

        return 'INV-' . str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    private function extractHighestInvoiceSequence(Collection $numbers): int
    {
        return $numbers
            ->map(function ($number) {
                if (!is_string($number)) {
                    return null;
                }

                if (!preg_match('/^INV-(\d+)$/i', trim($number), $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter(static fn($value) => $value !== null)
            ->max() ?? 0;
    }
}
