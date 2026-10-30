@extends('layouts.app')

@section('page_title', 'Follow Ups')

@section('content')
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Follow Ups</h4>
                    <p class="text-muted small mb-0">Track and manage your follow up tasks.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('followups.view')
                    <a href="{{ route('followups.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('followups.create')
                    <a href="{{ route('followups.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Follow Up
                    </a>
                    @endcan
                </div>
            </div>
            @include('crm.partials.module-filter-config', ['module' => 'followups', 'searchId' => 'followUpSearch', 'placeholder' => 'Search follow ups...'])
        </div>
        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="followupFilterTabs" role="tablist">
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
                <table class="table table-hover align-middle mb-0 responsive-table">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center">Sr.No</th>
                            <th class="text-start">Purpose &amp; Target</th>
                            <th class="text-center d-none d-md-table-cell">Staff Name</th>
                            <th class="text-center d-none d-lg-table-cell">Created At</th>
                            <th class="text-center d-none d-md-table-cell">Follow Up Date</th>
                            <th class="text-center d-none d-md-table-cell">Status</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 140px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="followUpsTable"></tbody>
                </table>
            </div>
            <div id="followupPaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <style>
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

@push('scripts')
    <script>
    window.crmUserPermissions = {
        followups: {
            view: @json(auth()->user()?->hasMatrixPermission('view_followups')),
            create: @json(auth()->user()?->hasMatrixPermission('create_followups')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_followups')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_followups')),
        }
    };
    </script>
    <script src="{{ crm_asset('js/followup.js') }}"></script>
@endpush
