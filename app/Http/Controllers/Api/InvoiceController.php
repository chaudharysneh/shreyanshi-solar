<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\BomProduct;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends ApiBaseController
{
    public function priceSuggestions(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin()
            || $user->hasMatrixPermission('create_invoices')
            || $user->hasMatrixPermission('edit_invoices')), 403);

        $data = $request->validate(['quantity' => 'required|numeric|gt:0']);
        $quantity = (float) $data['quantity'];

        return response()->json([
            'success' => true,
            'quantity' => $quantity,
            'suggestions' => array_map(fn ($rate) => [
                'rate_per_kw' => $rate,
                'price' => (int) round($quantity * $rate),
            ], [30000, 40000, 45000]),
        ]);
    }

    public function __construct()
    {
        $this->middleware('can:viewAny,' . Invoice::class)->only('index');
        $this->middleware('can:create,' . Invoice::class)->only('store');
        $this->middleware('can:view,invoice')->only('show');
        $this->middleware('can:update,invoice')->only(['update', 'updateStatus']);
        $this->middleware('can:delete,invoice')->only('destroy');
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();

        $query = Invoice::with(['customer', 'creator', 'items', 'estimate']);

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('invoice_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
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

        \App\Support\ListFilters::apply($query, $request, ['type', 'status'], ['invoice_date', 'due_date'], ['invoice_no']);
        $summaryRows=(clone $query)->setEagerLoads([])->withSum('payments','amount')->get(['id','status','amount']);
        $paidCents=0;$pendingCents=0;
        foreach($summaryRows as $row){
            if(!in_array($row->status,['paid','partial','unpaid','pending'],true))continue;
            $amount=max(0,(int)round((float)$row->amount*100));
            $received=$row->status==='paid'?$amount:min($amount,max(0,(int)round((float)$row->payments_sum_amount*100)));
            $paidCents+=$received;$pendingCents+=$amount-$received;
        }
        $summary=(object)['paid_amount'=>$paidCents/100,'pending_amount'=>$pendingCents/100];
        $invoices = $query
            ->orderByRaw("
                CASE
                    WHEN invoice_no REGEXP '^INV-[0-9]+$'
                    THEN CAST(SUBSTRING_INDEX(invoice_no, '-', -1) AS UNSIGNED)
                    ELSE 0
                END DESC
            ")
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully',
            'summary' => $summary,
            'data' => $invoices,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $priceMode = $request->input('price_mode');
        if (!in_array($priceMode, ['base', 'bom'], true)) {
            $priceMode = isset($invoice) ? $this->resolveInvoicePriceMode($invoice)
                : (\App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom');
        }
        $useBomPrice = $priceMode === 'bom';

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'invoice_name' => 'required|string|min:1',
            'type' => 'required|in:residential,commercial,industrial,common meter,ground mounted,ux template',
            'quantity' => 'required|numeric|gt:0',
            'price' => $useBomPrice ? 'required|numeric|min:0' : 'required|numeric|gt:0',
            'price_mode' => 'nullable|in:base,bom',
            'reference_no' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:10000',
            'bom_specifications' => 'nullable|array',
            'bom_specifications.*' => 'array:make,technical',
            'bom_specifications.*.make' => 'nullable|string|max:2000',
            'bom_specifications.*.technical' => 'nullable|string|max:10000',
            'global_tax_rate' => 'nullable|numeric|min:0',
            'solar_structure_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'subsidy_amount' => 'nullable|numeric|min:0',
            'template_id' => 'required|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope,included',
            'invoice_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'invoice_name.required' => 'Please enter invoice name',
            'type.required' => 'Please select invoice type',
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
            DB::beginTransaction();
            $userId = auth()->id();
            $customerId = $request->input('customer_id');
            $invoiceName = $request->input('invoice_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = $useBomPrice ? 0 : (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $invoiceDate = $request->input('invoice_date', now()->format('Y-m-d'));
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
            $currencyId = $this->resolveInvoiceCurrencyId($request->input('currency_id'));

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('invoices', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON (from BOM), with fallback to raw form arrays.
            $products = $this->normalizeInvoiceProducts($request);
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
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one BOM',
                    'errors' => [
                        'products' => ['Please select at least one BOM'],
                    ],
                ], 422);
            }

            // Calculate subtotal and GST from BOM-selected taxes.
            $basePrice = $price + $productsTotal;
            $subtotal = $basePrice + $solarStructureCharges;
            $gstBreakdown = $useBomPrice
                ? $this->buildProductGstBreakdown($products, (bool) $applyCharges)
                : $this->buildGlobalTaxBreakdown($subtotal, (float) $request->input('global_tax_rate', 0));
            $gstPercent = $applyCharges ? $gstBreakdown['tax_rate'] : 0;
            $gstAmount = $applyCharges ? $gstBreakdown['gst_amount'] : 0;

            $finalAmount = $subtotal + $gstAmount - $discount - $subsidyAmount;

            // Generate invoice number
            $invoiceNo = $this->generateNextInvoiceNumber();

            // Generation data (ROI info from reference code)
            $monthlyBill = $request->input('monthly_electricity_bill', 3000);
            $unitRate = $request->input('unit_rate', 8);
            $generationData = [
                'monthly_electricity_bill' => (float) $monthlyBill,
                'unit_rate' => (float) $unitRate,
            ];

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $customerId,
                'user_id' => $userId,
                'product_id' => $request->input('product_id'),
                'invoice_no' => $invoiceNo,
                'reference_no' => $request->input('reference_no'),
                'terms_conditions' => $request->input('terms_conditions'),
                'bom_specifications' => $request->input('bom_specifications'),
                'invoice_date' => $invoiceDate,
                'invoice_name' => $invoiceName,
                'type' => $type,
                'currency_id' => $currencyId,
                'attach_file' => $attachFile,
                'quantity' => $quantity,
                'price' => $price,
                'price_mode' => $priceMode,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId ?: null,
                'product_name' => $products,
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
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($request->boolean('update_template_comment')) {
                $this->updateTemplateEstimateComment($templateId, $comment);
            }

            app(\App\Services\UserLogService::class)->created($invoice, 'Created an Invoice ' . $invoice->invoice_no);

            DB::commit();

            send_admin_notification('Invoice', 'Created', $invoice->invoice_no, []);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'redirect' => route('invoices.index')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $priceMode = $request->input('price_mode');
        if (!in_array($priceMode, ['base', 'bom'], true)) {
            $priceMode = isset($invoice) ? $this->resolveInvoicePriceMode($invoice)
                : (\App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom');
        }
        $useBomPrice = $priceMode === 'bom';

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'invoice_name' => 'required|string|min:1',
            'type' => 'required|in:residential,commercial,industrial,common meter,ground mounted,ux template',
            'quantity' => 'required|numeric|gt:0',
            'price' => $useBomPrice ? 'required|numeric|min:0' : 'required|numeric|gt:0',
            'price_mode' => 'nullable|in:base,bom',
            'reference_no' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string|max:10000',
            'bom_specifications' => 'nullable|array',
            'bom_specifications.*' => 'array:make,technical',
            'bom_specifications.*.make' => 'nullable|string|max:2000',
            'bom_specifications.*.technical' => 'nullable|string|max:10000',
            'global_tax_rate' => 'nullable|numeric|min:0',
            'solar_structure_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'subsidy_amount' => 'nullable|numeric|min:0',
            'template_id' => 'required|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope,included',
            'invoice_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'invoice_name.required' => 'Please enter invoice name',
            'type.required' => 'Please select invoice type',
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
            DB::beginTransaction();
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $customerId = $request->input('customer_id');
            $invoiceName = $request->input('invoice_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = $useBomPrice ? 0 : (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $invoiceDate = $request->input('invoice_date', now()->format('Y-m-d'));
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
            $currencyId = $this->resolveInvoiceCurrencyId($request->input('currency_id'));

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('invoices', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON, with fallback to raw form arrays.
            $products = $this->normalizeInvoiceProducts($request);
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
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one BOM',
                    'errors' => [
                        'products' => ['Please select at least one BOM'],
                    ],
                ], 422);
            }

            // Calculate subtotal and GST from BOM-selected taxes.
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

            // Update invoice
            $updateData = [
                'customer_id' => $customerId,
                'product_id' => $request->input('product_id'),
                'invoice_date' => $invoiceDate,
                'invoice_name' => $invoiceName,
                'type' => $type,
                'quantity' => $quantity,
                'price' => $price,
                'price_mode' => $priceMode,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId ?: null,
                'currency_id' => $currencyId,
                'product_name' => $products,
                'comment' => $comment,
                'total' => $subtotal,
                'gst' => $gstPercent,
                'gst_amount' => $gstAmount,
                'gst_breakdown' => $applyCharges ? $gstBreakdown : null,
                'discount' => $discount,
                'subsidy_amount' => $subsidyAmount,
                'amount' => $finalAmount,
                'generation_data' => $generationData,
                'updated_by' => auth()->id(),
            ];

            foreach (['reference_no', 'terms_conditions', 'bom_specifications'] as $field) {
                if ($request->exists($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            if (!empty($attachFile)) {
                $updateData['attach_file'] = $attachFile;
            }

            if (($invoice->financePlan()->exists() || $invoice->payments()->exists()) && (round((float)$invoice->amount,2)!==round((float)$finalAmount,2) || (int)$invoice->customer_id!==(int)$customerId || (int)$invoice->currency_id!==(int)$currencyId)) {
                DB::rollBack();
                return response()->json(['success'=>false,'errors'=>['amount'=>['This invoice has finance records. Its total, customer and currency cannot be changed while those records exist.']]],422);
            }
            $invoice->update($updateData);

            if ($request->boolean('update_template_comment')) {
                $this->updateTemplateEstimateComment($templateId, $comment);
            }

            app(\App\Services\UserLogService::class)->updated($invoice, 'Updated an Invoice ' . ($invoice->invoice_no ?: ('ID ' . $invoice->id)));

            DB::commit();

            send_admin_notification('Invoice', 'Updated', $invoice->invoice_no, []);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'redirect' => route('invoices.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        if ($invoice->financePlan()->exists() || $invoice->financeEvents()->exists() || \App\Models\Payment::where('invoice_id', $invoice->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This invoice has finance records and cannot be deleted. Use payment corrections to retain its audit history.'], 422);
        }
        $invoice->update(['deleted_by' => auth()->id()]);
        $invoiceName = $invoice->invoice_no;
        app(\App\Services\UserLogService::class)->deleted($invoice, 'Deleted an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));
        $invoice->delete();

        send_admin_notification('Invoice', 'Deleted', $invoiceName ?? 'N/A', []);

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update',$invoice);
        return response()->json(['success'=>false,'message'=>'Record or correct payments using the invoice finance modal. Payment status is calculated from actual receipts.'],422);
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
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

    private function normalizeInvoiceProducts(Request $request): array
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

    private function resolveInvoiceCurrencyId($currencyId): ?int
    {
        if ($currencyId) {
            return (int) $currencyId;
        }

        $resolvedId = Currency::query()
            ->where('is_active', true)
            ->where('code', 'INR')
            ->value('id');

        if ($resolvedId) {
            return (int) $resolvedId;
        }

        $resolvedId = Currency::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('id');

        if ($resolvedId) {
            return (int) $resolvedId;
        }

        $resolvedId = Currency::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $resolvedId ? (int) $resolvedId : null;
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

    private function resolveInvoicePriceMode(Invoice $estimate): string
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

    private function updateTemplateEstimateComment(int $templateId, string $comment): void
    {
        if ($templateId <= 0) {
            return;
        }

        $template = \App\Models\PdfBuilderForm::find($templateId);
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

}
