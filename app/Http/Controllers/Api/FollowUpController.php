<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\FollowUp;
use App\Models\FollowUpStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FollowUpController extends ApiBaseController
{
    private const FOLLOW_UP_RULES = [
        'lead_id' => ['required', 'integer', 'exists:leads,id'],
        'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        'purpose' => ['required', 'string', 'max:255'],
        'comment' => ['nullable', 'string', 'max:2000'],
        'status_comment' => ['nullable', 'string', 'max:2000'],
        'priority' => ['required', 'in:low,medium,high'],
        'status' => ['required', 'in:pending,resheduled,completed,cancelled'],
    ];

    public function index(Request $request)
    {
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();

        $query = FollowUp::with(['lead', 'assignedUser', 'creator']);

        if ($request->has('lead_id') && $request->lead_id) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('priority', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn($q2) => $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('assignedUser', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->has('follow_up_at') && $request->follow_up_at) {
            $query->whereDate('follow_up_at', $request->follow_up_at);
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
        \App\Support\ListFilters::apply($query, $request, ['assigned_user_id', 'created_by', 'status'], ['created_at', 'follow_up_at'], []);
        $followUps = $query->latest('follow_up_at')->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Follow-ups retrieved successfully',
            'data' => $followUps,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', FollowUp::class);

        $validator = $this->makeValidator($request, true);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Handle timezone conversion for follow_up_at
        if (isset($data['follow_up_at']) && $request->has('browser_timezone_offset')) {
            try {
                $browserOffset = (int) $request->input('browser_timezone_offset');
                $appOffset = (new \DateTime('now', new \DateTimeZone(config('app.timezone'))))->getOffset();
                $offsetDiff = ($appOffset - ($browserOffset * 60)) / 60; // Convert to hours
                
                // Parse the datetime - handle both formats (with T separator or space)
                $dateString = str_replace('T', ' ', $data['follow_up_at']);
                $dt = \Carbon\Carbon::parse($dateString);
                $dt->addHours($offsetDiff);
                $data['follow_up_at'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If parsing fails, just use the original value
                Log::warning('Failed to parse follow_up_at date in store: ' . $e->getMessage());
            }
        }
        
        $this->ensureVisibleLead((int) $data['lead_id']);
        $data['assigned_user_id'] = $data['assigned_user_id'] ?? Auth::id();
        $this->ensureAssignableUser((int) $data['assigned_user_id']);
        if (FollowUp::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? Auth::id();
        }
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $followUp = FollowUp::create($data);
        app(\App\Services\UserLogService::class)->created($followUp, 'Created a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));

        // ── Email: Admin Notification (staff activity) ─────────────────
        $followUp->loadMissing(['lead']);
        send_admin_notification('Follow-Up', 'Created', $followUp->lead?->name ?? 'Unknown', []);

        // ── Email: Follow-Up Assigned (if assigned to a staff) ───────────
        if (!empty($data['assigned_user_id'])) {
            send_followup_assigned_notification($followUp);
        }

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up created successfully',
            'history_entry' => null,
            'redirect' => route('followups.index'),
        ], 201);
    }

    public function show($id)
    {
        $followUp = FollowUp::with(['lead', 'customer', 'assignedUser', 'creator'])->find($id);

        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('view', $followUp);

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up retrieved successfully'
        ]);
    }


    public function update(Request $request, $id)
    {
        $followUp = FollowUp::find($id);
        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('update', $followUp);

        $originalStatus = $followUp->status;
        $validator = $this->makeValidator($request, false);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Handle timezone conversion for follow_up_at
        if (isset($data['follow_up_at']) && $request->has('browser_timezone_offset')) {
            try {
                $browserOffset = (int) $request->input('browser_timezone_offset');
                $appOffset = (new \DateTime('now', new \DateTimeZone(config('app.timezone'))))->getOffset();
                $offsetDiff = ($appOffset - ($browserOffset * 60)) / 60; // Convert to hours
                
                // Parse the datetime - handle both formats (with T separator or space)
                $dateString = str_replace('T', ' ', $data['follow_up_at']);
                $dt = \Carbon\Carbon::parse($dateString);
                $dt->addHours($offsetDiff);
                $data['follow_up_at'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If parsing fails, just use the original value
                Log::warning('Failed to parse follow_up_at date in update: ' . $e->getMessage());
            }
        }
        
        $this->ensureVisibleLead((int) $data['lead_id']);
        $data['assigned_user_id'] = $data['assigned_user_id'] ?? $followUp->assigned_user_id ?? Auth::id();
        $this->ensureAssignableUser((int) $data['assigned_user_id']);
        if (FollowUp::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? $followUp->user_id ?? Auth::id();
        }
        $data['updated_by'] = auth()->id();
        $followUp->update($data);
        $historyEntry = null;

        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($followUp, $data['status'] ?? $followUp->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($followUp, 'Updated a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));

        // ── Email: Admin Notification (staff activity) ─────────────────
        $followUp->loadMissing(['lead']);
        send_admin_notification('Follow-Up', 'Updated', $followUp->lead?->name ?? 'Unknown', []);

        // ── Email: Follow-Up Assigned (if newly assigned to a staff) ─────
        if (!empty($data['assigned_user_id']) && $data['assigned_user_id'] != $followUp->getOriginal('assigned_user_id')) {
            send_followup_assigned_notification($followUp);
        }

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up updated successfully',
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('followups.index')
        ]);
    }

    public function destroy($id)
    {
        $followUp = FollowUp::find($id);

        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('delete', $followUp);

        $followUp->update(['deleted_by' => auth()->id()]);
        app(\App\Services\UserLogService::class)->deleted($followUp, 'Deleted a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));
        $followUp->delete();

        send_admin_notification('Follow-Up', 'Deleted', $followUpName ?? 'N/A', []);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up deleted successfully'
        ]);
    }

    public function apiByLead($id)
    {
        $followUps = FollowUp::with(['lead', 'assignedUser', 'creator'])
            ->where('lead_id', $id)
            ->latest('follow_up_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $followUps,
            'message' => 'Follow-ups retrieved successfully'
        ]);
    }

    public function getLeadAssignedUser($leadId)
    {
        $lead = \App\Models\Lead::with('assignedUser')->find($leadId);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $this->authorize('view', $lead);

        return response()->json([
            'success' => true,
            'data' => [
                'assigned_user' => $lead->assignedUser ? [
                    'id' => $lead->assignedUser->id,
                    'name' => $lead->assignedUser->name,
                    'email' => $lead->assignedUser->email,
                ] : null
            ],
            'message' => 'Lead assigned user retrieved successfully'
        ]);
    }

    private function recordStatusHistory(FollowUp $followUp, ?string $status, ?string $comment): ?FollowUpStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return FollowUpStatusHistory::create([
                'follow_up_id' => $followUp->id,
                'status' => $status ?? $followUp->status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Follow-up status history save skipped.', [
                'follow_up_id' => $followUp->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?FollowUpStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        $history->loadMissing('updater');

        return [
            'status' => $history->status,
            'status_label' => \Illuminate\Support\Str::of($history->status)->replace('_', ' ')->title()->toString(),
            'comment' => $history->comment ?: '-',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '-',
        ];
    }

    private function makeValidator(Request $request, bool $enforceFutureDate): \Illuminate\Contracts\Validation\Validator
    {
        $rules = self::FOLLOW_UP_RULES + [
            'follow_up_at' => $enforceFutureDate
                ? ['required', 'date', 'after_or_equal:today']
                : ['required', 'date'],
        ];

        return Validator::make($request->all(), $rules, $this->validationMessages());
    }

    private function validationMessages(): array
    {
        return [
            'lead_id.required' => 'Lead is required!',
            'lead_id.exists' => 'Please select a valid lead.',
            'assigned_user_id.required' => 'Staff name is required!',
            'assigned_user_id.exists' => 'Please select a valid staff member.',
            'purpose.required' => 'Purpose is required!',
            'priority.required' => 'Priority is required!',
            'priority.in' => 'Please select a valid priority.',
            'status.required' => 'Status is required!',
            'status.in' => 'Please select a valid status.',
            'follow_up_at.required' => 'Date/Time is required!',
            'follow_up_at.date' => 'Please enter a valid date/time.',
            'follow_up_at.after_or_equal' => 'Date/Time must be today or later.',
        ];
    }

    private function ensureVisibleLead(int $leadId): void
    {
        $lead = \App\Models\Lead::findOrFail($leadId);
        $this->authorize('view', $lead);
    }

    private function ensureAssignableUser(int $assignedUserId): void
    {
        if (Auth::user()?->isAdmin()) {
            return;
        }

        abort_unless($assignedUserId === (int) Auth::id(), 403, 'You can only assign records to yourself.');
    }
}
