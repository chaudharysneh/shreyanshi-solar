@extends('layouts.app')

@section('page_title', 'Deals')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        #moduleFilters .select2-container { width:100% !important; min-width:0; }
        #moduleFilters .select2-selection--single { min-height:46px; display:flex; align-items:center; border-color:#dce2ea; border-radius:6px; }
        #moduleFilters .select2-selection__rendered { padding-right:24px; }
        .select2-container--bootstrap-5.deal-filter-portal { width:auto !important; max-width:calc(100vw - 16px); }
        .deal-filter-dropdown { max-width:calc(100vw - 16px); }
        .deal-filter-dropdown .select2-results__options { overflow-x:hidden; }
        .deal-filter-dropdown .select2-results__option { overflow-wrap:anywhere; }
        .deal-filter-dropdown .select2-results__option--selected { background:#0a2540 !important; color:#fff !important; }
        .deal-filter-dropdown .select2-results__option--highlighted:not(.select2-results__option--selected) { background:#e4edf5 !important; color:#0a2540 !important; }
    </style>
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Manage Deals</h4>
                        <p class="text-muted small mb-0">Track sales opportunities and deal progress.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('deals.view')
                            <a href="{{ route('deals.export') }}" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('deals.create')
                            <a href="{{ route('deals.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Add Deal
                            </a>
                        @endcan
                    </div>
                </div>
                @include('crm.partials.module-filter-config', ['module' => 'deals', 'searchId' => 'dealsSearch', 'placeholder' => 'Search deals...'])
            </div>
            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="dealsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th class="text-start">Customer Name</th>
                                <th class="d-none d-md-table-cell text-center">Invoice Name</th>
                                <th class="d-none d-md-table-cell text-center">Created By</th>
                                <th class="d-none d-md-table-cell text-center">Invoice Amount</th>
                                <th class="d-none d-md-table-cell text-center">Status</th>
                                <th class="d-none d-md-table-cell text-center" style="width: 160px;">Action</th>
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
    @unless(auth()->user()?->hasMatrixPermission('create_bom'))
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @endunless
    <script src="{{ crm_asset('js/deals-filter-select2.js') }}"></script>
    <script>
        window.crmUserPermissions = {
            deals: {
                view: @json(auth()->user()?->hasMatrixPermission('view_deals')),
                create: @json(auth()->user()?->hasMatrixPermission('create_deals')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_deals')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_deals')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/deal.js') }}"></script>

@endpush
