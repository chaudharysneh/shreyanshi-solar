@extends('layouts.app')

@section('page_title', 'Edit Invoice #' . $invoice->invoice_no)

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ crm_asset('css/estimates.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .bom-row-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1.45fr) minmax(145px, 1fr) minmax(90px, .65fr) minmax(120px, .8fr) minmax(130px, .9fr) minmax(145px, 1fr) 42px;
            gap: 12px;
            align-items: end;
        }

        .bom-row-grid > div,
        .bom-row-grid .d-flex {
            min-width: 0;
        }

        .bom-row-grid .form-label {
            font-size: 10px;
            line-height: 1.2;
            margin-bottom: 6px;
            white-space: nowrap;
        }

        .bom-row-grid .form-control,
        .bom-row-grid .form-select {
            min-height: 38px;
            font-size: 13px;
            font-weight: 500;
        }

        .bom-row-grid .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            font-size: 13px;
            font-weight: 500;
        }

        .bom-row-grid .select2-container--bootstrap-5 .select2-selection__rendered {
            font-size: 13px;
            font-weight: 500;
        }

        .bom-row-grid .quick-add-bom-row,
        .bom-row-grid .delete-bom-row {
            width: 42px;
            min-height: 38px;
            padding-left: 0;
            padding-right: 0;
        }

        .bom-row-grid .product-select,
        .bom-row-grid .select2-container {
            min-width: 0 !important;
            width: 100% !important;
        }

        @media (max-width: 1199.98px) {
            .bom-row-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .bom-row-grid .bom-action-cell {
                grid-column: span 2;
            }
        }
        #invoiceEditForm .d-flex:has(.is-invalid) ~ .invalid-feedback {
            display: block !important;
        }
    </style>
    @include('crm.invoices.partials.mobile-wizard', ['formId' => 'invoiceEditForm'])
@endpush

@section('content')
    @php
        $selectedProducts = old('products');
        if (!is_array($selectedProducts)) {
            $selectedProducts = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name ?? '[]', true);
        }
        if (!is_array($selectedProducts) || empty($selectedProducts)) {
            $selectedProducts = [['product_id' => '', 'category_name' => '', 'quantity' => 0]];
        }
        $invoiceTaxRows = collect($gstTaxes ?? [])->flatMap(function ($tax) {
            $name = (string) $tax->name;
            $upperName = strtoupper($name);
            $rate = (float) $tax->rate;

            if (str_contains($upperName, 'CGST') && str_contains($upperName, 'SGST')) {
                return [
                    ['label' => 'CGST', 'rate' => $rate / 2],
                    ['label' => 'SGST', 'rate' => $rate / 2],
                ];
            }

            if (str_contains($upperName, 'IGST')) {
                return [['label' => 'IGST', 'rate' => $rate]];
            }

            return [['label' => $name, 'rate' => $rate]];
        })->values();

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

    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Invoice</h1>
                        <p class="text-muted small mb-0">Invoice No: {{ $invoice->invoice_no }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('view', $invoice)
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" enctype="multipart/form-data" class="needs-validation ajax-invoice-form" novalidate
                    id="invoiceEditForm" action="/api/invoices/{{ $invoice->id }}">
                    @csrf
                    @method('PUT')

                    <div class="create-step-indicator d-md-none mt-2">
                        <div class="create-step-dot active" id="cdot-1"></div>
                        <div class="create-step-dot" id="cdot-2"></div>
                        <div class="create-step-dot" id="cdot-3"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 create-step-1 active-step">
                            <label class="form-label fw-semibold mb-1">Select Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2">
                                <select name="customer_id" id="select_customer"
                                    class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice->customer_id) == $customer->id)>
                                            {{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="customer_id-error">Please select a customer</div>
                        </div>

                        <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold">Invoice Name <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_name" id="invoice_name"
                                value="{{ old('invoice_name', $invoice->invoice_name) }}"
                                class="form-control @error('invoice_name') is-invalid @enderror"
                                placeholder="Enter invoice name" required>
                            <div class="invalid-feedback" id="invoice_name-error">Please enter invoice name</div>
                        </div>

                    <input type="hidden" name="currency_id" id="currency_id" value="{{ old('currency_id', $invoice->currency_id ?? $defaultCurrencyId) }}">

                    <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold">Invoice Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror"
                                required>
                                <option value="">Select Type</option>
                                <option value="residential" @selected(old('type', $invoice->type) == 'residential')>Residential</option>
                                <option value="commercial" @selected(old('type', $invoice->type) == 'commercial')>Commercial</option>
                                <option value="industrial" @selected(old('type', $invoice->type) == 'industrial')>Industrial</option>
                                <option value="common meter" @selected(old('type', $invoice->type) == 'common meter')>Common Meter</option>
                                <option value="ground mounted" @selected(old('type', $invoice->type) == 'ground mounted')>Ground Mounted</option>
                            </select>
                            <div class="invalid-feedback" id="type-error">Please select invoice type</div>
                        </div>

                        <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="0.01" name="quantity" id="quantity"
                                value="{{ old('quantity', $invoice->quantity) }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="Enter kW"
                                required>
                            <div class="invalid-feedback" id="quantity-error">Please enter valid quantity (kW)</div>
                        </div>

                        <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" name="price" id="price"
                                value="{{ old('price', $invoice->price) }}"
                                class="form-control @error('price') is-invalid @enderror" placeholder="Enter price"
                                required>
                            <div class="invalid-feedback" id="price-error">Please enter valid price</div>
                        </div>

                        <div class="col-12 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold">Solar Meter Charges <span
                                    class="text-danger">*</span></label>
                            <select name="solar_meter_charges" id="solar_meter_select"
                                class="form-select @error('solar_meter_charges') is-invalid @enderror" required>
                                <option value="">Select</option>
                                <option value="as_per_actual" @selected(old('solar_meter_charges', $invoice->solar_meter_charges) == 'as_per_actual')>As per Actual</option>
                                <option value="as_per_client_scope" @selected(old('solar_meter_charges', $invoice->solar_meter_charges) == 'as_per_client_scope')>As per client scope</option>
                                <option value="included" @selected(old('solar_meter_charges', $invoice->solar_meter_charges) == 'included')>Included</option>
                            </select>
                            <div class="invalid-feedback" id="solar_meter_charges-error">Please select solar meter charges
                            </div>
                        </div>

                        <div class="col-12 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label class="form-label fw-semibold">Quotation Template <span class="text-danger">*</span></label>
                            <select name="template_id" id="template_id"
                                class="form-select @error('template_id') is-invalid @enderror" required>
                                <option value="">Select Template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected(old('template_id', $invoice->template_id) == $template->id)>
                                        {{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="template_id-error">Please select quotation template</div>
                        </div>

                        <div class="col-12 create-step-1 active-step estimate-charges-date-group">
                            <div class="row g-3">
                                <div class="col-12 col-md-8 estimate-charges-col">
                                    <label class="form-label fw-semibold">Charges</label>
                                    <div class="form-control d-flex align-items-center bg-light"
                                        style="min-height: 38px; padding: 0.375rem 0.75rem;">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="solar_structure_charges_check"
                                                value="1" @checked((float) old('solar_structure_charges', $invoice->solar_structure_charges) > 0)>
                                            <label class="form-check-label small" for="solar_structure_charges_check">
                                                Solar Structure Charges
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4 d-none d-md-block estimate-created-date-col">
                                    <label class="form-label fw-semibold">Invoice Date</label>
                                    <input type="date" name="invoice_date" id="invoice_date"
                                        value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}"
                                        class="form-control @error('invoice_date') is-invalid @enderror">
                                    <div class="invalid-feedback" id="invoice_date-error">
                                        @error('invoice_date')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 estimate-structure-charges-col" id="structure-charges-input" style="display: none;">
                                    <label class="form-label fw-semibold small">Enter Structure Charges</label>
                                    <input type="number" min="0" step="1" name="solar_structure_charges"
                                        id="solar_structure_charges"
                                        value="{{ old('solar_structure_charges', $invoice->solar_structure_charges) }}"
                                        class="form-control @error('solar_structure_charges') is-invalid @enderror"
                                        placeholder="0.00">
                                    <div class="invalid-feedback" id="solar_structure_charges-error">
                                        @error('solar_structure_charges')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 create-step-2">
                            <label class="form-label fw-semibold">BOM (Bill Of Material)</label>
                            <div class="bom-section bg-light rounded-3 p-3 border">
                                <div id="bomContainer">
                                    @foreach ($selectedProducts as $index => $selectedProduct)
                                        @php
                                            $selectedBom = $bomProducts->firstWhere('id', $selectedProduct['product_id'] ?? null);
                                            $selectedUnitPrice = $selectedProduct['price'] ?? ($selectedBom->price ?? 0);
                                            $selectedQuantity = $selectedProduct['quantity'] ?? 0;
                                            $selectedTaxRate = (float) ($selectedProduct['tax_rate'] ?? 0);
                                        @endphp
                                        <div class="bom-row mb-3 p-3 bg-white border rounded shadow-sm">
                                            <div class="bom-row-grid">
                                                <div>
                                                    <label class="form-label small fw-semibold">BOM <span
                                                            class="text-danger">*</span></label>
                                                    <div class="d-flex align-items-start gap-2">
                                                        <select name="service[]" class="form-select product-select" required>
                                                            <option value="">Select BOM</option>
                                                            @foreach ($bomProducts as $bom)
                                                                <option value="{{ $bom->id }}"
                                                                    data-name="{{ $bom->product_name }}"
                                                                    data-desc="{{ $bom->description ?? '' }}"
                                                                    data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'
                                                                    data-price="{{ $bom->price ?? 0 }}"
                                                                    data-meter="{{ $bom->meter ?? '' }}"
                                                                    data-nos="{{ $bom->nos ?? '' }}"
                                                                    @selected((string) ($selectedProduct['product_id'] ?? '') === (string) $bom->id)>
                                                                    {{ $bom->product_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-add-bom-row" data-bs-toggle="modal" data-bs-target="#quickAddBomModal" title="Add New BOM">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold">Make <span class="text-danger">*</span></label>
                                                    <select name="product_make[]" class="form-select product-make"
                                                        data-selected="{{ $selectedProduct['category_name'] ?? '' }}"
                                                        @disabled(empty($selectedProduct['product_id']))>
                                                        <option value="">Select Make</option>
                                                    </select>
                                                    <div class="invalid-feedback bom-make-error">Please select make.</div>
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold product-qty-label">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_qty[]"
                                                        value="{{ (float) $selectedQuantity > 0 ? $selectedQuantity : '' }}"
                                                        class="form-control" placeholder="Add Quantity">
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_price[]"
                                                        value="{{ round((float) $selectedUnitPrice) }}"
                                                        class="form-control product-price" placeholder="0">
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold">Tax</label>
                                                    <select name="product_tax_rate[]" class="form-select product-tax-rate">
                                                        <option value="0" data-label="" @selected($selectedTaxRate <= 0)>No Tax</option>
                                                        @foreach ($bomTaxOptions as $taxOption)
                                                            <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}" @selected(abs($selectedTaxRate - (float) $taxOption['rate']) < 0.001)>
                                                                {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Total Amount</label>
                                                    <input type="number" min="0" step="1"
                                                        value="{{ round((float) $selectedQuantity * (float) $selectedUnitPrice) }}"
                                                        class="form-control product-total" placeholder="0" readonly>
                                                </div>
                                                <div class="bom-action-cell">
                                                    <button type="button" class="btn btn-outline-danger w-100 delete-bom-row"
                                                        style="display: {{ $index === 0 ? 'none' : 'block' }};">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-dark-blue btn-sm" id="add_more_bom">
                                    <i class="bi bi-plus-circle me-2"></i>Add More BOM
                                </button>
                                <div class="estimate-bom-instruction" id="products-error" style="display:none;" role="status">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i><span class="products-error-text">Please select at least one BOM.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 create-step-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Design File</label>
                                <input type="file" name="attach_file" id="attach_file"
                                    class="form-control @error('attach_file') is-invalid @enderror"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <div class="invalid-feedback" id="attach_file-error">
                                    @error('attach_file')
                                        {{ $message }}
                                    @enderror
                                </div>

                                @if ($invoice->attach_file)
                                    <div class="mt-2 small">
                                        <a href="{{ Storage::url('invoices/' . $invoice->attach_file) }}" target="_blank"
                                            class="text-primary fw-medium"><i class="bi bi-file-earmark-check me-1"></i>View
                                            existing file</a>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="form-label fw-semibold">Comment</label>
                                <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="5"
                                    placeholder="Add any comments...">{{ old('comment', $invoice->comment) }}</textarea>
                                <div class="invalid-feedback" id="comment-error">
                                    @error('comment')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 create-step-3">
                            <div class="totals-card rounded-3 h-100 d-flex flex-column justify-content-center">
                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal:</span>
                                    <span id="subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="apply_gst" @checked((float) old('gst', $invoice->gst) > 0)>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="small fw-semibold">Apply GST</span>
                                    </div>
                                </div>

                                <div id="gst_fields_box">
                                    <div class="totals-row">
                                        <span class="small text-muted">Select BOM tax to apply GST.</span>
                                        <span class="small">0.00</span>
                                    </div>
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Discount:</span>
                                    <input type="number" name="discount" id="discount"
                                        value="{{ old('discount', $invoice->discount) }}" step="1" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subsidy:</span>
                                    <input type="number" name="subsidy_amount" id="subsidy_amount"
                                        value="{{ old('subsidy_amount', $invoice->subsidy_amount) }}"
                                        step="1" class="input-small">
                                </div>

                                <hr class="my-2">

                                <div class="totals-row total-row mb-0">
                                    <span class="h6 mb-0 fw-bold">Total Payable:</span>
                                    <span id="final_total_display" class="h5 mb-0 fw-bold">0.00</span>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="total" id="subtotal"
                            value="{{ old('total', $invoice->total) }}">
                        <input type="hidden" name="final_total" id="final_total"
                            value="{{ old('amount', $invoice->amount) }}">
                        <input type="hidden" name="gst" id="gst" value="{{ old('gst', $invoice->gst) }}">
                        <input type="hidden" name="status" id="status"
                            value="{{ old('status', $invoice->status) }}">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex justify-content-between gap-2 form-actions">
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark-blue d-none d-md-inline-block">Cancel</a>
                        <button type="button" class="btn btn-outline-dark-blue mobile-create-wizard-btn create-prev-btn" style="display: none !important;">Back</button>
                        <div class="d-flex ms-auto gap-2">
                            <button type="button" class="btn btn-dark-blue mobile-create-wizard-btn create-next-btn">Next</button>
                            <button type="submit" class="btn btn-dark-blue create-submit-btn" id="submitBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                    id="btnSpinner"></span>
                                <span id="btnText">Update</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add BOM Modal -->
    <div class="modal fade" id="quickAddBomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white">Add New BOM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="quickAddBomForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_bom_name" required>
                            <div class="invalid-feedback" id="quick_bom_name-error">Please enter BOM name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Make <span class="text-danger">*</span></label>
                            <select class="form-select quick-bom-make-select" id="quick_bom_category_id" required>
                                <option value="">Select Make</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" data-name="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_bom_category_id-error">Please select make</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="quick_bom_price" required>
                            <div class="invalid-feedback" id="quick_bom_price-error">Please enter unit price</div>
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white">Add New Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addCustomerQuickForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_name" required>
                            <div class="invalid-feedback">Please enter customer name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_number" required>
                            <div class="invalid-feedback">Please enter mobile number</div>
                        </div>
                        <div class="mb-3">
                            <a href="#" class="small text-decoration-none" onclick="$('#quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="quick_address_container">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="quick_customer_address" rows="2" placeholder="Address"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="saveQuickCustomerBtn">Save Customer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        @php
            $templateComments = ($templates ?? collect())->mapWithKeys(function ($template) {
                $formData = is_array($template->form_data) ? $template->form_data : (json_decode($template->form_data ?? '[]', true) ?: []);
                $comment = is_array($formData['estimate_comment'] ?? null) ? $formData['estimate_comment'] : [];
                return [
                    (string) $template->id => [
                        'active' => (int) ($comment['active'] ?? 0),
                        'content' => (string) ($comment['content'] ?? ''),
                    ],
                ];
            });
        @endphp
        window.invoiceTemplateComments = @json($templateComments);
        window.subsidiesData = @json($subsidies ?? []);
        window.invoiceTaxes = @json($invoiceTaxRows);
        window.invoiceBomQuickAddConfig = {
            storeUrl: @json(route('api.bom-products.store')),
            makeStoreUrl: @json(route('api.make.store'))
        };
        window.documentFormConfig = {
            formSelector: '.ajax-invoice-form',
            eventNs: 'invoice',
            nameField: 'invoice_name',
            nameErrorId: 'invoice_name-error',
            nameLabel: 'invoice name',
            namePrefix: 'INV-',
            defaultRedirect: '/invoices',
            templateCommentsKey: 'invoiceTemplateComments',
            bomQuickAddConfigKey: 'invoiceBomQuickAddConfig',
            requireCurrency: false,
            typeErrorMessage: 'Please select invoice type',
            bomPrereqMessage: 'Please fill required invoice details before adding BOM',
            saveSuccessMessage: 'Invoice saved successfully.',
            saveErrorMessage: 'Something went wrong while submitting the invoice.',
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/estimates.js') }}"></script>
    <script>
        $(document).ready(function() {
            function initSelect2(context = document) {
                $(context).find('#select_customer, #template_id, .product-select, .product-make').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
            initSelect2();

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && $(node).hasClass('bom-row')) {
                            $(node).find('.select2-container').remove();
                            $(node).find('.product-select, .product-make').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
                            $(node).find('option').removeAttr('data-select2-id');
                            initSelect2(node);
                        }
                    });
                });
            });
            const bomContainer = document.getElementById('bomContainer');
            if (bomContainer) observer.observe(bomContainer, { childList: true });

            $('#saveQuickCustomerBtn').click(function() {
                let name = $('#quick_customer_name').val().trim();
                let number = $('#quick_customer_number').val().trim();
                let address = $('#quick_customer_address').val().trim();

                $('#quick_customer_name, #quick_customer_number').removeClass('is-invalid');

                if (!name || !number) {
                    if(!name) $('#quick_customer_name').addClass('is-invalid');
                    if(!number) $('#quick_customer_number').addClass('is-invalid');
                    return;
                }

                if (!address) {
                    address = 'Address';
                }

                let btn = $(this);
                let originalText = btn.data('original-text') || btn.html();
                btn.data('original-text', originalText);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: '/api/customers',
                    type: 'POST',
                    data: {
                        name: name,
                        phone: number,
                        address: address,
                        status: 'active',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success && res.data) {
                            let newOption = new Option(res.data.name, res.data.id, true, true);
                            $('#select_customer').append(newOption).trigger('change');
                            $('#addCustomerModal').modal('hide');
                            $('#addCustomerQuickForm')[0].reset();
                            if (!window.customerSuccessToastShown) {
                                window.customerSuccessToastShown = true;
                                if (window.showAlert) window.showAlert('success', 'Customer added successfully');
                                setTimeout(() => window.customerSuccessToastShown = false, 1000);
                            }
                        }
                    },
                    error: function(xhr) {
                        if (window.showAlert) window.showAlert('error', xhr.responseJSON?.message || 'Failed to add customer');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            $(document).on('select2:select', '.product-select', function (e) {
                $(this).trigger('change');
            });
        });
    </script>
    @include('crm.invoices.partials.mobile-wizard-script', ['formId' => '#invoiceEditForm'])
@endpush
