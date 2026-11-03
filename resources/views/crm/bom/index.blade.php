@extends('layouts.app')

@section('page_title', 'BOM')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<style>
    #quickBomModal .select2-container { width: 100% !important; }
    #quickBomModal .select2-selection { min-height: 38px; }
    #quickBomModal .select2-selection--multiple { padding-bottom: 2px; }
    #quickBomModal .select2-selection.is-invalid { border-color: #dc3545; }
    #quickBomModal .modal-header .modal-title,
    #quickBomModal .modal-header .modal-title i { color: #fff !important; }
    #quickBomModal .modal-header p { color: rgba(255, 255, 255, .65) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Manage BOM</h1>
                    <p class="text-muted small mb-0">Manage BOM inventory records in Solar CRM.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('bom.create')
                        <button type="button" class="btn btn-outline-dark-blue" data-bs-toggle="modal" data-bs-target="#quickBomModal">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Quick BOM
                        </button>
                        <a href="{{ route('bom-products.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Add BOM
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 px-md-4 py-3 bg-light border-bottom">
                @include('crm.partials.module-filter-config', ['module' => 'bom', 'searchId' => 'bomProductsSearch', 'placeholder' => 'Search BOM...'])
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="bomProductsTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 100px;">Estimate default</th>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">HSN/SAC</th>
                            <th class="d-none d-md-table-cell">Make</th>
                            <th class="d-none d-md-table-cell">Technology</th>
                            <th class="d-none d-md-table-cell">Warranty</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="card-footer border-top-0 py-4 px-4" id="bomProductsPagination"></div>
        </div>
    </div>
</div>

@can('bom.create')
<div class="modal fade" id="quickBomModal" tabindex="-1" aria-labelledby="quickBomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="quickBomModalLabel"><i class="bi bi-lightning-charge-fill me-2"></i>Quick BOM</h5>
                    <p class="small text-white-50 mb-0">Create a BOM quickly with the essential details.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickBomForm" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="quick_product_name" class="form-label fw-semibold"><i class="bi bi-box-seam me-2 text-muted" aria-hidden="true"></i>BOM <span class="text-danger">*</span></label>
                            <input type="text" name="product_name" id="quick_product_name" class="form-control" required maxlength="255" placeholder="Enter new BOM name" autocomplete="off">
                            <div class="invalid-feedback" data-error-for="product_name"></div>
                        </div>
                        <div class="col-12">
                            <label for="quick_hsn_sac" class="form-label fw-semibold"><i class="bi bi-hash me-2 text-muted" aria-hidden="true"></i>HSN/SAC Code</label>
                            <input type="text" name="hsn_sac" id="quick_hsn_sac" class="form-control" maxlength="50" placeholder="Enter HSN/SAC code">
                            <div class="invalid-feedback" data-error-for="hsn_sac"></div>
                        </div>
                        <div class="col-12">
                            <label for="quick_category_id" class="form-label fw-semibold"><i class="bi bi-buildings me-2 text-muted" aria-hidden="true"></i>Make</label>
                            <select name="category_id[]" id="quick_category_id" class="form-select quick-bom-select quick-bom-creatable" multiple data-placeholder="Search or create Make">
                                @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="category_id"></div>
                        </div>
                        <div class="col-12">
                            <label for="quick_price" class="form-label fw-semibold"><i class="bi bi-currency-rupee me-2 text-muted" aria-hidden="true"></i>Price</label>
                            <input type="number" name="price" id="quick_price" class="form-control" min="0" step="0.01" placeholder="Enter price (optional)">
                            <div class="invalid-feedback" data-error-for="price"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_technology_id" class="form-label fw-semibold"><i class="bi bi-cpu me-2 text-muted" aria-hidden="true"></i>Technology</label>
                            <select name="technology_id" id="quick_technology_id" class="form-select quick-bom-select quick-bom-creatable" data-placeholder="Search or create Technology">
                                <option value=""></option>
                                @foreach($technologies as $technology)<option value="{{ $technology->id }}">{{ $technology->title }}</option>@endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="technology_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="quick_warranty_id" class="form-label fw-semibold"><i class="bi bi-shield-check me-2 text-muted" aria-hidden="true"></i>Warranty</label>
                            <select name="warranty_id" id="quick_warranty_id" class="form-select quick-bom-select quick-bom-creatable" data-placeholder="Search or create Warranty">
                                <option value=""></option>
                                @foreach($warranties as $warranty)<option value="{{ $warranty->id }}">{{ $warranty->title }}</option>@endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="warranty_id"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue" id="saveQuickBomBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" aria-hidden="true"></span>
                        <span class="button-text">Add BOM</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    window.bomProductsConfig = {
        indexUrl: @json(route('api.bom-products.index')),
        updateUrlTemplate: @json(route('api.bom-products.update', ['bomProduct' => '__ID__'])),
        storeUrl: @json(route('api.bom-products.store')),
        makeStoreUrl: @json(route('api.make.store')),
        technologyStoreUrl: @json(route('api.technology.store')),
        warrantyStoreUrl: @json(route('api.warranty.store')),
        destroyUrlTemplate: @json(route('api.bom-products.destroy', ['bomProduct' => '__ID__'])),
        editUrlTemplate: @json(route('bom-products.edit', ['bomProduct' => '__ID__'])),
        showUrlTemplate: @json(route('bom-products.show', ['bomProduct' => '__ID__']))
    };
    window.crmUserPermissions = {
        ...(window.crmUserPermissions || {}),
        bom: {
            view: @json(auth()->user()?->hasMatrixPermission('view_bom')),
            create: @json(auth()->user()?->hasMatrixPermission('create_bom')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_bom')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_bom')),
        }
    };
</script>
<script src="{{ crm_asset('js/bom-products.js') }}"></script>
@endpush
