@extends('layouts.app')

@section('page_title', 'Purchases')

@push('styles')
    <link rel="stylesheet"
        href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Manage Purchases</h4>
                        <p class="text-muted small mb-0">Track all purchase invoices and material IN transactions.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if (auth()->user()?->hasMatrixPermission('view_products'))
                            <a href="{{ route('purchases.export') }}" class="btn btn-outline-dark-blue" id="purchasesExportBtn">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endif
                        @can('purchases.create')
                            <a href="{{ route('purchases.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Material IN
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Purchase List</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search purchases..."
                            id="purchasesSearch" value="{{ request('search') }}">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="purchasesTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center" style="width: 60px;">Sr.No</th>
                            <th class="text-center">Vendor Name</th>
                            <th class="d-none d-md-table-cell text-center">IN No</th>
                            <th class="d-none d-md-table-cell text-center">IN Date</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="card-footer border-top-0 py-4 px-4" id="purchasesPagination"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.crmUserPermissions = {
            purchases: {
                view: @json(auth()->user()?->hasMatrixPermission('view_purchases')),
                create: @json(auth()->user()?->hasMatrixPermission('create_purchases')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_purchases')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_purchases')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/purchase.js') }}"></script>
@endpush
