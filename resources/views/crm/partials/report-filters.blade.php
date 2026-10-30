@php
    $reportOnly = false;
    $filterExportUrl = route($module === 'customers' ? 'masters.customers.export' : 'reports.' . $module . '_report.export', in_array($module, ['tasks', 'followups']) ? request()->all() : []);
    $searchId = $module . 'ReportSearch';
    $placeholder = 'Search ' . ($module === 'followups' ? 'follow ups' : $module) . '...';
    $select = fn ($label, $placeholder, $options) => compact('label','placeholder','options') + ['kind'=>'select'];
    $date = fn ($label) => ['label'=>$label,'kind'=>'date'];
    $labels = fn ($values) => collect($values)->mapWithKeys(fn ($v)=>[$v=>ucwords(str_replace('_',' ',$v))]);
    $staff = fn () => \App\Models\User::orderBy('name')->pluck('name','id');
    $customers = fn () => \App\Models\Customer::orderBy('name')->pluck('name','id');
    $fields = match ($module) {
        'customers' => [
            'created_at'=>$date('From - To'),
            'is_active'=>$select('Status','All statuses',['1'=>'Active','0'=>'Inactive']),
            'type'=>$select('Customer Type','All types',$labels(['Individual','Corporate','Government','NGO'])),
        ],
        'leads' => [
            'created_at'=>$date('Created At'),
            'status'=>$select('Status','All statuses',$labels(['new','qualified','working','ready_to_close','won','lost'])),
            'lead_source_id'=>$select('Lead Source','All sources',\App\Models\LeadSource::orderBy('name')->pluck('name','id')),
            'lead_stage_id'=>$select('Lead Stage','All stages',\App\Models\Stage::orderBy('name')->pluck('name','id')),
            'created_by'=>$select('Created By','All creators',$staff()),
            'assigned_user_id'=>$select('Assigned To','All assignees',$staff()),
        ],
        'deals' => [
            'customer_id'=>$select('Customer Name','All customer names',$customers()),
            'created_by'=>$select('Created By','All staff',$staff()),
            'amount'=>['label'=>'Estimate Amount','kind'=>'range'],
            'status_id'=>$select('Status','All statuses',\App\Models\Status::dealChoices()->pluck('name','id')),
        ],
        'tasks' => [
            'customer_id'=>$select('Customer Name','All customer names',$customers()),
            'assigned_user_id'=>$select('Staff','All staff',$staff()),
            'task_type'=>$select('Task Type','All task types',$labels(['Normal task','Site visit'])),
            'status'=>$select('Status','All statuses',$labels(['pending','in_progress','completed'])),
            'due_date'=>$date('Due Date'),
        ],
        'followups' => [
            'assigned_user_id'=>$select('Staff','All staff',$staff()),
            'created_at'=>$date('Created At'),
            'follow_up_at'=>$date('Follow Up Date'),
            'status'=>$select('Status','All statuses',['pending'=>'Pending','resheduled'=>'Rescheduled','completed'=>'Completed','cancelled'=>'Cancelled']),
        ],
    };
@endphp
@include('crm.partials.list-filters')
