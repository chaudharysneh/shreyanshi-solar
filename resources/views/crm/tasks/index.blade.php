@extends('layouts.app')

@section('page_title', 'Tasks')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <style>
        .task-action-modal .modal-header {
            background: #0a2540;
        }

        .task-action-error {
            display: none;
            margin-top: 0.25rem;
        }

        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Tasks</h4>
                    <p class="text-muted small mb-0">Track team tasks, priorities, and deadlines.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('tasks.view')
                    <a href="{{ route('tasks.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('tasks.create')
                    <a href="{{ route('tasks.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Task
                    </a>
                    @endcan
                </div>
            </div>
            @include('crm.partials.module-filter-config', ['module' => 'tasks', 'searchId' => 'tasksSearch', 'placeholder' => 'Search tasks...'])
        </div>
        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="taskFilterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                            Created By Me
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                            Assigned To Me
                        </button>
                    </li>
                </ul>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="tasksTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-center" style="width: 80px;">Sr.No</th>
                            <th class="text-center">Customer</th>
                            <th class="text-center d-none d-md-table-cell">Staff Name</th>
                            <th class="text-center d-none d-md-table-cell">Invoice</th>
                            <th class="text-center">Task</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 1%; white-space: nowrap;">Task Type</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 1%; white-space: nowrap;">Status</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 100px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="tasksPagination" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>

    <div class="modal fade task-action-modal" id="taskActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title mb-0 text-white">Task Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="taskActionForm" novalidate>
                        <input type="hidden" id="taskActionTaskId">
                        <input type="hidden" id="taskActionNextStatus">

                        <div class="mb-4">
                            <label for="taskActionComment" class="form-label fw-semibold">Comment <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea id="taskActionComment" class="form-control" rows="2" placeholder="Add comment if needed"></textarea>
                        </div>

                        <div id="taskActionStartFields" class="d-none">
                            <div class="mb-3">
                                <label for="taskActionImages" class="form-label fw-semibold">Upload Images/ Docs</label>
                                <input type="file" id="taskActionImages" class="form-control" multiple>
                            </div>
                        </div>

                        <div id="taskActionEndFields" class="d-none">
                            <div class="mb-3">
                                <label for="taskActionLightBill" class="form-label fw-semibold">Upload Light Bill</label>
                                <input type="file" id="taskActionLightBill" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="taskActionMeasurements" class="form-label fw-semibold">Upload Measurements</label>
                                <input type="file" id="taskActionMeasurements" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="taskActionSitePhoto" class="form-label fw-semibold">Upload Site Photo</label>
                                <input type="file" id="taskActionSitePhoto" class="form-control">
                            </div>
                        </div>

                        <div id="taskActionFormAlert" class="invalid-feedback d-block task-action-error mb-3"></div>

                        <div class="d-flex justify-content-end pt-3 border-top mt-4">
                            <button type="submit" class="btn btn-dark-blue px-4" id="taskActionSubmitBtn">
                                <span class="spinner-border spinner-border-sm d-none me-2" id="taskActionSpinner"></span>
                                <span id="taskActionSubmitText">Submit Task</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.crmUserPermissions = {
    tasks: {
        view: @json(auth()->user()?->hasMatrixPermission('view_tasks')),
        create: @json(auth()->user()?->hasMatrixPermission('create_tasks')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_tasks')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_tasks')),
    }
};
</script>
<script src="{{ crm_asset('js/tasks.js') }}"></script>
@endpush
