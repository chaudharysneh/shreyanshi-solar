@extends('layouts.app')

@section('page_title', 'Tasks - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden task-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Task</h1>
                        <p class="text-muted small mb-0">Update task details for the team.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tasks.view')
                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/tasks/{{ $task->id }}" id="taskForm"
                    class="needs-validation ajax-task-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-diagram-project me-2 text-muted"></i>Invoice <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100" style="min-width: 0;">
                                    <select name="invoice_id" id="invoice_id"
                                        class="form-select @error('invoice_id') is-invalid @enderror" required>
                                        <option value="">Select Invoice</option>
                                        @foreach($invoices as $invoice)
                                            <option value="{{ $invoice->id }}"
                                                @selected(old('invoice_id', $selectedInvoiceId) == $invoice->id)>
                                                {{ $invoice->invoice_name ?: $invoice->invoice_no ?: 'Invoice #' . $invoice->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="invoice_id-error">{{ $errors->first('invoice_id') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-user me-2 text-muted"></i>Assigned To <span class="text-danger">*</span></label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror">
                                    <option value="">-- Search User --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', $task->assigned_user_id) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback d-block" id="assigned_user_id-error">
                                {{ $errors->first('assigned_user_id') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-list-check me-2 text-muted"></i>Task Title <span class="text-danger">*</span></label>
                            <input name="title" id="title" value="{{ old('title', $task->title) }}"
                                class="form-control @error('title') is-invalid @enderror" required>
                            <div class="invalid-feedback d-block" id="title-error">{{ $errors->first('title') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-bars me-2 text-muted"></i>Description</label>
                            <textarea name="description" id="description" rows="2"
                                class="form-control @error('description') is-invalid @enderror"
                                required>{{ old('description', $task->description) }}</textarea>
                            <div class="invalid-feedback d-block" id="description-error">{{ $errors->first('description') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-days me-2 text-muted"></i>Due Date</label>
                            <input type="date" name="due_date" id="due_date"
                                value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"
                                class="form-control @error('due_date') is-invalid @enderror">
                            <div class="invalid-feedback d-block" id="due_date-error">{{ $errors->first('due_date') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-arrow-up-long me-2 text-muted"></i>Priority</label>
                            <select name="priority" id="priority"
                                class="form-select searchable-select @error('priority') is-invalid @enderror" required>
                                <option value="">Select Priority</option>
                                <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Medium
                                </option>
                                <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error">{{ $errors->first('priority') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-circle-info me-2 text-muted"></i>Status <span class="text-danger">*</span></label>
                            <select name="status" id="status"
                                class="form-select searchable-select @error('status') is-invalid @enderror js-status-comment-trigger"
                                required>
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('status', $task->status) === 'pending')>Pending</option>
                                <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>In
                                    Progress</option>
                                <option value="completed" @selected(old('status', $task->status) === 'completed')>Completed
                                </option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error">{{ $errors->first('status') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-tags me-2 text-muted"></i>Task Type <span class="text-danger">*</span></label>
                            <select name="task_type" id="task_type" class="form-select searchable-select @error('task_type') is-invalid @enderror" required>
                                @if($task->task_type === 'solar_workflow')
                                <option value="solar_workflow" selected>Solar workflow</option>
                                @else
                                <option value="">Select Task Type</option>
                                <option value="Normal task" @selected(old('task_type', $task->task_type) === 'Normal task')>Normal task</option>
                                <option value="Site visit" @selected(old('task_type', $task->task_type) === 'Site visit')>Site visit</option>
                                @endif
                            </select>
                            <div class="invalid-feedback d-block" id="task_type-error">{{ $errors->first('task_type') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($task->task_type === 'solar_workflow' && $workflowSteps->isNotEmpty())
        <section class="card border-0 shadow-sm rounded-4 mt-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Workflow stages & statuses</h5><p class="text-muted small">Update each installation stage below. Overall task status follows stage progress automatically.</p><div class="row g-3">
        @foreach($workflowSteps as $step)
            @php
                $locked=$workflowSteps->take($loop->index)->contains(fn($previous)=>$previous['status']!=='completed');
                $shade=['pending'=>'secondary','in_progress'=>'warning','completed'=>'success'][$step['status']] ?? 'secondary';
            @endphp
            @include('crm.tasks.partials.workflow-stage',['stepNumber'=>$loop->iteration])
        @endforeach
        </div></div></section>
        @endif
        @include('crm.partials.status-history-table' , ['histories' => $task->statusHistories])
    </div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ crm_asset('css/task-workflow.css') }}">
        <style>
            .task-edit-dropdown .select2-results__option[aria-selected="true"] {background:#0b2942!important;color:white!important;}
            .task-edit-dropdown .select2-results__option--highlighted[aria-selected="false"] {background:#e5edf4!important;color:#0b2942!important;}
            .select2-results__options {
                max-height: 200px !important;
                overflow-y: auto !important;
            }
            #quickEstimateModal .modal-dialog { z-index: 1055; }
            #addCustomerModal, #quickAddBomModal { z-index: 1065 !important; }
            body.modal-open .modal-backdrop.show ~ .modal-backdrop.show { z-index: 1060; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="{{ crm_asset('js/tasks.js') }}"></script>
        <script src="{{ crm_asset('js/task-workflow.js') }}"></script>
        <script>
            $(document).ready(function() {
                $('#invoice_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#invoice_id').closest('.flex-grow-1')
                });

                $('#assigned_user_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                $('#status,#task_type,#priority').select2({theme:'bootstrap-5',width:'100%',dropdownCssClass:'task-edit-dropdown'});
                @if($task->task_type === 'solar_workflow')
                $('#status').prop('disabled',true).trigger('change.select2');
                @endif
                flatpickr("#due_date", {
                    allowInput: true,
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    
                });
            });
        </script>
    @endpush
@endsection

