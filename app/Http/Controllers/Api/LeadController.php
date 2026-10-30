<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeadController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();
        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', 'exists:lead_statuses,code'],
            'lead_source_id' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $leads = Lead::with(['leadSource', 'stage', 'assignedUser', 'creator'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('sic_code', 'like', "%{$search}%");
                });
            })
            ->when(!$user->isAdmin() && $filter === 'created_by_me', function ($query) use ($user) {
                // All records I created (regardless of assignment)
                $query->where('created_by', $user->id);
            })
            ->when(!$user->isAdmin() && $filter === 'assigned_to_me', function ($query) use ($user) {
                // Records assigned to me but NOT created by me
                $query->where('assigned_user_id', $user->id)
                      ->where('created_by', '!=', $user->id);
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $filters['status']))
            ->when($request->filled('lead_source_id'), fn ($q) => $q->where('lead_source_id', $filters['lead_source_id']))
            ->when($request->filled('created_by'), fn ($q) => $q->where('created_by', $filters['created_by']))
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $labels = \App\Models\LeadStatus::options();
        $leads->getCollection()->each(function ($lead) use ($labels) {
            $lead->setAttribute('status_label', $labels[$lead->status] ?? $lead->status);
        });

        return response()->json([
            'success' => true,
            'message' => 'Leads retrieved successfully',
            'data' => $leads,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:10', 'min:10', 'unique:leads,phone'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'sic_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'exists:lead_statuses,code'],
            'source' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            ...$this->sourceDetailRules($request),
            'lead_stage_id' => ['nullable', 'exists:stages,id,is_active,1'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data = $this->normalizeSourceDetails($data);
        $data['status'] = $data['status'] ?? 'new';
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null);
        if (Lead::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? auth()->id();
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('leads', 'public');
        }

        $lead = Lead::create($data);

        app(\App\Services\UserLogService::class)->created($lead);

        if ($request->has('custom_fields')) {
            $lead->saveCustomFields($request->get('custom_fields'));
        }

        // WhatsApp notification — fire & forget, never breaks main flow
        try {
            $phone = $lead->whatsapp ?: $lead->phone;
            if ($phone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'customer_created',
                    $phone,
                    [$lead->name],
                    $lead->id
                );
            }
        } catch (\Throwable) {
        }

        // ── Email: Lead Assigned (if assigned to a staff) ──────────────────
        if (!empty($data['assigned_user_id'])) {
            $lead->loadMissing(['assignedUser', 'leadSource', 'creator']);
            send_lead_assigned_notification($lead);
        }

        // ── Email: Admin Notification (staff activity) ──────────────────
        send_admin_notification('Lead', 'Created', $lead->name, []);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => $lead->fresh(['leadSource', 'stage', 'assignedUser', 'creator']),
            'history_entry' => null,
            'redirect' => route('leads.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $lead = Lead::with(['leadSource', 'stage', 'assignedUser', 'creator'])->findOrFail($id);
        $this->authorize('view', $lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead retrieved successfully',
            'data' => $lead,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('update', $lead);
        $originalStatus         = $lead->status;
        $originalAssignedUserId = $lead->assigned_user_id;
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:10', 'min:10', 'unique:leads,phone,' . $lead->id],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'sic_code' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'exists:lead_statuses,code'],
            'source' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            ...$this->sourceDetailRules($request, $lead),
            'lead_stage_id' => ['nullable', 'exists:stages,id,is_active,1'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data = $this->normalizeSourceDetails($data, $lead);
            $this->ensureAssignableUser($data['assigned_user_id'] ?? null);
            if (Lead::supportsOwnedByUserColumn()) {
                $data['user_id'] = $data['assigned_user_id'] ?? $lead->user_id ?? auth()->id();
            }

            if ($request->hasFile('image')) {
                if ($lead->image) {
                    Storage::disk('public')->delete($lead->image);
                }

                $data['image'] = $request->file('image')->store('leads', 'public');
            }

            $lead->update($data);

            if (
                (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
                || filled($data['status_comment'] ?? null)
            ) {
                $historyEntry = $this->recordStatusHistory($lead, $data['status'] ?? $lead->status, $data['status_comment'] ?? null);
            }
            app(\App\Services\UserLogService::class)->updated($lead);

            if ($request->has('custom_fields')) {
                $lead->saveCustomFields($request->get('custom_fields'));
            }

            // ── Email: Lead Assigned (if assignment changed) ───────────────
            if (!empty($data['assigned_user_id'])
                && (int) $data['assigned_user_id'] !== (int) $originalAssignedUserId
            ) {
                $lead->loadMissing(['assignedUser', 'leadSource', 'creator']);
                send_lead_assigned_notification($lead);
            }

            // ── Email: Admin Notification (staff activity) ──────────────
            send_admin_notification('Lead', 'Updated', $lead->name, []);

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully.',
                'data' => $lead->fresh(['leadSource', 'stage', 'assignedUser', 'creator']),
                'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
                'redirect' => route('leads.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Lead update failed', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update lead right now.',
                'errors' => null,
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('delete', $lead);

        if ($lead->image) {
            Storage::disk('public')->delete($lead->image);
        }

        app(\App\Services\UserLogService::class)->deleted($lead);
        
        $leadName = $lead->name;
        $lead->delete();

        send_admin_notification('Lead', 'Deleted', $leadName ?? 'N/A', []);

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }

    private function ensureAssignableUser(?int $assignedUserId): void
    {
        if (!$assignedUserId) {
            return;
        }

        if (auth()->user()?->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) auth()->id(), 403, 'You can only assign records to yourself.');
    }

    private function recordStatusHistory(Lead $lead, ?string $status, ?string $comment): ?LeadStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return LeadStatusHistory::create([
                'lead_id' => $lead->id,
                'status' => $status ?? $lead->status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Lead status history save skipped.', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?LeadStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        return [
            'status_label' => \App\Models\LeadStatus::where('code', $history->status)->value('name') ?? match (strtolower((string) $history->status)) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '-',
            },
            'comment' => $history->comment ?: '-',
            'updated_by' => $history->updater?->name ?? auth()->user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }

    private function sourceDetailRules(Request $request, ?Lead $lead = null): array
    {
        $source = \App\Models\LeadSource::find($request->input('lead_source_id', $lead?->lead_source_id));
        return [
            'referral_name' => [\Illuminate\Validation\Rule::requiredIf($source?->kind === 'referral'), 'nullable', 'string', 'max:255'],
            'channel_partner_id' => [\Illuminate\Validation\Rule::requiredIf($source?->kind === 'channel_partner'), 'nullable', 'integer',
                \Illuminate\Validation\Rule::exists('channel_partners', 'id')->where(function ($query) use ($lead) {
                    $query->where(function ($query) use ($lead) {
                        $query->where('status', 'active');
                        if ($lead?->channel_partner_id) $query->orWhere('id', $lead->channel_partner_id);
                    });
                }),
            ],
        ];
    }

    private function normalizeSourceDetails(array $data, ?Lead $lead = null): array
    {
        $sourceId = array_key_exists('lead_source_id', $data) ? $data['lead_source_id'] : $lead?->lead_source_id;
        $source = \App\Models\LeadSource::find($sourceId);
        if ($source) $data['source'] = $source->name;
        $data['referral_name'] = $source?->kind === 'referral' ? ($data['referral_name'] ?? null) : null;
        $data['channel_partner_id'] = $source?->kind === 'channel_partner' ? ($data['channel_partner_id'] ?? null) : null;
        return $data;
    }

    private function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'image.mimes' => 'Please select a valid image! Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB!',
        ];
    }
}
