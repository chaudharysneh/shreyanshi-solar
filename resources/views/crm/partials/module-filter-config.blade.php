@php
    $select = fn ($label, $placeholder, $options) => compact('label', 'placeholder', 'options') + ['kind' => 'select'];
    $date = fn ($label) => ['label' => $label, 'kind' => 'date'];
    $text = fn ($label, $placeholder) => compact('label', 'placeholder') + ['kind' => 'text'];
    $labels = fn ($values) => collect($values)->mapWithKeys(fn ($v) => [$v => ucwords(str_replace('_', ' ', $v))])->all();
    $staff = fn () => \App\Models\User::orderBy('name')->pluck('name', 'id');
    $types = $labels(['residential', 'commercial', 'industrial']);
    $fields = match ($module) {
        'tickets' => [
            'customer_id' => $select('Customer', 'All customers', \App\Models\Customer::visibleTo(auth()->user())->orderBy('name')->pluck('name','id')),
            'assigned_user_id' => $select('Assigned To', 'All assignees', $staff()),
            'priority' => $select('Priority', 'All priorities', $labels(['Low','Medium','High'])),
            'status' => $select('Status', 'All statuses', $labels(['Open','In Progress','Resolved','Closed'])),
            'created_at' => $date('Created At'),
        ],
        'products' => [
            'category_id' => $select('Category', 'All category', \App\Models\Categories::orderBy('name')->pluck('name','id')),
            'quantity' => $text('Qty', 'Search qty'), 'created_at' => $date('Created At'),
        ],
        'deals' => [
            'customer_id' => $select('Customer Name', 'All customer name', \App\Models\Customer::orderBy('name')->pluck('name','id')),
            'created_by' => $select('Created By', 'All creators', \App\Models\User::whereIn('id', \App\Models\Deal::query()->select('created_by')->distinct())->orderBy('name')->pluck('name', 'id')),
            'created_at' => $date('Created At'),
            'status_id' => $select('Status', 'All status', \App\Models\Status::dealChoices()->pluck('name','id')),
        ],
        'tasks' => [
            'customer_id' => $select('Customer Name', 'All customer name', \App\Models\Customer::orderBy('name')->pluck('name','id')),
            'assigned_user_id' => $select('Staff Name', 'All staff name', $staff()),
            'task_type' => $select('Task Type', 'All task type', ['Normal task'=>'Normal task','Site visit'=>'Site visit']),
            'status' => $select('Status', 'All status', $labels(['pending','in_progress','completed'])),
            'due_date' => $date('Due Date'),
        ],
        'users' => [
            'role_id' => $select('Role', 'All roles', \Spatie\Permission\Models\Role::whereNotIn('name',['admin','super-admin'])->orderBy('name')->pluck('name','id')),
            'job_title' => $select('Job Title', 'All job titles', \App\Models\User::whereNotNull('job_title')->where('job_title','!=','')->distinct()->orderBy('job_title')->pluck('job_title','job_title')),
            'is_active' => $select('Status', 'All statuses', ['1'=>'Active','0'=>'Inactive']),
            'created_at' => $date('Created At'),
        ],
        'followups' => [
            'created_by' => $select('Created By', 'All creators', auth()->user()?->isAdmin() ? $staff() : \App\Models\User::whereIn('id', \App\Models\FollowUp::query()->select('created_by')->distinct())->orderBy('name')->pluck('name', 'id')),
            'created_at' => $date('Created At'), 'follow_up_at' => $date('Follow Up Date'),
            'status' => $select('Status', 'All statuses', ['pending'=>'Pending','resheduled'=>'Rescheduled','completed'=>'Completed','cancelled'=>'Cancelled']),
        ],
        'meetings' => [
            'customer_id' => $select('Customer', 'All customers', \App\Models\Customer::orderBy('name')->pluck('name', 'id')),
            'created_by' => $select('Created By', 'All creators', auth()->user()?->isAdmin() ? $staff() : \App\Models\User::whereIn('id', \App\Models\Meeting::query()->select('created_by')->distinct())->orderBy('name')->pluck('name', 'id')),
            'scheduled_at' => $date('Scheduled On'),
            'meeting_type' => $select('Meeting Type', 'All types', $labels(['virtual','in-person','telephonic'])),
            'status' => $select('Status', 'All statuses', $labels(['scheduled','completed','cancelled'])),
        ],
        'bom' => [
            'category_id' => $select('Make', 'All make', \App\Models\Category::orderBy('name')->pluck('name','id')),
            'technology_id' => $select('Technology', 'All technology', \App\Models\Technology::orderBy('title')->pluck('title','id')),
            'warranty_id' => $select('Warranty', 'All warranty', \App\Models\Warranty::orderBy('title')->pluck('title','id')),
            'created_at' => $date('Created At'),
        ],
        'estimates' => [
            'estimate_no' => $text('Estimate No', 'Search estimate no'),
            'type' => $select('Estimate Type', 'All estimate type', $types),
            'estimate_date' => $date('Estimate Date'),
            'status' => $select('Status', 'All status', $labels(['pending','approved','rejected','completed'])),
        ],
        'invoices' => [
            'invoice_no' => $text('Invoice No', 'Search invoice no'),
            'type' => $select('Invoice Type', 'All invoice type', $types),
            'invoice_date' => $date('Invoice Date'), 'due_date' => $date('Due Date'),
            'status' => $select('Status', 'All status', ['unpaid'=>'Unpaid','partial'=>'Partially paid','paid'=>'Paid','cancelled'=>'Cancelled']),
        ],
    };
@endphp
@include('crm.partials.list-filters')
