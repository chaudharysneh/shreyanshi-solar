@extends('layouts.app')

@section('page_title', 'Inventory')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Inventory</h4>
                    <p class="text-muted small mb-0">Track product stock levels and inventory changes.</p>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Inventory List</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search inventory..." id="inventorySearch" value="{{ request('search') }}">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center" style="width: 80px;">Sr.No</th>
                            <th class="text-center">Product Info</th>
                            <th class="d-none d-md-table-cell text-center">Current Stock</th>
                            <th class="d-none d-md-table-cell text-center">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 200px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="card-footer border-top-0 py-4 px-4" id="inventoryPagination"></div>
        </div>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog" aria-labelledby="editStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white" id="editStockModalLabel">Edit Product Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStockForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control" id="modalProductName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Stock</label>
                        <input type="text" class="form-control" id="modalCurrentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stock Update</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="updateType" id="addQuantity" value="add" checked>
                                <label class="form-check-label" for="addQuantity">Add Quantity</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="updateType" id="minusQuantity" value="minus">
                                <label class="form-check-label" for="minusQuantity">Minus Quantity</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="quantityLabel">How much to Add?</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="decreaseQty">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="form-control text-center" id="modalQuantityChange" placeholder="0" min="0" value="0" required>
                            <button type="button" class="btn btn-outline-secondary" id="increaseQty">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="quantityError"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue px-4 rounded-3" id="saveStockBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.crmUserPermissions = {
            inventory: {
                view: @json(auth()->user()?->hasMatrixPermission('view_inventory')),
                create: @json(auth()->user()?->hasMatrixPermission('create_inventory')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_inventory')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_inventory')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/inventory.js') }}"></script>
@endpush
