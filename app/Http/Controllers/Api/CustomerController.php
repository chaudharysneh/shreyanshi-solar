<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::with(['country', 'city', 'pipelineStage', 'creator'])
            ->latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'is_active' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'pipeline_stage_id' => ['nullable', 'integer', 'exists:stages,id,is_active,1'],
        ]);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $filters['date_from']);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $filters['date_to']);
        if ($request->filled('is_active')) $query->where('is_active', $filters['is_active']);
        if ($request->filled('type')) $query->where('type', $filters['type']);
        if ($request->filled('pipeline_stage_id')) $query->where('pipeline_stage_id', $filters['pipeline_stage_id']);
        $customers = $query->latest()->paginate($filters['per_page'] ?? 10);
        $isAdmin = auth()->user()?->isAdmin() ?? false;
        $authId = auth()->id();

        $customers->getCollection()->transform(function (Customer $customer) use ($isAdmin, $authId) {
            // Check if user is the creator
            $isCreator = $customer->created_by && (int) $customer->created_by === (int) $authId;

            // ✅ CHANGED: Permission logic updated
            // 1. Admin: Full access to all customers
            // 2. Staff: Can view ALL customers AND edit/delete ANY customer

            $canUpdate = true;  // ✅ All staff can edit any customer
            $canDelete = true;  // ✅ All staff can delete any customer

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'pipeline_stage_id' => $customer->pipeline_stage_id,
                'pipeline_stage_name' => optional($customer->pipelineStage)->name,
                'created_by_name' => $customer->creator?->name,
                'created_at' => optional($customer->created_at)->toIso8601String(),
                'can_view' => true, // All users can view all customers
                'can_update' => $canUpdate,
                'can_delete' => $canDelete,
                'is_creator' => $isCreator,
                'is_admin' => $isAdmin,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Customers retrieved successfully.',
            'data' => $customers
        ]);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['country', 'city']);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);

        $lockName = 'customer_create_' . md5($request->phone ?? $request->email ?? Str::random());
        $lockAcquired = \Illuminate\Support\Facades\DB::selectOne("SELECT GET_LOCK(?, 5) AS lock_acquired", [$lockName])->lock_acquired;

        if (!$lockAcquired) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait a moment before submitting again.'
            ], 429);
        }

        try {
            // Idempotency check for double-submissions
            if ($request->filled('phone') || $request->filled('email')) {
                $query = Customer::whereNull('deleted_at');
                if ($request->filled('phone')) {
                    $query->where('phone', $request->phone);
                } else {
                    $query->where('email', $request->email);
                }
                $existing = $query->first();
                
                if ($existing && $existing->created_at && $existing->created_at->diffInSeconds(now()) < 10) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Customer created successfully.',
                        'redirect' => '/masters/customers',
                        'data' => $existing
                    ], 201);
                }
            }

            $data = $request->validate([
                ...\App\Support\CustomerSourceDetails::rules($request),
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->whereNull('deleted_at')],
                'phone' => ['required', 'string', 'min:10', 'max:10', Rule::unique('customers', 'phone')->whereNull('deleted_at')],
                'whatsapp' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
                'dob' => ['nullable', 'date'],
                'anniversary_date' => ['nullable', 'date'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:255'],
                'tax_number' => ['nullable', 'string', 'max:100'],
                'type' => ['nullable', 'string', 'max:100'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'city_id' => ['nullable', 'exists:cities,id'],
                'finance_id' => ['nullable', 'exists:finance_masters,id'],
                'installation_master_id' => ['nullable', 'exists:installation_masters,id'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('customers', 'public');
            }

            $data['is_active'] = $request->boolean('is_active', true);

            if (Schema::hasColumn('customers', 'created_by')) {
                $data['created_by'] = auth()->id();
            }

            if (Schema::hasColumn('customers', 'updated_by')) {
                $data['updated_by'] = auth()->id();
            }

            // Assign default pipeline stage if not provided
            if (!isset($data['pipeline_stage_id'])) {
                $firstStage = \App\Models\Stage::orderBy('sort_order', 'asc')->first();
                if ($firstStage) {
                    $data['pipeline_stage_id'] = $firstStage->id;
                }
            }

            $customer = Customer::create(\App\Support\CustomerSourceDetails::normalize($data));
            
            // Sync the workflow stage to ensure they appear on the correct workflow board immediately
            if ($customer->pipeline_stage_id) {
                app(\App\Services\WorkflowService::class)->syncWorkflowToPipelineStage($customer->id, $customer->pipeline_stage_id);
            }

            if ($request->has('custom_fields')) {
                $customer->saveCustomFields($request->get('custom_fields'));
            }

            // WhatsApp welcome message
            try {
                $phone = $customer->whatsapp ?: $customer->phone;
                if ($phone) {
                    app(\App\Services\WhatsAppService::class)->sendForModule(
                        'customer_welcome_message',
                        $phone,
                        [
                            $customer->name ?? 'Customer',
                            $customer->company_name ?: '--',
                            $customer->phone ?: '--',
                            $customer->address ?: '--',
                        ],
                        $customer->id
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Customer create WhatsApp block failed', [
                    'customer_id' => $customer->id ?? null,
                    'error'       => $e->getMessage(),
                ]);
            }

            // ── Email: Customer Welcome ──────────────────────────────────
            send_customer_welcome_notification($customer);

            // ── Email: Admin Notification (if created by Staff) ──────────
            send_admin_notification('Customer', 'Created', $customer->name ?? $customer->company_name ?? 'Unknown', [], url('/masters/customers/' . $customer->id . '/edit'));

            return response()->json([
                'success'  => true,
                'message'  => 'Customer created successfully.',
                'redirect' => '/masters/customers',
                'data'     => $customer
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        } finally {
            \Illuminate\Support\Facades\DB::statement("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        try {
            $data = $request->validate([
                ...\App\Support\CustomerSourceDetails::rules($request,$customer),
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)->whereNull('deleted_at')],
                'phone' => ['required', 'string', 'min:10', 'max:10', Rule::unique('customers', 'phone')->ignore($customer->id)->whereNull('deleted_at')],
                'whatsapp' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
                'dob' => ['nullable', 'date'],
                'anniversary_date' => ['nullable', 'date'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:255'],
                'tax_number' => ['nullable', 'string', 'max:100'],
                'type' => ['nullable', 'string', 'max:100'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'city_id' => ['nullable', 'exists:cities,id'],
                'finance_id' => ['nullable', 'exists:finance_masters,id'],
                'installation_master_id' => ['nullable', 'exists:installation_masters,id'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('image')) {
                if ($customer->image) {
                    Storage::disk('public')->delete($customer->image);
                }
                $data['image'] = $request->file('image')->store('customers', 'public');
            }

            if ($request->has('is_active')) {
                $data['is_active'] = $request->boolean('is_active');
            }

            if (Schema::hasColumn('customers', 'updated_by')) {
                $data['updated_by'] = auth()->id();
            }

            $customer->update(\App\Support\CustomerSourceDetails::normalize($data,$customer));
            if ($request->has('custom_fields')) {
                $customer->saveCustomFields($request->get('custom_fields'));
            }

            try {
                $phone = $customer->whatsapp ?: $customer->phone;
                if ($phone) {
                    app(\App\Services\WhatsAppService::class)->sendForModule(
                        'customer_profile_updated',
                        $phone,
                        [
                            $customer->name ?? 'Customer',
                            $customer->company_name ?: '--',
                            $customer->phone ?: '--',
                            $customer->address ?: '--',
                        ],
                        $customer->id
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Customer update WhatsApp block failed', [
                    'customer_id' => $customer->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }

            // ── Email: Admin Notification (if updated by Staff) ──────────
            send_admin_notification('Customer', 'Updated', $customer->name ?? $customer->company_name ?? 'Unknown', [], url('/masters/customers/' . $customer->id . '/edit'));

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'redirect' => '/masters/customers',
                'data' => $customer
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating customer: ' . $e->getMessage()
            ], 500);
        }
    }


    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        try {
            $customerId = $customer->id;

            if ($customer->image) {
                Storage::disk('public')->delete($customer->image);
            }

            $customer->delete();

            // Delete associated workflow data
            $workflowTables = [
                'document_verifications',
                'registrations',
                'feasibilities',
                'finances',
                'dispatches',
                'waiting_floors',
                'installations',
                'intimations',
                'net_meterings',
                'subsidies',
            ];

            foreach ($workflowTables as $table) {
                if (Schema::hasTable($table)) {
                    \Illuminate\Support\Facades\DB::table($table)
                        ->where('customer_id', $customerId)
                        ->delete();
                }
            }

            send_admin_notification('Customer', 'Deleted', $customerName ?? 'N/A', []);

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $this->authorize('create', Customer::class);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'import_file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'import_file.required' => 'Please select an import file.',
            'import_file.file' => 'Please upload a valid import file.',
            'import_file.mimes' => 'Please upload a CSV file.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read the import file.',
            ], 422);
        }

        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Import file is empty.',
            ], 422);
        }

        $normalizedHeaders = array_map(function ($header) {
            return \Illuminate\Support\Str::of((string) $header)->lower()->replace([' ', '-', '.'], '_')->toString();
        }, $headers);

        $rowNumber = 1;
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowData = $this->mapImportRow($normalizedHeaders, $row);
            $name = trim((string) ($rowData['name'] ?? ''));
            $email = trim((string) ($rowData['email'] ?? ''));
            $phone = trim((string) ($rowData['phone'] ?? ''));

            if ($name === '' || ($email === '' && $phone === '')) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Name and either Email or Phone are required.";
                continue;
            }

            // Basic email validation if provided
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Invalid email format.";
                continue;
            }

            // Find existing customer by email or phone
            $customer = null;
            if ($email !== '') {
                $customer = Customer::where('email', $email)->first();
            }
            if (!$customer && $phone !== '') {
                $customer = Customer::where('phone', $phone)->first();
            }

            $payload = [
                'name' => $name,
                'email' => $email ?: ($customer ? $customer->email : null),
                'phone' => $phone ?: ($customer ? $customer->phone : null),
                'whatsapp' => trim((string) ($rowData['whatsapp'] ?? ($customer ? $customer->whatsapp : ''))),
                'company_name' => trim((string) ($rowData['company'] ?? ($rowData['company_name'] ?? ($customer ? $customer->company_name : '')))),
                'address' => trim((string) ($rowData['address'] ?? ($customer ? $customer->address : ''))),
                'type' => trim((string) ($rowData['type'] ?? ($customer ? $customer->type : ''))),
                'is_active' => true,
            ];

            if ($customer) {
                $customer->update($payload);
                $updated++;
            } else {
                $payload['created_by'] = auth()->id();
                $payload['user_id'] = auth()->id();
                Customer::create($payload);
                $imported++;
            }
        }

        fclose($handle);

        $message = "{$imported} customer(s) imported successfully.";
        if ($updated > 0) {
            $message .= " {$updated} existing customer(s) updated.";
        }
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'summary' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
            'redirect' => '/masters/customers',
        ]);
    }

    private function mapImportRow(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $value = $row[$index] ?? null;
            $mapped[$header] = is_string($value) ? trim($value) : $value;
        }
        return $mapped;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}
