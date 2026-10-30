@php
    $estimatePriceMode = $estimatePriceMode ?? (\App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom');
    $bomTaxOptions = collect($gstTaxes ?? [])->map(function ($tax) {
        $taxName = strtoupper((string) $tax->name);
        $label = (string) $tax->name;

        if (str_contains($taxName, 'CGST') && str_contains($taxName, 'SGST')) {
            $label = 'CGST + SGST';
        } elseif (str_contains($taxName, 'IGST')) {
            $label = 'IGST';
        }

        return ['label' => $label, 'rate' => (float) $tax->rate];
    })->filter(function ($taxOption) {
        return (float) ($taxOption['rate'] ?? 0) > 0;
    })->values();
@endphp
<div class="modal fade {{ $estimatePriceMode === 'base' ? 'quick-estimate-base-price-mode' : '' }}" id="quickEstimateModal" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <form id="quickEstimateForm" novalidate class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-white"><i class="bi bi-lightning-charge-fill me-2 text-white" aria-hidden="true"></i>Quick Estimate</h5>
                    <p class="small text-white-50 mb-0">Create a basic estimate with default/static settings.</p>
                </div>
                <div class="d-flex align-items-start gap-3 ms-auto">
                    <div class="quick-price-mode-card">
                        <span class="quick-price-mode-title">Pricing Method</span>
                        <div class="quick-price-mode-options" role="group" aria-label="Quick estimate pricing method">
                            <button type="button" class="quick-price-mode-option" data-quick-price-mode-option="base">
                                <i class="bi bi-cash-stack" aria-hidden="true"></i>Base Price
                            </button>
                            <button type="button" class="quick-price-mode-option" data-quick-price-mode-option="bom">
                                <i class="bi bi-boxes" aria-hidden="true"></i>BOM Price
                            </button>
                        </div>
                        <select name="price_mode" id="quick_estimate_price_mode" class="form-select form-select-sm quick-estimate-price-mode-selector quick-price-mode-select" aria-label="Pricing Method">
                            <option value="bom" @selected($estimatePriceMode === 'bom')>Show BOM Price only</option>
                            <option value="base" @selected($estimatePriceMode === 'base')>Show Base Price only</option>
                        </select>
                    </div>
                    <button type="button" class="btn-close btn-close-white mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            
            <div class="modal-body p-4">
                    <!-- Mobile Step Indicator -->
                    <div class="quick-step-indicator d-md-none">
                        <div class="quick-step-dot active" id="qdot-1"></div>
                        <div class="quick-step-dot" id="qdot-2"></div>
                        <div class="quick-step-dot" id="qdot-3"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100 quick-customer-select-wrap" style="min-width: 0;">
                                    <select class="form-select" name="customer_id" id="quick_estimate_customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" id="quickEstimateAddCustomerBtn" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="quick_customer_id-error">Please select a customer.</div>
                        </div>
                        <div class="col-12 col-md-8 quick-step-1 active-step">
                            <div class="row g-2">
                                <div class="col-6 quick-step-field-col">
                                    <label class="form-label fw-semibold">Estimate Name</label>
                                    <input type="text" class="form-control" name="estimate_name" id="quick_estimate_name" placeholder="Auto from customer">
                                </div>
                                <div class="col-6 quick-type-select-wrap quick-step-field-col">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" id="quick_estimate_type" required>
                                        <option value="" selected>Select Type</option>
                                        <option value="residential">Residential</option>
                                        <option value="commercial">Commercial</option>
                                        <option value="industrial">Industrial</option>
                                        <option value="common meter">Common Meter</option>
                                        <option value="ground mounted">Ground Mounted</option>
                                    </select>
                                    <div class="invalid-feedback" id="quick_type-error">Please select type.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-8 quick-step-1 active-step">
                            <div class="row g-2">
                                <div class="col-6 quick-step-field-col">
                                    <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                                    <input type="number" min="1" step="1" class="form-control" name="quantity" id="quick_quantity" placeholder="Enter kW" required>
                                    <div class="invalid-feedback" id="quick_quantity-error">Please enter quantity.</div>
                                </div>
                                <div class="col-6 quick-step-field-col quick-base-price-col {{ $estimatePriceMode === 'bom' ? 'd-none' : '' }}">
                                    <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                                    <input type="number" min="0" step="1" class="form-control" name="price" id="quick_price" value="{{ $estimatePriceMode === 'bom' ? 0 : '' }}" placeholder="Enter price" @required($estimatePriceMode === 'base')>
                                    <div class="invalid-feedback" id="quick_price-error">Please enter price.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 quick-step-1 active-step quick-template-select-wrap" id="quick_template_wrapper">
                            <label class="form-label fw-semibold">Quotation Template <span class="text-danger">*</span></label>
                            <select class="form-select" name="template_id" id="quick_template_id" required>
                                <option value="">Select Template</option>
                                @foreach ($templates ?? [] as $template)
                                    <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_template_id-error">Please select template.</div>
                        </div>
                            <div class="col-12 col-md-4 quick-step-1 active-step quick-global-tax-col {{ $estimatePriceMode === 'base' ? '' : 'd-none' }}">
                                <label class="form-label fw-semibold">Tax Rate (Global)</label>
                                <select name="global_tax_rate" id="quick_global_tax_rate" class="form-select">
                                    <option value="0" data-label="No Tax">No Tax</option>
                                    @foreach ($bomTaxOptions as $taxOption)
                                        <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">{{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        <div class="col-12 quick-step-2">
                            <label class="form-label fw-semibold">BOM Details <span class="text-danger">*</span></label>
                            <div class="border rounded-3 bg-light p-3">
                                <div id="quickBomRows" class="d-flex flex-column gap-2">
                                    <div class="quick-bom-row bg-white border rounded-3 p-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 {{ $estimatePriceMode === 'base' ? 'col-md-5' : 'col-md-3' }} quick-bom-select-col">
                                                <label class="form-label small fw-semibold">BOM <span class="text-danger">*</span></label>
                                                <div class="d-flex align-items-start gap-2">
                                                    <select class="form-select quick-bom-select" name="quick_bom_id[]">
                                                        <option value="">Select BOM</option>
                                                        @foreach ($bomProducts ?? [] as $bom)
                                                            <option value="{{ $bom->id }}"
                                                                data-name="{{ $bom->product_name }}"
                                                                data-price="{{ $bom->price ?? 0 }}"
                                                                data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'
                                                                data-tax-rate="{{ $bom->tax_rate ?? 0 }}">
                                                                {{ $bom->product_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary edit-bom-link flex-shrink-0 d-none" style="width: 38px; height: 38px; padding: 0;" title="Edit selected BOM">
                                                        <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-estimate-add-bom-btn" style="width: 38px; height: 38px; padding: 0;" title="Add New BOM">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12 {{ $estimatePriceMode === 'base' ? 'col-md-4' : 'col-md-2' }} quick-bom-make-col">
                                                <label class="form-label small fw-semibold">Make</label>
                                                <select class="form-select quick-bom-make-select" name="quick_bom_make[]" disabled>
                                                    <option value="">Select Make</option>
                                                </select>
                                            </div>
                                            <div class="col-6 {{ $estimatePriceMode === 'base' ? 'col-md-2' : 'col-md-1' }} quick-bom-qty-col">
                                                <label class="form-label small fw-semibold">Qty <span class="text-danger">*</span></label>
                                                <input type="number" min="1" step="1" class="form-control quick-bom-qty" name="quick_bom_qty[]" value="1">
                                            </div>
                                            <div class="col-6 col-md-2 quick-bom-money-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                <label class="form-label small fw-semibold">Unit Price</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-price" name="quick_bom_price[]" value="0">
                                            </div>
                                            <div class="col-12 col-md-1 quick-bom-money-col quick-bom-amount-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                <label class="form-label small fw-semibold">Amount</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-amount" value="0" readonly>
                                            </div>
                                            <div class="col-12 col-md-2 quick-bom-tax-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                <label class="form-label small fw-semibold">Tax</label>
                                                <select name="quick_bom_tax_rate[]" class="form-select quick-bom-tax-rate">
                                                    <option value="0" data-label="">No Tax</option>
                                                    @foreach ($bomTaxOptions as $taxOption)
                                                        <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                                            {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100 quick-remove-bom-row" style="display:none;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-dark-blue btn-sm mt-3" id="quickAddBomRow">
                                    <i class="bi bi-plus-circle me-1"></i>Add More BOM
                                </button>
                                <div class="quick-bom-instruction" id="quick_bom_id-error" style="display:none;" role="status">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Please select at least one BOM.
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 quick-step-3 quick-totals-col order-md-2">
                            <div class="totals-card quick-totals-card rounded-3">
                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal:</span>
                                    <span id="quick_subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="quick_apply_gst" checked>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="small fw-semibold">Apply GST</span>
                                    </div>
                                </div>

                                <div id="quick_gst_fields_box">
                                    <div class="totals-row">
                                        <span class="small text-muted">Select BOM tax to apply GST.</span>
                                        <span class="small">0.00</span>
                                    </div>
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal (Tax Incl.):</span>
                                    <span id="quick_subtotal_tax_incl_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Discount:</span>
                                    <input type="number" id="quick_discount" value="0" step="1" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subsidy:</span>
                                    <input type="number" id="quick_subsidy_amount" value="0" step="1" class="input-small">
                                </div>

                                <hr class="my-2">

                                <div class="totals-row total-row mb-0">
                                    <span class="h6 mb-0 fw-bold">Total Payable:</span>
                                    <span id="quick_final_total_display" class="h5 mb-0 fw-bold">0.00</span>
                                </div>
                            </div>
                            <input type="hidden" id="quick_subtotal" value="0">
                            <input type="hidden" id="quick_subtotal_tax_incl" value="0">
                            <input type="hidden" id="quick_final_total" value="0">
                            <input type="hidden" id="quick_gst" value="0">
                        </div>
                        <div class="col-12 col-md-6 quick-step-3 quick-comment-col order-md-1">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea class="form-control" name="comment" id="quick_estimate_comment" rows="2" placeholder="Optional comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top rounded-bottom-4 d-flex justify-content-between pb-3">
                    <button type="button" class="btn btn-outline-dark-blue d-none d-md-block" data-bs-dismiss="modal">Cancel</button>
                    
                    <!-- Mobile Wizard Buttons -->
                    <button type="button" class="btn btn-outline-dark-blue mobile-wizard-btn quick-prev-btn" style="display: none !important;">Back</button>
                    
                    <div class="d-flex ms-auto">
                        <button type="button" class="btn btn-dark-blue mobile-wizard-btn quick-next-btn">Next</button>
                        <button type="submit" class="btn btn-dark-blue quick-submit-btn" id="quickEstimateSubmitBtn">
                            <span class="submit-label">Create Estimate</span>
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>

@include('crm.estimates.partials.quick-estimate-add-customer-modal')

@push('styles')
    @include('crm.estimates.partials.quick-estimate-wizard-styles')
@endpush

@push('scripts')
    @include('crm.estimates.partials.quick-estimate-wizard-script')
@endpush

    <div class="modal fade" id="quickAddBomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 640px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add New BOM</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="quickAddBomForm" novalidate>
                    {{-- Row 1: BOM Name | Make --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_bom_name" required>
                            <div class="invalid-feedback" id="quick_bom_name-error">Please enter BOM name</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Make</label>
                            <select class="form-select quick-bom-make-select" id="quick_bom_category_id">
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
                            <textarea class="form-control" id="quick_bom_description" rows="3" placeholder="BOM Description" style="resize:none;"></textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold"><i class="bi bi-image crm-label-icon" aria-hidden="true"></i> BOM Image</label>
                            <input type="file" class="form-control" id="quick_bom_image" accept="image/jpeg,image/png,image/jpg" style="cursor:pointer;">
                            <div class="form-text text-muted">Accepted: JPG, PNG, JPEG &mdash; Max 5MB</div>
                            <div id="quick_bom_image_preview" class="mt-2" style="display:none;">
                                <button type="button" class="btn btn-sm btn-danger bom-image-remove" data-input="quick_bom_image" data-preview="quick_bom_image_preview" style="position:absolute; margin-left:115px; margin-top:-6px; width:24px; height:24px; padding:0; border-radius:50%;">&times;</button>
                                <img src="" alt="BOM Image Preview" id="quick_bom_image_thumb"
                                    style="max-height:80px; max-width:140px; border-radius:6px; border:1px solid #dee2e6; object-fit:contain; background:#f8f9fa; padding:3px;">
                            </div>
                        </div>
                    </div>
                    {{-- Row 3: Unit Price | Tax --}}
                    <div class="row g-3 mb-0">
                        <div class="col-6">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="quick_bom_price" required>
                            <div class="invalid-feedback" id="quick_bom_price-error">Please enter unit price</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tax</label>
                            <select class="form-select" id="quick_bom_tax_rate">
                                <option value="0" data-label="">No Tax</option>
                                @foreach ($bomTaxOptions as $taxOption)
                                    <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                        {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        @foreach ([
                            'technology_id' => ['Technology', \App\Models\Technology::orderBy('title')->get()],
                            'warranty_id' => ['Warranty', \App\Models\Warranty::orderBy('title')->get()]
                        ] as $field => [$label, $options])
                            <div class="col-6">
                                <label class="form-label fw-semibold">{{ $label }}</label>
                                <select class="form-select" id="quick_bom_{{ $field }}">
                                    <option value="">Select {{ $label }}</option>
                                    @foreach ($options as $option)<option value="{{ $option->id }}">{{ $option->title }}</option>@endforeach
                                </select>
                            </div>
                        @endforeach
                        @foreach (['height' => 'Height', 'fitting_material' => 'Fitting Material', 'fitting_type' => 'Fitting Type', 'thickness' => 'Thickness', 'size_of_pipe' => 'Pipe Size', 'capacity' => 'Capacity', 'meter' => 'Meter', 'nos' => 'Nos'] as $field => $label)
                            <div class="col-6"><label class="form-label fw-semibold">{{ $label }}</label><input type="text" class="form-control" id="quick_bom_{{ $field }}"></div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveQuickBomBtn">Save BOM</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit BOM Modal -->
<div class="modal fade" id="editBomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 640px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Edit BOM Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editBomForm" novalidate>
                    <input type="hidden" id="edit_bom_id">
                    {{-- Row 1: BOM Name | Make --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_bom_name" placeholder="Enter BOM name" required>
                            <div class="invalid-feedback" id="edit_bom_name-error">Please enter BOM name</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Make</label>
                            <select class="form-select edit-bom-make-select" id="edit_bom_category_id">
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
                            <textarea class="form-control" id="edit_bom_description" rows="3" placeholder="BOM Description" style="resize:none;"></textarea>
                            <label for="edit_bom_hsn_sac" class="form-label fw-semibold mt-3"><i class="fa-solid fa-hashtag crm-label-icon" aria-hidden="true"></i>HSN/SAC Code</label>
                            <input type="text" class="form-control" id="edit_bom_hsn_sac" placeholder="HSN/SAC Code">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold"><i class="bi bi-image crm-label-icon" aria-hidden="true"></i> BOM Image</label>
                            <input type="file" class="form-control" id="edit_bom_image" accept="image/jpeg,image/png,image/jpg" style="cursor:pointer;">
                            <div class="form-text text-muted">Accepted: JPG, PNG, JPEG &mdash; Max 5MB</div>
                            <div id="edit_bom_image_preview" class="mt-2" style="display:none;">
                                <button type="button" class="btn btn-sm btn-danger bom-image-remove" data-input="edit_bom_image" data-preview="edit_bom_image_preview" style="position:absolute; margin-left:115px; margin-top:-6px; width:24px; height:24px; padding:0; border-radius:50%;">&times;</button>
                                <img src="" alt="BOM Image Preview" id="edit_bom_image_thumb"
                                    style="max-height:80px; max-width:140px; border-radius:6px; border:1px solid #dee2e6; object-fit:contain; background:#f8f9fa; padding:3px;">
                            </div>
                        </div>
                    </div>
                    {{-- Row 3: Unit Price | Tax --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="edit_bom_price" placeholder="Enter unit price" required>
                            <div class="invalid-feedback" id="edit_bom_price-error">Please enter unit price</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tax</label>
                            <select class="form-select" id="edit_bom_tax_rate">
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
                    @include('crm.estimates.partials.bom-specification-fields')
                    <div class="p-3 bg-light rounded border border-info border-opacity-25">
                        <label class="form-label fw-semibold text-dark mb-2">Save Options</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="edit_bom_save_mode" id="edit_bom_mode_estimate" value="estimate" checked>
                            <label class="form-check-label text-secondary small" for="edit_bom_mode_estimate">
                                Update for this estimate only
                            </label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="edit_bom_save_mode" id="edit_bom_mode_master" value="master">
                            <label class="form-check-label text-secondary small" for="edit_bom_mode_master">
                                Also update master BOM record
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveEditBomBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true" id="saveEditBomSpinner"></span>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
