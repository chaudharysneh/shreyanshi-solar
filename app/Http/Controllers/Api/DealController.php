<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\ModuleStatusHistory;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DealController extends ApiBaseController
{
    public function index(Request $request)
    {
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();

        $query = Deal::with(['customer', 'invoice.currency', 'currency', 'status', 'assignedUser', 'stage', 'creator']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice', function ($eq) use ($search) {
                        $eq->where('invoice_name', 'like', "%{$search}%")->orWhere('invoice_no', 'like', "%{$search}%");
                    });
            });
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

        $request->validate(['created_by' => ['nullable', 'integer', 'min:1']]);
        \App\Support\ListFilters::apply($query, $request, ['customer_id','status_id','created_by'], ['created_at']);
        $request->validate([
            'creator_name' => ['nullable','string','max:100'],
            'amount_min' => ['nullable','numeric','min:0'],
            'amount_max' => ['nullable','numeric','min:0'],
        ]);
        if ($request->filled('creator_name')) $query->whereHas('creator', fn ($q) => $q->where('name','like','%' . $request->input('creator_name') . '%'));
        if ($request->filled('amount_min')) $query->where('amount','>=',$request->input('amount_min'));
        if ($request->filled('amount_max')) $query->where('amount','<=',$request->input('amount_max'));
        $deals = $query->latest()->paginate($request->integer('per_page',10));

        return response()->json([
            'success' => true,
            'message' => 'Deals retrieved successfully',
            'data' => $deals,
        ], 200);
    }

    public function customerInvoices(Request $request)
    {
        $user = $request->user();
        if (!$user || (!$user->hasMatrixPermission('create_deals') && !$user->hasMatrixPermission('edit_deals') && !$user->hasMatrixPermission('view_deals'))) return response()->json(['message' => 'Access denied.'], 403);
        $data = $request->validate(['customer_id' => ['required', 'integer']]);
        $customer = Customer::visibleTo($user)->findOrFail($data['customer_id']);
        $invoices = $this->availableDealInvoices($user)->where('customer_id', $customer->id)->get();
        return response()->json(['success' => true, 'data' => $invoices->map(fn ($invoice) => [
            'invoice_id' => $invoice->id, 'invoice_name' => $invoice->invoice_name ?: $invoice->invoice_no,
            'customer_id' => $invoice->customer_id, 'amount' => $invoice->amount, 'payable_amount' => (float) $invoice->amount,
        ])]);
    }

    private function availableDealInvoices($user)
    {
        return Invoice::query()->when(!$user->isAdmin(), fn ($q) => $q->where(fn ($owned) => $owned->where('user_id', $user->id)->orWhere('created_by', $user->id)))->orderByDesc('id');
    }

    public function customerEstimates(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $canUseDealEstimates = $user->isAdmin()
            || (method_exists($user, 'hasMatrixPermission') && (
                $user->hasMatrixPermission('create_deals')
                || $user->hasMatrixPermission('edit_deals')
                || $user->hasMatrixPermission('view_deals')
            ));

        if (!$canUseDealEstimates) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::visibleTo($user)->find($validated['customer_id']);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $query = Estimate::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('estimate_date')
            ->orderBy('estimate_name');

        if (!$user->isAdmin()) {
            $userId = (int) $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('created_by', $userId);
            });
        }

        $estimates = $query->get([
            'estimate_id',
            'estimate_name',
            'customer_id',
            'amount',
            'total',
            'gst_amount',
            'discount',
            'subsidy_amount',
        ]);

        $estimates = $estimates->map(function (Estimate $estimate) {
            $payable = (float) ($estimate->amount ?? 0);
            if ($payable <= 0) {
                $payable = (float) ($estimate->total ?? 0)
                    + (float) ($estimate->gst_amount ?? 0)
                    - (float) ($estimate->discount ?? 0)
                    - (float) ($estimate->subsidy_amount ?? 0);
            }

            return [
                'estimate_id' => $estimate->estimate_id,
                'estimate_name' => $estimate->estimate_name,
                'customer_id' => $estimate->customer_id,
                'amount' => $estimate->amount,
                'total' => $estimate->total,
                'gst_amount' => $estimate->gst_amount,
                'discount' => $estimate->discount,
                'subsidy_amount' => $estimate->subsidy_amount,
                'payable_amount' => max(0, round($payable, 2)),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $estimates,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Deal::class);

        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'timeline_value' => ['required', 'integer', 'min:1'],
            'timeline_unit' => ['required', Rule::in(['days', 'months'])],
            'status_comment' => ['nullable', 'string', 'max:2000'],
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal')->where('is_active', true)->whereIn('name', Status::DEAL_NAMES),
            ],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'signature_path' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,avif', 'max:51200'],
        ];

        if (Schema::hasColumn('deals', 'probability')) {
            $rules['probability'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            $rules['stage_id'] = ['nullable', 'integer', 'exists:stages,id,is_active,1'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'customer_id.required' => 'Customer is required',
            'amount.required' => 'Invoice amount is required',
            'amount.gt' => 'Invoice amount must be greater than 0',
            'amount.max' => 'Invoice amount is too large',
            'timeline_value.required' => 'Timeline is required and must be greater than 0',
            'timeline_value.min' => 'Timeline is required and must be greater than 0',
            'timeline_unit.required' => 'Timeline is required and must be greater than 0',
            'status_id.required' => 'Deal status is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the following errors:',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if ($request->hasFile('signature_path')) {
            $data['signature_path'] = $request->file('signature_path')->store('deals/signatures', 'public');
        }
        $data = $this->prepareDealData($data);
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser((int) $data['assigned_user_id']);
        if (Deal::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? Auth::id();
        }

        if (empty($data['currency_id'])) {
            $defaultCurrency = Currency::where('is_default', true)->first() ?? Currency::orderBy('id')->first();
            if ($defaultCurrency) {
                $data['currency_id'] = $defaultCurrency->id;
            }
        }

        if (Schema::hasColumn('deals', 'created_by')) {
            $data['created_by'] = Auth::id();
        }

        try {
            $deal = Deal::create($data);
            app(\App\Services\UserLogService::class)->created($deal);
            $this->ensureCustomerForWonDeal($deal);
            $this->createAutoTaskForDeal($deal);


            send_admin_notification('Deal', 'Created', $deal->title, []);

            return response()->json([
                'success' => true,
                'message' => 'Deal created successfully!',
                'data' => $deal->fresh(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator']),
                'history_entry' => null,
                'redirect' => route('deals.index'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deal. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $deal = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])
            ->findOrFail($id);
        $this->authorize('view', $deal);

        return response()->json([
            'success' => true,
            'message' => 'Deal retrieved successfully',
            'data' => $deal,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorize('update', $deal);
        $originalStatusId = $deal->status_id;
        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'timeline_value' => ['required', 'integer', 'min:1'],
            'timeline_unit' => ['required', Rule::in(['days', 'months'])],
            'status_comment' => ['nullable', 'string', 'max:2000'],
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal')->where('is_active', true)->whereIn('name', Status::DEAL_NAMES),
            ],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'signature_path' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,avif', 'max:51200'],
        ];

        if (Schema::hasColumn('deals', 'probability')) {
            $rules['probability'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            $rules['stage_id'] = ['nullable', 'integer', 'exists:stages,id,is_active,1'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'customer_id.required' => 'Customer is required',
            'amount.required' => 'Invoice amount is required',
            'amount.gt' => 'Invoice amount must be greater than 0',
            'amount.max' => 'Invoice amount is too large',
            'timeline_value.required' => 'Timeline is required and must be greater than 0',
            'timeline_value.min' => 'Timeline is required and must be greater than 0',
            'timeline_unit.required' => 'Timeline is required and must be greater than 0',
            'status_id.required' => 'Deal status is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the following errors:',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if ($request->hasFile('signature_path')) {
            if ($deal->signature_path && Storage::disk('public')->exists($deal->signature_path)) {
                Storage::disk('public')->delete($deal->signature_path);
            }
            $data['signature_path'] = $request->file('signature_path')->store('deals/signatures', 'public');
        }
        $data = $this->prepareDealData($data, $deal);
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser((int) $data['assigned_user_id']);
        if (Deal::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? $deal->user_id ?? Auth::id();
        }

        if (empty($data['currency_id'])) {
            $data['currency_id'] = $deal->currency_id;
            if (!$data['currency_id']) {
                $defaultCurrency = Currency::where('is_default', true)->first() ?? Currency::orderBy('id')->first();
                if ($defaultCurrency) {
                    $data['currency_id'] = $defaultCurrency->id;
                }
            }
        }

        try {
            $deal->update($data);
            if (
                (array_key_exists('status_id', $data) && (int) $data['status_id'] !== (int) $originalStatusId)
                || filled($data['status_comment'] ?? null)
            ) {
                $historyEntry = $this->recordStatusHistory($deal, $data['status_id'] ?? $deal->status_id, $data['status_comment'] ?? null);
            }
            app(\App\Services\UserLogService::class)->updated($deal);
            $this->ensureCustomerForWonDeal($deal);


            send_admin_notification('Deal', 'Updated', $deal->title, []);

            return response()->json([
                'success' => true,
                'message' => 'Deal updated successfully!',
                'data' => $deal->fresh(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator']),
                'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
                'redirect' => route('deals.index', $deal),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deal. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorize('delete', $deal);
        $dealName = $deal->title;
        app(\App\Services\UserLogService::class)->deleted($deal);
        $deal->delete();

        send_admin_notification('Deal', 'Deleted', $dealName ?? 'N/A', []);

        return response()->json([
            'success' => true,
            'message' => 'Deal deleted successfully!',
        ]);
    }

    public function updateStatus(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal')->where('is_active', true)->whereIn('name', Status::DEAL_NAMES),
            ],
        ]);

        $deal->update([
            'status_id' => $data['status_id'],
        ]);
        $this->ensureCustomerForWonDeal($deal);


        return response()->json([
            'success' => true,
            'message' => 'Deal status updated.',
        ]);
    }

    private function ensureCustomerForWonDeal(Deal $deal): void
    {
        $status = Status::find($deal->status_id);
        if (!$this->isWonStatus($status?->name)) {
            return;
        }

        $customer = $deal->customer_id ? Customer::find($deal->customer_id) : null;
        if ($customer) {
            return;
        }

        $customerData = [
            'name' => 'Customer - ' . $deal->title,
            'email' => null,
            'phone' => null,
            'is_active' => true,
        ];

        if (Schema::hasColumn('customers', 'created_by')) {
            $customerData['created_by'] = Auth::id();
        }

        if (Schema::hasColumn('customers', 'updated_by')) {
            $customerData['updated_by'] = Auth::id();
        }

        $customer = Customer::create($customerData);

        $deal->update([
            'customer_id' => $customer->id,
        ]);
    }

    private function createAutoTaskForDeal(Deal $deal): void
    {
        Task::create([
            'title' => 'Send proposal',
            'description' => 'Auto task for new deal: ' . $deal->title,
            'related_type' => 'deal',
            'related_id' => $deal->id,
            'assigned_user_id' => $deal->assigned_user_id,
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'status' => 'pending',
        ]);
    }

    private function createOrUpdateProjectFromDeal(Deal $deal): void
    {
        $status = Status::find($deal->status_id);
        if (!$this->isWonStatus($status?->name)) {
            return;
        }

        if ($this->hasBookingProjectConflict($deal)) {
            return;
        }

        $projectQuery = Project::query();
        if (Schema::hasColumn('projects', 'deal_id')) {
            $projectQuery->where('deal_id', $deal->id);
        }

        $project = $projectQuery->first();
        if (!$project) {
            $createData = [
                'name' => $this->projectNameFromDeal($deal),
                'customer_id' => $deal->customer_id,
                'assigned_user_id' => $deal->assigned_user_id,
                'status' => 'planning',
            ];

            if (Schema::hasColumn('projects', 'project_code')) {
                $createData['project_code'] = $this->generateProjectCode();
            }

            if (Schema::hasColumn('projects', 'deal_id')) {
                $createData['deal_id'] = $deal->id;
            }

            Project::create($createData);
        } else {
            $updateData = [
                'name' => $this->projectNameFromDeal($deal),
                'customer_id' => $deal->customer_id,
                'assigned_user_id' => $deal->assigned_user_id,
            ];

            if (Schema::hasColumn('projects', 'deal_id')) {
                $updateData['deal_id'] = $deal->id;
            }

            $project->update($updateData);
        }
    }

    private function projectNameFromDeal(Deal $deal): string
    {
        $customerName = $deal->customer?->name ?? 'Customer';
        return $deal->title . ' - ' . $customerName;
    }

    private function generateProjectCode(): string
    {
        do {
            $code = 'PRJ-' . now()->format('Ymd') . '-' . strtoupper(str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT));
        } while (Project::where('project_code', $code)->exists());

        return $code;
    }

    private function hasBookingProjectConflict(Deal $deal): bool
    {
        if (!Schema::hasColumn('projects', 'booking_id')) {
            return false;
        }
        if (!Schema::hasColumn('projects', 'customer_id')) {
            return false;
        }

        $hasStartDate = Schema::hasColumn('projects', 'start_date');
        $hasEndDate = Schema::hasColumn('projects', 'end_date');
        if (!$hasStartDate || !$hasEndDate) {
            return false;
        }

        $query = Project::query()
            ->whereNotNull('booking_id')
            ->where('customer_id', $deal->customer_id);

        if ($deal->expected_close_date) {
            $expected = Carbon::parse($deal->expected_close_date)->startOfDay();
            $query->whereDate('start_date', '<=', $expected)
                ->whereDate('end_date', '>=', $expected);
        } else {
            $query->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '>=', Carbon::today());
            });
        }

        return $query->exists();
    }

    private function validateCloseDate($validator, Request $request): void
    {
        $expected = $request->input('expected_close_date');
        $statusId = $request->input('status_id');
        $customerId = $request->input('customer_id');

        if (!$expected || !$statusId) {
            return;
        }

        $status = Status::find($statusId);
        if (!$status) {
            return;
        }

        $statusName = strtolower((string) $status->name);
        if (in_array($statusName, ['won', 'won/confirm', 'lost'], true)) {
            $this->validateAgainstLeadCreated($validator, $expected, $customerId);
            return;
        }

        try {
            $expectedDate = Carbon::parse($expected)->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        if ($expectedDate->lt(Carbon::today())) {
            $validator->errors()->add('expected_close_date', 'Close date cannot be in the past unless the deal is Won or Lost.');
        }

        $this->validateAgainstLeadCreated($validator, $expected, $customerId);
    }

    private function validateAgainstLeadCreated($validator, string $expected, ?string $customerId): void
    {
        if (!$customerId) {
            return;
        }

        $lead = Lead::where('converted_customer_id', $customerId)
            ->orderBy('created_at')
            ->first();

        if (!$lead || !$lead->created_at) {
            return;
        }

        try {
            $expectedDate = Carbon::parse($expected)->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        if ($expectedDate->lt($lead->created_at->startOfDay())) {
            $validator->errors()->add('expected_close_date', 'Close date cannot be before the lead creation date.');
        }
    }

    private function recordStatusHistory(Deal $deal, $statusId, ?string $comment): ?ModuleStatusHistory
    {
        $statusName = null;

        if ($statusId) {
            $statusName = Status::find($statusId)?->name;
        }

        if (!$statusName && !filled($comment)) {
            return null;
        }

        try {
            return ModuleStatusHistory::create([
                'historable_type' => Deal::class,
                'historable_id' => $deal->id,
                'status' => $statusName,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Deal status history save skipped.', [
                'deal_id' => $deal->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?ModuleStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        $status = strtolower((string) $history->status);

        return [
            'status_label' => match ($status) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'won/confirm' => 'Won/Confirm',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '-',
            },
            'comment' => $history->comment ?: '-',
            'updated_by' => $history->updater?->name ?? Auth::user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function ensureAssignableUser(int $assignedUserId): void
    {
        if (Auth::user()?->isAdmin()) {
            return;
        }

        abort_unless($assignedUserId === (int) Auth::id(), 403, 'You can only assign records to yourself.');
    }

    private function prepareDealData(array $data, ?Deal $deal = null): array
    {
        $invoice = null;
        if (!empty($data['invoice_id'])) {
            $invoice = $this->availableDealInvoices(auth()->user())->where('customer_id', $data['customer_id'])->find($data['invoice_id']);
            if (!$invoice) throw \Illuminate\Validation\ValidationException::withMessages(['invoice_id' => 'Select an accessible invoice for this customer.']);
            $data['estimate_id'] = $invoice->estimate_id;
            $data['amount'] = $invoice->amount;
            $data['currency_id'] = $invoice->currency_id;
            $data['title'] = $invoice->invoice_name ?: $invoice->invoice_no;
        } elseif (array_key_exists('invoice_id', $data)) {
            $data['estimate_id'] = null;
        }
        $estimate = null;

        if (!empty($data['estimate_id'])) {
            $estimate = Estimate::with('customer')->find($data['estimate_id']);
        }

        $data['assigned_user_id'] = (int) ($data['assigned_user_id'] ?? $deal?->assigned_user_id ?? Auth::id());
        $data['title'] = $this->resolveDealTitle($data, $estimate, $deal);
        $data['expected_close_date'] = $this->resolveExpectedCloseDate($data);

        if (Schema::hasColumn('deals', 'probability')) {
            $data['probability'] = array_key_exists('probability', $data)
                ? ($data['probability'] ?? 0)
                : ($deal?->probability ?? 0);
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            $data['stage_id'] = $data['stage_id']
                ?? $deal?->stage_id
                ?? \App\Models\Stage::where('is_active', true)->orderBy('name')->value('id');
        }

        return $data;
    }

    private function resolveDealTitle(array $data, ?Estimate $estimate, ?Deal $deal = null): string
    {
        if (!empty($data['title'])) {
            return trim((string) $data['title']);
        }

        if ($estimate && filled($estimate->estimate_name)) {
            return (string) $estimate->estimate_name;
        }

        $customer = Customer::find($data['customer_id'] ?? $deal?->customer_id);
        if ($customer && filled($customer->name)) {
            return 'Deal - ' . $customer->name;
        }

        return $deal?->title ?: 'Deal';
    }

    private function resolveExpectedCloseDate(array $data): string
    {
        $timelineValue = (int) ($data['timeline_value'] ?? 0);
        $timelineUnit = strtolower((string) ($data['timeline_unit'] ?? 'days'));
        $date = Carbon::today();

        if ($timelineUnit === 'months') {
            return $date->addMonths($timelineValue)->toDateString();
        }

        return $date->addDays($timelineValue)->toDateString();
    }

    private function isWonStatus(?string $statusName): bool
    {
        $status = strtolower(trim((string) $statusName));

        return in_array($status, ['won', 'won/confirm'], true);
    }
}
