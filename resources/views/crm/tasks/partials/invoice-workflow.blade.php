@if($task->invoice_id)
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header task-workflow-header p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-sun me-2"></i>Customer & installation workflow</h5>
                <div class="small">Installation stages and updates for this invoice</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if(auth()->user()->hasMatrixPermission('edit_tasks') && auth()->user()->can('update', $task))
                    <a href="{{ route('tasks.edit', $task) }}" class="btn task-workflow-header-btn"><i class="bi bi-pencil me-1"></i>Edit</a>
                @endif
                <a href="{{ route('tasks.index') }}" class="btn task-workflow-header-btn"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="small text-muted">Customer</div><div class="fw-semibold">{{ $customer?->name ?? '-' }}</div><div class="small">{{ $customer?->phone }}</div></div>
            <div class="col-md-4"><div class="small text-muted">Invoice</div>@if($invoice)<a class="fw-semibold" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_name }}</a><div class="small">#{{ $invoice->invoice_no }} &middot; {{ ucfirst($invoice->status) }}</div>@else<div class="small">No accessible invoice</div>@endif</div>
            <div class="col-md-4"><div class="small text-muted">Invoice total</div><div class="fw-semibold">{{ $invoice ? number_format($invoice->amount, 2).' '.($invoice->currency?->code ?? '') : '-' }}</div><div class="small">Assigned: {{ $task->assignedUser?->name ?? 'Unassigned' }}</div></div>
        </div>
        @if($invoice)
            @include('crm.invoices.partials.finance-summary')
        @endif
        @if($workflowSteps->isNotEmpty())
        @php
            $completedSteps = $workflowSteps->where('status', 'completed')->count();
        @endphp
        <div class="d-flex justify-content-between mb-2"><h6 class="fw-bold">Workflow progress</h6><span class="small text-muted">{{ $completedSteps }} / {{ $workflowSteps->count() }} completed</span></div>
        <div class="progress mb-4" style="height:8px"><div class="progress-bar bg-success" style="width:{{ 100 * $completedSteps / $workflowSteps->count() }}%"></div></div>
        <div class="row g-3">
            @foreach($workflowSteps as $step)
                @php
                    $locked = $workflowSteps->take($loop->index)->contains(fn ($previous) => $previous['status'] !== 'completed');
                    $shade = ['pending' => 'secondary', 'in_progress' => 'warning', 'completed' => 'success'][$step['status']] ?? 'secondary';
                @endphp
                @include('crm.tasks.partials.workflow-stage', ['stepNumber' => $loop->iteration])
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif
@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/task-workflow.css') }}">
<style>
.task-workflow-header { background:#071c2c !important; }
.task-workflow-header h5, .task-workflow-header h5 i { color:#fff !important; }
.task-workflow-header .small { color:#d9e6f2 !important; opacity:1 !important; }
.task-workflow-header .task-workflow-header-btn { background:#fff !important; border:1px solid #fff !important; color:#071c2c !important; font-weight:600; }
.task-workflow-header .task-workflow-header-btn i { color:#071c2c !important; }
.task-workflow-header .task-workflow-header-btn:hover,
.task-workflow-header .task-workflow-header-btn:focus-visible { background:#e4edf5 !important; border-color:#e4edf5 !important; color:#071c2c !important; }
</style>
@endpush
@push('scripts')
@unless(auth()->user()->hasMatrixPermission('create_bom'))
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endunless
<script src="{{ crm_asset('js/task-workflow.js') }}"></script>
@endpush
