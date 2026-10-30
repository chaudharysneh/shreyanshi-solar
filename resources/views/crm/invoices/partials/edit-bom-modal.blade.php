<!-- Edit BOM Modal -->
<div class="modal fade" id="invoiceEditBomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 640px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Edit BOM Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="invoiceEditBomForm" novalidate>
                    <input type="hidden" id="invoice_edit_bom_id">
                    {{-- Row 1: BOM Name | Make --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="invoice_edit_bom_name" placeholder="Enter BOM name" required>
                            <div class="invalid-feedback" id="invoice_edit_bom_name-error">Please enter BOM name</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Make</label>
                            <select class="form-select edit-bom-make-select" id="invoice_edit_bom_category_id">
                                <option value="">Select Make</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" data-name="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Row 2: Description | BOM Image --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="invoice_edit_bom_description" rows="3" placeholder="BOM Description" style="resize:none;"></textarea>
                            <label for="invoice_edit_bom_hsn_sac" class="form-label fw-semibold mt-3"><i class="fa-solid fa-hashtag crm-label-icon" aria-hidden="true"></i>HSN/SAC Code</label>
                            <input type="text" class="form-control" id="invoice_edit_bom_hsn_sac" placeholder="HSN/SAC Code">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold"><i class="bi bi-image crm-label-icon" aria-hidden="true"></i> BOM Image</label>
                            <input type="file" class="form-control" id="invoice_edit_bom_image" accept="image/jpeg,image/png,image/jpg" style="cursor:pointer;">
                            <div class="form-text text-muted">Accepted: JPG, PNG, JPEG &mdash; Max 5MB</div>
                            <div id="invoice_edit_bom_image_preview" class="mt-2" style="display:none;">
                                <button type="button" class="btn btn-sm btn-danger bom-image-remove" data-input="invoice_edit_bom_image" data-preview="invoice_edit_bom_image_preview" style="position:absolute; margin-left:115px; margin-top:-6px; width:24px; height:24px; padding:0; border-radius:50%;">&times;</button>
                                <img src="" alt="BOM Image Preview" id="invoice_edit_bom_image_thumb"
                                    style="max-height:80px; max-width:140px; border-radius:6px; border:1px solid #dee2e6; object-fit:contain; background:#f8f9fa; padding:3px;">
                            </div>
                        </div>
                    </div>
                    {{-- Row 3: Unit Price | Tax --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="invoice_edit_bom_price" placeholder="Enter unit price" required>
                            <div class="invalid-feedback" id="invoice_edit_bom_price-error">Please enter unit price</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tax</label>
                            <select class="form-select" id="invoice_edit_bom_tax_rate">
                                <option value="0" data-label="">No Tax</option>
                                @foreach ($bomTaxOptions as $taxOption)
                                    <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                        {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Save Options --}}
                    @include('crm.invoices.partials.bom-specification-fields')
                    <div class="p-3 bg-light rounded border border-info border-opacity-25">
                        <label class="form-label fw-semibold text-dark mb-2">Save Options</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="invoice_edit_bom_save_mode" id="invoice_edit_bom_mode_estimate" value="estimate" checked>
                            <label class="form-check-label text-secondary small" for="invoice_edit_bom_mode_estimate">
                                Update for this estimate only
                            </label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="invoice_edit_bom_save_mode" id="invoice_edit_bom_mode_master" value="master">
                            <label class="form-check-label text-secondary small" for="invoice_edit_bom_mode_master">
                                Also update master BOM record
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="invoiceSaveEditBomBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true" id="invoiceSaveEditBomSpinner"></span>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
