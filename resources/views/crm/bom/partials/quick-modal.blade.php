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
                <div class="modal-body"><div class="row g-3">
                    <div class="col-12">
                        <label for="quick_product_name" class="form-label fw-semibold"><i class="bi bi-box-seam me-2 text-muted"></i>BOM <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="quick_product_name" class="form-control" required maxlength="255" placeholder="Enter new BOM name" autocomplete="off">
                        <div class="invalid-feedback" data-error-for="product_name"></div>
                    </div>
                    <div class="col-12">
                        <label for="quick_hsn_sac" class="form-label fw-semibold"><i class="bi bi-hash me-2 text-muted"></i>HSN/SAC Code</label>
                        <input type="text" name="hsn_sac" id="quick_hsn_sac" class="form-control" maxlength="50" placeholder="Enter HSN/SAC code">
                        <div class="invalid-feedback" data-error-for="hsn_sac"></div>
                    </div>
                    <div class="col-12">
                        <label for="quick_category_id" class="form-label fw-semibold"><i class="bi bi-buildings me-2 text-muted"></i>Make</label>
                        <select name="category_id[]" id="quick_category_id" class="form-select quick-bom-select quick-bom-creatable" multiple data-placeholder="Search or create Make">
                            @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                        </select>
                        <div class="invalid-feedback" data-error-for="category_id"></div>
                    </div>
                    <div class="col-12">
                        <label for="quick_price" class="form-label fw-semibold"><i class="bi bi-currency-rupee me-2 text-muted"></i>Price</label>
                        <input type="number" name="price" id="quick_price" class="form-control" min="0" step="0.01" placeholder="Enter price (optional)">
                        <div class="invalid-feedback" data-error-for="price"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="quick_technology_id" class="form-label fw-semibold"><i class="bi bi-cpu me-2 text-muted"></i>Technology</label>
                        <select name="technology_id" id="quick_technology_id" class="form-select quick-bom-select quick-bom-creatable" data-placeholder="Search or create Technology"><option value=""></option>
                            @foreach($technologies as $technology)<option value="{{ $technology->id }}">{{ $technology->title }}</option>@endforeach
                        </select>
                        <div class="invalid-feedback" data-error-for="technology_id"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="quick_warranty_id" class="form-label fw-semibold"><i class="bi bi-shield-check me-2 text-muted"></i>Warranty</label>
                        <select name="warranty_id" id="quick_warranty_id" class="form-select quick-bom-select quick-bom-creatable" data-placeholder="Search or create Warranty"><option value=""></option>
                            @foreach($warranties as $warranty)<option value="{{ $warranty->id }}">{{ $warranty->title }}</option>@endforeach
                        </select>
                        <div class="invalid-feedback" data-error-for="warranty_id"></div>
                    </div>
                </div></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue" id="saveQuickBomBtn"><span class="spinner-border spinner-border-sm d-none me-1"></span><span class="button-text">Add BOM</span></button>
                </div>
            </form>
        </div>
    </div>
</div>
