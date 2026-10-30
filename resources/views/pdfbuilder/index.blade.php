@extends('layouts.app')

@section('page_title', 'PDF Templates')

@section('content')
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage PDF Templates</h4>
                    <p class="text-muted small mb-0">Track and manage your custom PDF templates.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('templates.create')
                        <a href="{{ route('pdfbuilder.create') }}" class="btn btn-dark-blue">
                            <i class="fa-solid fa-plus me-1"></i>Add Template
                        </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">All Templates</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="templateSearch" class="form-control crm-search-input border-0" placeholder="Search templates...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table pdfbuilder-templates-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Template Name</th>
                            <th class="d-none d-md-table-cell">Created Date</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 150px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="templatesTableBody"></tbody>
                </table>
            </div>
            <div id="templatePaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<style>
    #templatesTableBody td {
        font-size: 0.95rem;
    }
    #templatesTableBody .template-name span {
        font-size: 0.95rem;
    }
    #templatesTableBody .template-time {
        font-size: 0.82rem;
    }
    .pdfbuilder-templates-table thead th {
        font-size: 0.8rem;
    }
</style>
@endpush

@push('scripts')
<script>
window.crmUserPermissions = {
    pdfbuilder: {
        view: @json(auth()->user()?->hasMatrixPermission('view_templates')),
        create: @json(auth()->user()?->hasMatrixPermission('create_templates')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_templates')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_templates')),
    }
};
window.pdfbuilderApiUrl = "{{ route('pdfbuilder.api.templet') }}";
window.pdfbuilderApiDeletePath = "/api/v1/pdfbuilderApi/delete/"; // Base path for deletion
</script>
<script src="{{ crm_asset('js/pdfbuilder.js') }}"></script>
@endpush
