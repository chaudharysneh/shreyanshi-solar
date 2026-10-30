@extends('layouts.app')

@section('page_title', 'Products')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Products</h4>
                    <p class="text-muted small mb-0">Track product pricing and categories in the CRM.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('products.view')
                        <a href="{{ route('products.export') }}" class="btn btn-outline-dark-blue">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                    @endcan
                    @can('products.create')
                        <a href="{{ route('products.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Add Product
                        </a>
                    @endcan
                </div>
            </div>
            @include('crm.partials.module-filter-config', ['module' => 'products', 'searchId' => 'productsSearch', 'placeholder' => 'Search products...'])
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="productsTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center" style="width: 80px;">Sr.No</th>
                            <th class="text-center">Product Info</th>
                            <th class="d-none d-md-table-cell text-center">Category</th>
                            <th class="d-none d-md-table-cell text-center">Qty</th>
                            <th class="d-none d-md-table-cell text-center">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="card-footer border-top-0 py-4 px-4" id="productsPagination"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.crmUserPermissions = {
            products: {
                view: @json(auth()->user()?->hasMatrixPermission('view_products')),
                create: @json(auth()->user()?->hasMatrixPermission('create_products')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_products')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_products')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/product.js') }}"></script>
@endpush
