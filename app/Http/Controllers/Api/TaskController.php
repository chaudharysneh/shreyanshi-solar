<?php

namespace App\Http\Controllers\Api;

use App\Models\ModuleStatusHistory;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskController extends ApiBaseController
{
    public function updateWorkflowStep(Request $request, Task $task)
    {
        $this->authorize('update', $task);
        $data = $request->validate([
            'stage_id' => ['required', 'integer'],
            'status' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:1000'],
            'check_in_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'check_out_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv', 'max:10240'],
        ]);
        app(\App\Services\TaskWorkflowService::class)->update($task, $data, $request->file('attachments', []), $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Workflow stage updated.']);
    }

    private const TASK_UPLOAD_MIMES = 'jpg,jpeg,png,gif,webp,bmp,avif,pdf,doc,docx';

    public function index(Request $request)
    {
        $search = $request->get('search');
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();

        $tasks = Task::with(['assignedUser', 'owner', 'customer', 'project.customer', 'invoice'])
            ->withExists([
                'statusHistories as has_completed_history' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('assignedUser', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                            $invoiceQuery->where('invoice_name', 'like', "%{$search}%")
                                ->orWhere('invoice_no', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function ($projectQuery) use ($search) {
                            $projectQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when(!$user->isAdmin() && $filter === 'created_by_me', function ($query) use ($user) {
                // All records I created/own (using user_id since tasks don't have created_by)
                $query->where('user_id', $user->id);
            })
            ->when(!$user->isAdmin() && $filter === 'assigned_to_me', function ($query) use ($user) {
                // Records assigned to me but NOT owned by me
                $query->where('assigned_user_id', $user->id)
                      ->where(function ($q) use ($user) {
                          $q->whereNull('user_id')
                            ->orWhere('user_id', '!=', $user->id);
                      });
            })
            ->tap(function ($query) use ($request) {
                \App\Support\ListFilters::apply($query,$request,['assigned_user_id','task_type','status'],['due_date']);
                $request->validate(['customer_id'=>['nullable','integer']]);
                if ($request->filled('customer_id')) {
                    $query->where(function ($q) use ($request) {
                        $q->whereHas('customer', fn ($c) => $c->whereKey($request->integer('customer_id')))
                            ->orWhere(function ($fallback) use ($request) {
                                $fallback->whereDoesntHave('customer')->whereHas('project', fn ($p) => $p->where('customer_id',$request->integer('customer_id')));
                            });
                    });
                }
            })
            ->latest()
            ->paginate($request->integer('per_page',10))
            ->withQueryString();

        $tasks->getCollection()->transform(function (Task $task) {
            $task->task_action_mode = $this->resolveTaskActionMode($task);

            return $task;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => $tasks,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $invoice = $this->resolveVisibleInvoice((int) $data['invoice_id']);
        $data['related_id'] = $invoice->customer_id;
        $data['estimate_id'] = $invoice->estimate_id;
        $data['project_id'] = null;
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null);
        $data['related_type'] = !empty($data['related_id']) ? 'customer' : null;
        if (Task::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? Auth::id();
        }

        $task = Task::create($data);
        app(\App\Services\UserLogService::class)->created($task);

        if ($request->has('custom_fields')) {
            $task->saveCustomFields($request->get('custom_fields'));
        }

        // WhatsApp notification to assigned staff
        try {
            $task->load(['assignedUser', 'customer', 'project.customer', 'invoice']);

            $projectName = $task->invoice?->invoice_name ?: ($task->invoice?->invoice_no ?: '--');
            $customer = $task->customer ?: $task->project?->customer;
            $customerName = $customer?->name ?: 'Customer';
            $dueDateText = $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d M Y') : '';
            $priorityText = $task->priority ? ucfirst(str_replace('_', ' ', $task->priority)) : '';
            $descText = $task->description ?: '';

            $staffPhone = $task->assignedUser?->whatsapp ?: $task->assignedUser?->phone;
            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_assigned_staff',
                    $staffPhone,
                    [
                        $task->assignedUser->name ?? 'Staff',
                        $projectName,
                        $task->title ?? 'Task',
                        $customerName,
                        $dueDateText,
                        $priorityText,
                        $descText,
                    ],
                    $task->id
                );
            }

            $customerPhone = $customer?->whatsapp ?: $customer?->phone;
            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_created_customer',
                    $customerPhone,
                    [
                        $customerName,
                        $projectName,
                        $task->title ?? 'Task',
                        $task->assignedUser?->name ?? 'Staff',
                        $dueDateText,
                        $descText,
                    ],
                    $task->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Task create WhatsApp block failed', [
                'task_id' => $task->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // ── Email: Task Assigned (if assigned to a staff) ──────────────────
        if (!empty($data['assigned_user_id'])) {
            send_task_assigned_notification($task);
        }

        // ── Email: Admin Notification (staff activity) ──────────────────
        send_admin_notification('Task', 'Created', $task->title, []);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task->fresh(['assignedUser', 'owner', 'customer', 'project.customer', 'invoice']),
            'history_entry' => null,
            'redirect' => route('tasks.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $task = Task::with(['assignedUser', 'owner', 'customer', 'project.customer', 'project.creator', 'invoice'])->findOrFail($id);
        $this->authorize('view', $task);

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully.',
            'data' => $task,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $originalStatus = $task->status;
        if($task->task_type==='solar_workflow') $request->merge(['task_type'=>'solar_workflow','status'=>$task->status]);
        $validator = Validator::make($request->all(), $this->rules(true), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if($task->task_type!=='solar_workflow' && $data['task_type']==='solar_workflow') return response()->json(['success'=>false,'errors'=>['task_type'=>['Solar workflow is created automatically from invoice approval.']]],422);
        $invoice = $this->resolveVisibleInvoice((int) $data['invoice_id']);
        $data['related_id'] = $invoice->customer_id;
        $data['estimate_id'] = $invoice->estimate_id;
        $data['project_id'] = null;
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null);
        $data['related_type'] = !empty($data['related_id']) ? 'customer' : null;
        if (Task::supportsOwnedByUserColumn()) {
            $data['user_id'] = $data['assigned_user_id'] ?? $task->user_id ?? Auth::id();
        }

        $task->update($data);
        $historyEntry = null;
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($task, $data['status'] ?? $task->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($task);

        if ($request->has('custom_fields')) {
            $task->saveCustomFields($request->get('custom_fields'));
        }

        try {
            $task->load(['assignedUser', 'customer', 'project.customer', 'invoice']);

            $projectName = $task->invoice?->invoice_name ?: ($task->invoice?->invoice_no ?: '--');
            $customer = $task->customer ?: $task->project?->customer;
            $customerName = $customer?->name ?: 'Customer';
            $customerPhone = $customer?->whatsapp ?: $customer?->phone;
            $staffName = $task->assignedUser?->name ?? 'Staff';
            $staffPhone = $task->assignedUser?->whatsapp ?: $task->assignedUser?->phone;
            $dueDateText = $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d M Y') : '';
            $priorityText = $task->priority ? ucfirst(str_replace('_', ' ', $task->priority)) : '';
            $statusText = $task->status ? ucwords(str_replace('_', ' ', $task->status)) : '';
            $descText = $task->description ?: '';

            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_updated_staff',
                    $staffPhone,
                    [
                        $staffName,
                        $projectName,
                        $task->title ?? 'Task',
                        $customerName,
                        $dueDateText,
                        $priorityText,
                        $statusText,
                        $descText,
                    ],
                    $task->id
                );
            }

            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_updated_customer',
                    $customerPhone,
                    [
                        $customerName,
                        $projectName,
                        $task->title ?? 'Task',
                        $staffName,
                        $dueDateText,
                        $statusText,
                        $descText,
                    ],
                    $task->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Task update WhatsApp block failed', [
                'task_id' => $task->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // ── Email: Task Assigned (if newly assigned to a staff) ───────────
        if (!empty($data['assigned_user_id']) && $data['assigned_user_id'] != $task->getOriginal('assigned_user_id')) {
            send_task_assigned_notification($task);
        }

        // ── Email: Admin Notification ────────────────────────────────
        send_admin_notification('Task', 'Updated', $task->title, []);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task->fresh(['assignedUser', 'owner', 'customer', 'project.customer', 'invoice']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('tasks.index', $task),
        ]);
    }

    public function quickStatusUpdate(Task $task, Request $request)
    {
        $this->authorize('update', $task);

        $task->loadMissing(['assignedUser', 'owner', 'customer', 'project.customer', 'invoice']);

        $currentStatus = Task::normalizeStatusValue($task->status);
        $requestedNextStatus = Task::normalizeStatusValue($request->input('next_status'));
        $actionMode = $this->resolveTaskActionMode($task);

        $nextStatus = match ($actionMode) {
            'start' => 'in_progress',
            'end' => 'completed',
            default => null,
        };

        if (!$nextStatus || ($requestedNextStatus && $requestedNextStatus !== $nextStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'Task action is not available for the current status.',
            ], 422);
        }

        $rules = [
            'comment' => ['nullable', 'string', 'max:2000'],
            'location_latitude' => ['required', 'numeric', 'between:-90,90'],
            'location_longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:2000'],
        ];

        if ($nextStatus === 'in_progress') {
            $rules['images'] = ['nullable', 'array'];
            $rules['images.*'] = ['file', 'mimes:' . self::TASK_UPLOAD_MIMES, 'max:10240'];
        }

        if ($nextStatus === 'completed') {
            $rules['light_bill'] = ['nullable', 'file', 'mimes:' . self::TASK_UPLOAD_MIMES, 'max:10240'];
            $rules['measurements'] = ['nullable', 'file', 'mimes:' . self::TASK_UPLOAD_MIMES, 'max:10240'];
            $rules['site_photo'] = ['nullable', 'file', 'mimes:' . self::TASK_UPLOAD_MIMES, 'max:10240'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'images.required' => 'Please upload at least one image.',
            'images.min' => 'Please upload at least one image.',
            'light_bill.required' => 'Please upload Light Bill.',
            'measurements.required' => 'Please upload Measurements.',
            'site_photo.required' => 'Please upload Site Photo.',
            'location_latitude.required' => 'Location access is required to continue.',
            'location_longitude.required' => 'Location access is required to continue.',
            'images.*.mimes' => 'Please upload a valid image or document file.',
            'light_bill.mimes' => 'Please upload a valid Light Bill file.',
            'measurements.mimes' => 'Please upload a valid Measurements file.',
            'site_photo.mimes' => 'Please upload a valid Site Photo file.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = $validator->validated()['comment'] ?? null;

        $task->update(['status' => $nextStatus]);
        $this->storeTaskActionDocuments($task, $request, $nextStatus);

        $historyEntry = $this->recordStatusHistory($task, $nextStatus, $comment, [
            'location_address' => $validator->validated()['location_address'] ?? null,
            'location_latitude' => $validator->validated()['location_latitude'] ?? null,
            'location_longitude' => $validator->validated()['location_longitude'] ?? null,
        ]);
        app(\App\Services\UserLogService::class)->updated($task);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'data' => $task->fresh(['assignedUser', 'owner', 'customer', 'project.customer', 'invoice']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
        ]);
    }

    private function storeTaskActionDocuments(Task $task, Request $request, string $status): void
    {
        $documentType = Task::class;

        if ($status === 'in_progress' && $request->hasFile('images')) {
            foreach ((array) $request->file('images') as $index => $file) {
                if (!$file) {
                    continue;
                }

                $path = $file->store('documents', 'public');

                Document::create([
                    'title' => 'Task Start Image ' . ($index + 1),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'documentable_id' => $task->id,
                    'documentable_type' => $documentType,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        if ($status !== 'completed') {
            return;
        }

        $singleFiles = [
            'light_bill' => 'Task End Light Bill',
            'measurements' => 'Task End Measurements',
            'site_photo' => 'Task End Site Photo',
        ];

        foreach ($singleFiles as $field => $title) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = $file->store('documents', 'public');

            Document::create([
                'title' => $title,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'documentable_id' => $task->id,
                'documentable_type' => $documentType,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);
        $taskTitle = $task->title;
        app(\App\Services\UserLogService::class)->deleted($task);
        $task->delete();

        send_admin_notification('Task', 'Deleted', $taskTitle ?? 'Unknown', []);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function rules(bool $updating = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'related_id' => ['nullable', 'exists:customers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'status' => ['nullable', 'in:pending,in_progress,completed'],
            'task_type' => ['required', $updating ? 'in:Normal task,Site visit,solar_workflow' : 'in:Normal task,Site visit'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function messages(): array
    {
        return [
            'invoice_id.required' => 'Invoice is required.',
            'invoice_id.exists' => 'Please select a valid invoice.',
            'title.required' => 'Task title is required.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'due_date.date' => 'Please enter a valid due date.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Please select a valid priority.',
            'status.required' => 'Status is required.',
            'status.in' => 'Please select a valid status.',
            'task_type.required' => 'Task Type is required.',
            'task_type.in' => 'Please select a valid task type.',
            'assigned_user_id.required' => 'Staff is required.',
            'assigned_user_id.exists' => 'Please select a valid staff user.',
        ];
    }

    private function recordStatusHistory(Task $task, ?string $status, ?string $comment, array $extra = []): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return ModuleStatusHistory::create([
                'historable_type' => Task::class,
                'historable_id' => $task->id,
                'status' => $status,
                'comment' => filled($comment) ? $comment : null,
                'location_address' => $extra['location_address'] ?? null,
                'location_latitude' => $extra['location_latitude'] ?? null,
                'location_longitude' => $extra['location_longitude'] ?? null,
                'updated_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Task status history save skipped.', [
                'task_id' => $task->id,
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

        $history->loadMissing('updater');

        return [
            'status' => $history->status,
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '-',
            'comment' => $history->comment ?: '-',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '-',
        ];
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function ensureVisibleProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('view', $project);
    }

    private function resolveVisibleInvoice(int $invoiceId): Invoice
    {
        $invoice = Invoice::with('customer')->findOrFail($invoiceId);
        $this->authorize('view', $invoice);

        return $invoice;
    }

    private function ensureAssignableUser(?int $assignedUserId): void
    {
        if (!$assignedUserId || Auth::user()?->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) Auth::id(), 403, 'You can only assign records to yourself.');
    }

    private function resolveTaskActionMode(Task $task): ?string
    {
        $status = Task::normalizeStatusValue($task->status);

        if ($status === 'pending') {
            return 'start';
        }

        if ($status === 'in_progress') {
            return 'end';
        }

        return null;
    }
}
