@extends('layouts.app')

@section('page_title', 'Support Tickets')

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

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Tickets</h4>
                    <p class="text-muted small mb-0">Track open support requests and customer issues.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('tickets.view')
                    <a href="{{ route('tickets.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('tickets.create')
                    <a href="{{ route('tickets.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Ticket
                    </a>
                    @endcan
                </div>
            </div>
            @include('crm.partials.module-filter-config', ['module'=>'tickets', 'searchId'=>'ticketsSearch', 'placeholder'=>'Search tickets...'])
        </div>

        <div class="card-body p-0">
            {{-- Tickets don't have assignment feature, so no tabs needed for staff --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="ticketsTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center">Ticket Name</th><th class="d-none d-md-table-cell text-center">Customer</th><th class="d-none d-md-table-cell text-center">Assigned To</th>
                            <th class="d-none d-md-table-cell text-center">Priority</th>
                            <th class="d-none d-md-table-cell text-center">Status</th>
                            <th class="d-none d-md-table-cell text-center">Created At</th>
                            <th class="text-center pe-4 d-none d-md-table-cell" style="width: 120px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.crmUserPermissions = {
    ...(window.crmUserPermissions || {}),
    tickets: {
        view: @json(auth()->user()?->hasMatrixPermission('view_tickets')),
        create: @json(auth()->user()?->hasMatrixPermission('create_tickets')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_tickets')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_tickets')),
    }
};
</script>
<script src="{{ crm_asset('js/tickets-api.js') }}"></script>
@endpush
