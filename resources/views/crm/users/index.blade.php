@extends('layouts.app')

@section('page_title', 'Staff & Users')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Staff & Users</h4>
                        <p class="text-muted small mb-0">Manage staff accounts and permissions.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-dark-blue" onclick="showImportDialog()">
                            <i class="fa-solid fa-upload me-1"></i>Import CSV
                        </button>
                        <a href="{{ route('users.export') }}" class="btn btn-outline-dark-blue">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                        <button type="button" class="btn btn-dark-blue" id="addStaffBtn" onclick="handleAddStaffClick(event)">
                            <i class="bi bi-person-plus me-1"></i>Add Staff
                        </button>
                    </div>
                </div>
                @include('crm.partials.module-filter-config', ['module' => 'users', 'searchId' => 'usersSearch', 'placeholder' => 'Search staff...'])
            </div>

            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" class="d-none"
                id="usersImportForm">
                @csrf
                <input type="file" name="import_file" id="usersImportFile" accept=".csv,text/csv"
                    onchange="if(this.files.length){ document.getElementById('usersImportForm').submit(); }">
            </form>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Sr.No</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="text-center d-none d-md-table-cell" style="min-width: 220px;">Action</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="card-footer border-top-0 py-4 px-4" id="usersPagination"></div>
            </div>
        </div>
    </div>

    <div class="modal fade permissions-modal" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel">Staff Permission</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="permissions-table-wrap">
                        <table class="table permissions-matrix-table align-middle mb-0">
                            <colgroup>
                                <col class="module-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="check-cell">View</th>
                                    <th class="check-cell">Insert</th>
                                    <th class="check-cell">Edit</th>
                                    <th class="check-cell">Delete</th>
                                    <th class="check-cell">All</th>
                                </tr>
                            </thead>
                            <tbody id="permissionsModalBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.usersIndexConfig = {
            createUrl: @json(route('users.create')),
            supportEmail: @json('info@fableadtechnolabs.com'),
            staffLimit: @json($staffLimit),
            currentStaffCount: @json($currentStaffCount),
            planName: @json($planName),
        };

        window.usersPermissionsConfig = {
            modules: @json(config('crm_permissions.modules', [])),
            actions: @json(config('crm_permissions.actions', [])),
        };

        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            users: {
                view: true,
                create: true,
                edit: true,
                delete: true,
                permissions: true,
            }
        };
    </script>
    <script
        src="{{ crm_asset('js/users.js') }}"></script>
@endpush