@extends('layouts.app')

@section('page_title', 'Invoices - Edit')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ crm_asset('css/invoice-form.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        #bomContainer .bom-qty-unit { font-size: 10px; font-weight: 400; }
        .bom-row-grid {
            display: grid;
            grid-template-columns: minmax(200px, 1.35fr) minmax(135px, 1fr) minmax(110px, .75fr) minmax(85px, .65fr) minmax(110px, .8fr) minmax(125px, .9fr) minmax(125px, .9fr) 42px;
            gap: 12px;
            align-items: end;
        }

        .bom-row-grid > div,
        .bom-row-grid .d-flex {
            min-width: 0;
        }

        #bomContainer .bom-row-grid label.form-label {
            font-size: 14px !important;
            font-weight: 500 !important;
            line-height: 1.2;
            margin-bottom: 3px !important;
            white-space: nowrap;
        }

        #bomContainer .bom-row-grid label.form-label i,
        #bomContainer .bom-row-grid label.form-label .crm-label-icon {
            display: inline-block !important;
            font-size: 10px !important;
            width: 12px;
            margin-right: 4px;
            color: #64748b;
        }

        .bom-row-grid .form-control,
        .bom-row-grid .form-select {
            min-height: 38px;
            font-size: 14px;
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

        .select2-dropdown,
        .select2-container--open {
            z-index: 99999 !important;
        }

        #invoiceEditForm .d-flex:has(.is-invalid) ~ .invalid-feedback {
            display: block !important;
        }

        .estimate-price-mode-card {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            border: 1px solid #dbe5f1;
            border-radius: 8px;
            background: #f8fbff;
            padding: 5px 6px 5px 10px;
        }

        .estimate-price-mode-title {
            color: #0f172a;
            font-size: 11px;
            white-space: nowrap;
            margin-right: 2px;
        }

        .estimate-price-mode-options {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            border-radius: 10px;
            background: #eef4fb;
        }

        .estimate-price-mode-option {
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #475569;
            min-height: 28px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .estimate-price-mode-option:hover {
            color: #0b376d;
            background: rgba(255, 255, 255, .72);
        }

        .estimate-price-mode-option.active {
            color: #ffffff;
            background: linear-gradient(135deg, #0b376d 0%, #0d6efd 100%);
            box-shadow: 0 4px 10px rgba(13, 110, 253, .18);
        }

        .estimate-price-mode-option i {
            font-size: 11px;
        }

        .estimate-price-mode-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        @media (max-width: 1199.98px) {
            .bom-row-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .bom-row-grid .bom-action-cell {
                grid-column: span 2;
            }
        }

        /* Responsive Multi-Step Logic */
        @media (max-width: 767.98px) {
            #invoiceEditForm .create-step-1,
            #invoiceEditForm .create-step-2,
            #invoiceEditForm .create-step-3 {
                display: none !important;
            }
            #invoiceEditForm .active-step {
                display: block !important;
            }
            #invoiceEditForm label.form-label {
                display: flex;
                align-items: center;
                flex-wrap: nowrap;
                white-space: nowrap;
                width: 100%;
                min-height: auto;
                margin-bottom: 0.35rem;
                gap: 0.35rem;
                line-height: 1.2;
            }
            #invoiceEditForm .crm-label-with-icon {
                flex-wrap: nowrap;
            }
            #invoiceEditForm .crm-label-icon,
            #invoiceEditForm label.form-label .text-danger {
                flex-shrink: 0;
            }
            #invoiceEditForm .estimate-template-label-group {
                min-width: 0;
                flex: 0 1 auto;
            }
            #edit_template_wrapper label.form-label {
                justify-content: flex-start;
            }
            #invoiceEditForm .row.g-3 > [class*="col-"] {
                min-width: 0;
            }
            #invoiceEditForm .active-step > .row {
                display: flex !important;
                flex-wrap: wrap;
            }
            #invoiceEditForm .estimate-charges-date-group > .row {
                min-height: 0 !important;
            }
            #invoiceEditForm > .row.g-3 {
                min-height: 420px;
            }
            #invoiceEditForm .form-actions .mobile-create-wizard-btn {
                width: auto !important;
            }
            .bom-row-grid > div:nth-child(1),
            .bom-row-grid > div:nth-child(2) {
                grid-column: span 2;
            }

            .create-step-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 5px;
                margin-bottom: 10px;
                padding: 0 10px;
            }
            .create-step-dot {
                height: 8px;
                width: 100%;
                background: #e9ecef;
                border-radius: 10px;
                transition: 0.3s;
            }
            .create-step-dot.active {
                background: #121a33;
            }
        }
        @media (min-width: 768px) {
            .create-step-indicator {
                display: none !important;
            }
            .mobile-create-wizard-btn {
                display: none !important;
            }
        }
        #bomContainer .bom-row-grid {
            grid-template-columns: repeat(3, minmax(0, 1.3fr)) minmax(0, 1fr) minmax(190px, 1.7fr) minmax(0, 1fr) !important;
            align-items: start;
            grid-template-rows: min-content 1fr;
            gap: 4px 12px;
        }
        #bomContainer .bom-row-grid > div:nth-child(1) { grid-column: 1 / 4; grid-row: 1 / 3; }
        #bomContainer .bom-row-grid > div:nth-child(2) { grid-column: 4 / 7; grid-row: 1; }
        #bomContainer .bom-row-grid > div:nth-child(3) { grid-column: 7 / 9; grid-row: 1; }
        #bomContainer .bom-row-grid > div:nth-child(4) { grid-column: 4; grid-row: 2; }
        #bomContainer .bom-row-grid > div:nth-child(5) { grid-column: 5; grid-row: 2; }
        #bomContainer .bom-row-grid > div:nth-child(6) { grid-column: 6 / 8; grid-row: 2; }
        #bomContainer .bom-row-grid > div:nth-child(7) { grid-column: 8; grid-row: 2; }
        #bomContainer .bom-row-grid > div:nth-child(8) { grid-column: 9; grid-row: 1 / 3; }
        .bom-section { margin-inline: -8px; }
        @media (min-width: 768px) {
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(2) { display:none; }
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(3) { grid-column: 4 / 7; grid-row: 1; }
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(4) { grid-column: 7 / 9; grid-row: 1; }
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(5) { grid-column: 4 / 6; grid-row: 2; }
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(6) { grid-column: 6 / 8; grid-row: 2; }
            #bomContainer .bom-without-make .bom-row-grid > div:nth-child(7) { grid-column: 8; grid-row: 2; }
        }
        #bomContainer { padding-top: 14px; padding-right: 14px !important; }
        #bomContainer .bom-row {
            position: relative;
            overflow: visible;
            padding: 6px !important;
        }
        #bomContainer .bom-row-grid input.form-control,
        #bomContainer .bom-row-grid .form-select,
        #bomContainer .bom-row-grid .select2-selection {
            min-height: 28px !important;
            height: 28px !important;
            padding-top: 3px !important;
            padding-bottom: 3px !important;
            font-size: 13px !important;
            line-height: 20px !important;
        }
        #bomContainer .bom-row-grid .select2-selection__rendered {
            font-size: 13px !important;
            line-height: 20px !important;
        }
        #bomContainer .bom-row-grid .product-description {
            min-height: 56px !important;
            font-size: 13px !important;
            padding: 3px 8px;
            line-height: 16px !important;
        }
        #bomContainer .bom-row-grid .edit-bom-link,
        #bomContainer .bom-row-grid .quick-add-bom-row {
            width: 28px !important;
            height: 28px !important;
            min-height: 28px !important;
            padding: 0 !important;
            font-size: 13px !important;
        }
        #bomContainer .bom-row-grid > .bom-action-cell {
            position: absolute;
            top: -12.5px;
            right: -12.5px;
            width: 25px;
            z-index: 1;
        }
        #bomContainer .bom-action-cell .delete-bom-row {
            width: 25px;
            height: 25px;
            min-height: 25px;
            font-size: 11px;
            line-height: 23px;
            padding: 0;
            border-radius: 50%;
            background-color: var(--bs-body-bg, #fff);
            box-shadow: 0 2px 5px rgba(0, 0, 0, .12);
        }
        #bomContainer .bom-action-cell .delete-bom-row i {
            font-size: 11px;
        }
        #bomContainer .bom-action-cell .delete-bom-row:hover {
            background-color: var(--bs-danger, #dc3545);
        }
        #bomContainer .select2-selection__rendered { white-space: normal; overflow-wrap: anywhere; }
        #bomContainer .product-select,
        #bomContainer .product-select + .select2-container .select2-selection__rendered {
            font-weight: 600 !important;
            white-space: nowrap;
            overflow-wrap: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 767px) {
            #bomContainer .bom-row-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #bomContainer .bom-row-grid > div:nth-child(n) { grid-column: auto; grid-row: auto; }
            #bomContainer .bom-row-grid > div:nth-child(1),
            #bomContainer .bom-row-grid > div:nth-child(2) { grid-column: 1 / -1; }
            #bomContainer .bom-row-grid > div:nth-child(1) { padding-right: 38px; }
        }
    </style>
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
        $estimateTaxRows = collect($gstTaxes ?? [])->flatMap(function ($tax) {
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

        $savedGlobalTaxRate = (float) ($invoice->gst ?? 0);
        $savedGstBreakdown = is_array($invoice->gst_breakdown)
            ? $invoice->gst_breakdown
            : (json_decode((string) $invoice->gst_breakdown, true) ?: []);
        if ($estimatePriceMode === 'base' && isset($savedGstBreakdown['tax_rate'])) {
            $savedGlobalTaxRate = (float) $savedGstBreakdown['tax_rate'];
        }
    @endphp

    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Invoice</h1>
                        <p class="text-muted small mb-0">Invoice No: {{ $invoice->invoice_no }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end align-items-center">
                        <div class="estimate-price-mode-card">
                            <span class="estimate-price-mode-title fw-bold">Pricing Method</span>
                            <div class="estimate-price-mode-options" role="group" aria-label="Pricing Method">
                                <button type="button" class="estimate-price-mode-option" data-price-mode-option="base">
                                    <i class="bi bi-cash-stack" aria-hidden="true"></i>Base Price
                                </button>
                                <button type="button" class="estimate-price-mode-option" data-price-mode-option="bom">
                                    <i class="bi bi-boxes" aria-hidden="true"></i>BOM Price
                                </button>
                            </div>
                            <select name="price_mode" id="estimate_price_mode_selector" form="invoiceEditForm" class="form-select form-select-sm estimate-price-mode-selector estimate-price-mode-select" aria-label="Pricing Method">
                                <option value="bom" @selected($estimatePriceMode === 'bom')>Show BOM Price only</option>
                                <option value="base" @selected($estimatePriceMode === 'base')>Show Base Price only</option>
                            </select>
                        </div>
                        @can('invoices.view')
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
                    id="invoiceEditForm" data-price-suggestions-url="{{ route('api.invoices.price-suggestions') }}" action="/api/invoices/{{ $invoice->id }}">
                    @csrf
                    <input type="hidden" name="currency_id" value="{{ old('currency_id', $invoice->currency_id ?? $defaultCurrencyId) }}">
                    @method('PUT')

                    <!-- Mobile Step Indicator -->
                    <div class="create-step-indicator d-md-none mt-2">
                        <div class="create-step-dot active" id="cdot-1"></div>
                        <div class="create-step-dot" id="cdot-2"></div>
                        <div class="create-step-dot" id="cdot-3"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 create-step-1 active-step">
                            <label class="form-label fw-semibold mb-1">Select Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100" style="min-width: 0;">
                                    <select name="customer_id" id="select_customer"
                                        class="form-select @error('customer_id') is-invalid @enderror" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice->customer_id) == $customer->id)>
                                                {{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#invoiceAddCustomerModal" title="Add New Customer">
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

                        <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col">
                            <label for="reference_no" class="form-label fw-semibold">Reference#</label>
                            <input type="text" name="reference_no" id="reference_no" maxlength="255"
                                value="{{ old('reference_no', $invoice->reference_no) }}"
                                class="form-control @error('reference_no') is-invalid @enderror">
                            <div class="invalid-feedback" id="reference_no-error">
                                @error('reference_no')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>

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

                        <div class="col-6 col-md-4 create-step-1 active-step estimate-form-field-col estimate-base-price-col {{ $estimatePriceMode === 'bom' ? 'd-none' : '' }}">
                            <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" name="price" id="price"
                                value="{{ $estimatePriceMode === 'bom' ? 0 : old('price', $invoice->price) }}"
                                class="form-control @error('price') is-invalid @enderror" placeholder="Enter price"
                                @required($estimatePriceMode === 'base')>
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

                        <div class="col-12 col-md-4 create-step-1 active-step estimate-form-field-col" id="edit_template_wrapper">
                            <label class="form-label fw-semibold w-100 d-flex align-items-center gap-2 mb-1">
                                <span class="estimate-template-label-group d-inline-flex align-items-center gap-1 min-width-0">
                                    Invoice Format <span class="text-danger">*</span>
                                </span>
                                <a href="#" id="edit_template_link" class="text-primary small text-decoration-none flex-shrink-0 ms-auto d-none" target="_blank" title="Edit selected template">
                                    Edit
                                </a>
                            </label>
                            <select name="template_id" id="template_id"
                                class="form-select @error('template_id') is-invalid @enderror" required>
                                <option value="">Select Invoice Format</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected(old('template_id', $invoice->template_id) == $template->id)>
                                        {{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="template_id-error">Please select invoice format</div>
                        </div>

                        <div class="col-12 create-step-1 active-step estimate-charges-date-group">
                            <div class="row g-3">
                                <div class="col-12 {{ $estimatePriceMode === 'base' ? 'col-md-4' : 'col-md-8' }} estimate-charges-col">
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

                                    <div class="col-12 col-md-4 estimate-global-tax-col {{ $estimatePriceMode === 'base' ? '' : 'd-none' }}">
                                        <label class="form-label fw-semibold">Tax Rate (Global)</label>
                                        <select name="global_tax_rate" id="global_tax_rate" class="form-select">
                                            <option value="0" data-label="No Tax" @selected($savedGlobalTaxRate <= 0)>No Tax</option>
                                            @foreach ($bomTaxOptions as $taxOption)
                                                <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}" @selected(abs($savedGlobalTaxRate - (float) $taxOption['rate']) < 0.001)>{{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>

                                <div class="col-12 col-md-4 d-none d-md-block estimate-created-date-col">
                                    <label class="form-label fw-semibold">Created Date</label>
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
                            <div class="bom-section bg-light rounded-3 p-2 border">
                                <label for="estimateBomSearch" class="form-label small fw-semibold">Search BOM</label>
                                <div class="input-group mb-1" style="max-width:480px;">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" id="estimateBomSearch" class="form-control" placeholder="Search BOM by name..." autocomplete="off" aria-controls="bomContainer">
                                </div>
                                <div id="estimateBomSearchEmpty" class="text-muted text-center py-3 d-none" role="status">No matching BOMs found.</div>
                                <div id="bomContainer" style="max-height:60vh;overflow-y:auto;overflow-x:hidden;padding-right:8px;">
                                    @foreach ($selectedProducts as $index => $selectedProduct)
                                        @php
                                            $selectedBom = $bomProducts->firstWhere('id', $selectedProduct['product_id'] ?? null);
                                            $selectedUnitPrice = $selectedProduct['price'] ?? ($selectedBom->price ?? 0);
                                            $selectedQuantity = $selectedProduct['quantity'] ?? 0;
                                            $selectedTaxRate = (float) ($selectedProduct['tax_rate'] ?? 0);
                                        @endphp
                                        <div class="bom-row mb-3 p-3 bg-white border rounded shadow-sm"
                                            data-bom-specifications="{{ json_encode([(string) ($selectedProduct['product_id'] ?? '') => array_merge($selectedBom ? $selectedBom->only(['technology_id', 'warranty_id', 'height', 'fitting_material', 'fitting_type', 'thickness', 'size_of_pipe', 'capacity', 'meter', 'nos']) : [], array_intersect_key($selectedProduct, array_flip(['technology_id', 'warranty_id', 'height', 'fitting_material', 'fitting_type', 'thickness', 'size_of_pipe', 'capacity', 'meter', 'nos'])))]) }}">
                                            <div class="bom-row-grid" @style([$estimatePriceMode === 'base' ? 'grid-template-columns: minmax(180px, 2fr) minmax(130px, 1.2fr) minmax(90px, .7fr) minmax(70px, auto)' : ''])>
                                                <div>
                                                    <label class="form-label small fw-semibold w-100 d-flex align-items-center gap-2 mb-1">
                                                        <span><i class="fa-solid fa-cubes crm-label-icon" aria-hidden="true"></i>BOM <span class="text-danger">*</span></span>
                                                    </label>
                                                    <div class="d-flex align-items-start gap-2">
                                                        <select name="service[]" class="form-select product-select" required>
                                                            <option value="">Select BOM</option>
                                                            @foreach ($bomProducts as $bom)
                                                                <option value="{{ $bom->id }}"
                                                                    data-name="{{ $bom->product_name }}"
                                                                    data-hsn="{{ $bom->hsn_sac ?? "" }}"
                                                                    data-desc="{{ $bom->description ?? '' }}"
                                                                    data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'
                                                                    data-price="{{ $bom->price ?? 0 }}"
                                                                    data-meter="{{ $bom->meter ?? '' }}"
                                                                    data-nos="{{ $bom->nos ?? '' }}"
                                                                    data-tax-rate="{{ $bom->tax_rate ?? 0 }}"
                                                                    @selected((string) ($selectedProduct['product_id'] ?? '') === (string) $bom->id)>
                                                                    {{ $bom->product_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-outline-primary edit-bom-link d-none flex-shrink-0" style="width: 38px; height: 38px; padding: 0;" title="Edit selected BOM">
                                                            <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-add-bom-row" style="width: 38px; height: 38px; padding: 0;" data-bs-toggle="modal" data-bs-target="#invoiceQuickAddBomModal" title="Add New BOM">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                <textarea name="product_description[]" class="form-control product-description mt-1" rows="3" aria-label="BOM description" style="min-height: 84px; resize: vertical;" placeholder="Add a description to your item">{{ $selectedProduct['description'] ?? ($selectedBom->description ?? '') }}</textarea>
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold"><i class="fa-solid fa-industry crm-label-icon" aria-hidden="true"></i>Make</label>
                                                    <select name="product_make[]" class="form-select product-make"
                                                        data-selected="{{ $selectedProduct['category_name'] ?? '' }}"
                                                        @disabled(empty($selectedProduct['product_id']))>
                                                        <option value="">Select Make</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold"><i class="fa-solid fa-hashtag crm-label-icon" aria-hidden="true"></i>HSN/SAC</label>
                                                    <input type="text" name="product_hsn_sac[]" class="form-control product-hsn-sac mt-1" value="{{ $selectedProduct['hsn_sac'] ?? optional($bomProducts->firstWhere('id', $selectedProduct['product_id'] ?? null))->hsn_sac ?? '' }}" placeholder="HSN/SAC Code">
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-semibold product-qty-label"><i class="fa-solid fa-cubes-stacked crm-label-icon" aria-hidden="true"></i>Qty <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_qty[]"
                                                        value="{{ (float) $selectedQuantity > 0 ? $selectedQuantity : '' }}"
                                                        class="form-control" placeholder="Add Quantity">
                                                </div>
                                                <div class="estimate-bom-money-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                    <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Rate <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_price[]"
                                                        value="{{ round((float) $selectedUnitPrice) }}"
                                                        class="form-control product-price" placeholder="0">
                                                </div>
                                                <div class="estimate-bom-money-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                    <label class="form-label small fw-semibold"><i class="fa-solid fa-percent crm-label-icon" aria-hidden="true"></i>Tax</label>
                                                    <select name="product_tax_rate[]" class="form-select product-tax-rate">
                                                        <option value="0" data-label="" @selected($selectedTaxRate <= 0)>No Tax</option>
                                                        @foreach ($bomTaxOptions as $taxOption)
                                                            <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}" @selected(abs($selectedTaxRate - (float) $taxOption['rate']) < 0.001)>
                                                                {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="estimate-bom-money-col {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                                    <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Amount</label>
                                                    <input type="number" min="0" step="1"
                                                        value="{{ round((float) $selectedQuantity * (float) $selectedUnitPrice) }}"
                                                        class="form-control product-total" placeholder="0" readonly>
                                                </div>
                                                <div class="bom-action-cell">
                                                    <button type="button" class="btn btn-outline-danger w-100 delete-bom-row"
                                                        style="display: {{ $index === 0 ? 'none' : 'block' }};">
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-dark-blue btn-sm" id="add_more_bom">
                                    <i class="bi bi-plus-circle me-1"></i>Add More BOM
                                </button>
                                <div class="estimate-bom-instruction" id="products-error" style="display:none;" role="status">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i><span class="products-error-text">Please select at least one BOM.</span>
                                </div>
                            </div>
                        </div>

                        <div hidden>
                            @include('crm.invoices.partials.bom-specifications-table')
                        </div>

                        <div class="col-lg-6 create-step-3">
                            <div>
                                <label class="form-label fw-semibold w-100 d-flex align-items-center gap-2 mb-1">
                                    <span>Comment</span>
                                    <button type="button" id="edit_template_comment_btn" class="btn btn-link btn-sm text-primary text-decoration-none p-0 ms-auto d-none">
                                        Edit
                                    </button>
                                </label>
                                <input type="hidden" name="update_template_comment" id="update_template_comment" value="0">
                                <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="2"
                                    placeholder="Add any comments...">{{ old('comment', $invoice->comment) }}</textarea>
                                <div class="invalid-feedback" id="comment-error">
                                    @error('comment')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                            @include('crm.invoices.partials.terms-conditions-form')
                        </div>

                        <div class="col-lg-6 create-step-3">
                            <div class="totals-card rounded-3 d-flex flex-column">
                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal:</span>
                                    <span id="subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center {{ $estimatePriceMode === 'base' ? 'd-none' : '' }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="apply_gst" checked>
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

                                <div class="totals-row border-top pt-2 mt-2">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal (Tax Incl.):</span>
                                    <span id="subtotal_tax_incl_display" class="fw-bold text-dark">0.00</span>
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

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark-blue d-none d-md-inline-block">Cancel</a>
                        <button type="button" class="btn btn-outline-dark-blue mobile-create-wizard-btn create-prev-btn" style="display: none !important;">Back</button>
                        <div class="d-flex gap-2 ms-sm-auto justify-content-end w-100 w-sm-auto">
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
    <div class="modal fade" id="invoiceQuickAddBomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 640px;">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white">Add New BOM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="invoiceQuickAddBomForm" novalidate>
                        {{-- Row 1: BOM Name | Make --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="invoice_quick_bom_name" placeholder="Enter BOM name" required>
                                <div class="invalid-feedback" id="invoice_quick_bom_name-error">Please enter BOM name</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Make</label>
                                <select class="form-select quick-bom-make-select" id="invoice_quick_bom_category_id">
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
                                <textarea class="form-control" id="invoice_quick_bom_description" rows="3" placeholder="BOM Description" style="resize:none;"></textarea>
                                <label for="invoice_quick_bom_hsn_sac" class="form-label fw-semibold mt-3"><i class="fa-solid fa-hashtag crm-label-icon" aria-hidden="true"></i>HSN/SAC Code</label>
                                <input type="text" class="form-control" id="invoice_quick_bom_hsn_sac" placeholder="HSN/SAC Code">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold"><i class="bi bi-image crm-label-icon" aria-hidden="true"></i> BOM Image</label>
                                <input type="file" class="form-control" id="invoice_quick_bom_image" accept="image/jpeg,image/png,image/jpg" style="cursor:pointer;">
                                <div class="form-text text-muted">Accepted: JPG, PNG, JPEG &mdash; Max 5MB</div>
                                <div id="invoice_quick_bom_image_preview" class="mt-2" style="display:none;">
                                <button type="button" class="btn btn-sm btn-danger bom-image-remove" data-input="invoice_quick_bom_image" data-preview="invoice_quick_bom_image_preview" style="position:absolute; margin-left:115px; margin-top:-6px; width:24px; height:24px; padding:0; border-radius:50%;">&times;</button>
                                    <img src="" alt="BOM Image Preview" id="invoice_quick_bom_image_thumb"
                                        style="max-height:80px; max-width:140px; border-radius:6px; border:1px solid #dee2e6; object-fit:contain; background:#f8f9fa; padding:3px;">
                                </div>
                            </div>
                        </div>
                        {{-- Row 3: Unit Price | Tax --}}
                        <div class="row g-3 mb-0">
                            <div class="col-6">
                                <label class="form-label fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Rate <span class="text-danger">*</span></label>
                                <input type="number" min="0" step="1" class="form-control" id="invoice_quick_bom_price" placeholder="Enter unit price" required>
                                <div class="invalid-feedback" id="invoice_quick_bom_price-error">Please enter unit price</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Tax</label>
                                <select class="form-select" id="invoice_quick_bom_tax_rate">
                                    <option value="0" data-label="">No Tax</option>
                                    @foreach ($bomTaxOptions as $taxOption)
                                        <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                            {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="invoiceSaveQuickBomBtn">Save BOM</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Add Customer Modal -->
    <div class="modal fade" id="invoiceAddCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white">Add New Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="invoiceAddCustomerQuickForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="invoice_quick_customer_name" required>
                            <div class="invalid-feedback">Please enter customer name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="invoice_quick_customer_number" required>
                            <div class="invalid-feedback">Please enter mobile number</div>
                        </div>
                        <div class="mb-3">
                            <a href="#" class="small text-decoration-none" onclick="$('#invoice_quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="invoice_quick_address_container">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="invoice_quick_customer_address" rows="2" placeholder="Address"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="invoiceSaveQuickCustomerBtn">Save Customer</button>
                </div>
            </div>
        </div>
    </div>
    @include('crm.invoices.partials.edit-bom-modal')
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
        window.documentFormConfig = {
            formSelector: '.ajax-invoice-form',
            eventNs: 'invoice',
            nameField: 'invoice_name',
            nameErrorId: 'invoice_name-error',
            nameLabel: 'invoice name',
            namePrefix: 'INV-',
            defaultRedirect: '/invoices',
            templateCommentsKey: 'estimateTemplateComments',
            bomQuickAddConfigKey: 'estimateBomQuickAddConfig',
            requireCurrency: false,
            typeErrorMessage: 'Please select invoice type',
            bomPrereqMessage: 'Please fill required invoice details before adding BOM',
            saveSuccessMessage: 'Invoice saved successfully.',
            saveErrorMessage: 'Something went wrong while submitting the invoice.',
        };
        window.estimateTemplateComments = @json($templateComments);
        window.subsidiesData = @json($subsidies ?? []);
        window.estimateTaxes = @json($estimateTaxRows);
        window.estimateBomQuickAddConfig = {
            storeUrl: @json(route('api.bom-products.store')),
            makeStoreUrl: @json(route('api.make.store'))
        };
        window.documentEstimatePriceMode = @json($estimatePriceMode);
        window.estimatePriceMode = window.documentEstimatePriceMode;
        window.estimateSavedPricing = {
            basePrice: @json((float) $invoice->price),
            globalTaxRate: @json($savedGlobalTaxRate)
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/invoice-form.js') }}"></script>
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

            $('#invoiceSaveQuickCustomerBtn').click(function() {
                let name = $('#invoice_quick_customer_name').val().trim();
                let number = $('#invoice_quick_customer_number').val().trim();
                let address = $('#invoice_quick_customer_address').val().trim();

                $('#invoice_quick_customer_name, #invoice_quick_customer_number').removeClass('is-invalid');

                if (!name || !number) {
                    if(!name) $('#invoice_quick_customer_name').addClass('is-invalid');
                    if(!number) $('#invoice_quick_customer_number').addClass('is-invalid');
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
                            $('#invoiceAddCustomerModal').modal('hide');
                            $('#invoiceAddCustomerQuickForm')[0].reset();
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

            // Mobile Wizard Logic for Edit
            let currentStep = 1;
            const totalSteps = 3;

            function updateEditWizardUI() {
                if (window.innerWidth >= 768) {
                    $('#invoiceEditForm .create-step-1, #invoiceEditForm .create-step-2, #invoiceEditForm .create-step-3').removeClass('active-step');
                    $('.create-step-indicator').addClass('d-none');
                    $('.create-prev-btn, .create-next-btn').addClass('d-none');
                    $('.create-submit-btn').removeClass('d-none');
                    return;
                }

                $('.create-step-indicator').removeClass('d-none');
                $('#invoiceEditForm .create-step-1, #invoiceEditForm .create-step-2, #invoiceEditForm .create-step-3').removeClass('active-step');
                $('#invoiceEditForm .create-step-' + currentStep).addClass('active-step');

                $('.create-step-dot').removeClass('active');
                for (let i = 1; i <= currentStep; i++) {
                    $('#cdot-' + i).addClass('active');
                }

                if (currentStep === 1) {
                    $('.create-prev-btn').attr('style', 'display: none !important;');
                    $('.create-next-btn').attr('style', 'display: inline-block !important;');
                    $('.create-submit-btn').attr('style', 'display: none !important;');
                } else if (currentStep === 2) {
                    $('.create-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.create-next-btn').attr('style', 'display: inline-block !important;');
                    $('.create-submit-btn').attr('style', 'display: none !important;');
                } else {
                    $('.create-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.create-next-btn').attr('style', 'display: none !important;');
                    $('.create-submit-btn').attr('style', 'display: inline-block !important;');
                }
            }

            // Step 2 Validation (BOM check)
            function validateStep2BOM(forceShow = false) {
                if (typeof window.validateEstimateBomRows === 'function') {
                    return window.validateEstimateBomRows({ clearFirst: forceShow }).isValid;
                }

                const bomError = $('#products-error');
                let hasValidBom = false;

                $('#bomContainer .bom-row').each(function () {
                    const select = $(this).find('.product-select');

                    if (select.val()) {
                        hasValidBom = true;
                    }
                });

                if (!hasValidBom) {
                    if (forceShow) {
                        bomError.addClass('d-block').show();
                        $('#bomContainer .bom-row:first-child .product-select').first().addClass('is-invalid');
                    }
                    return false;
                }

                bomError.removeClass('d-block').hide();
                return true;
            }

            $('.create-next-btn').click(function () {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateEditWizardUI();
                }
            });

            $('.create-prev-btn').click(function () {
                if (currentStep > 1) {
                    currentStep--;
                    updateEditWizardUI();
                }
            });

            // Dynamically clear validation errors using event delegation
            $('#invoiceEditForm').on('change input', '[required], .product-select, .product-make, input[name="product_qty[]"], .product-price', function() {
                const val = $(this).val();
                if (val || $(this).attr('name') === 'product_qty[]' || $(this).hasClass('product-price')) {
                    if (typeof window.markEstimateBomFieldInvalid === 'function') {
                        window.markEstimateBomFieldInvalid(this, false);
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    if ($(this).hasClass('product-make')) {
                        $(this).closest('.bom-row').find('.bom-make-error').removeClass('d-block');
                    }
                    if ($(this).hasClass('product-select')) {
                        $('#products-error').hide();
                    }
                }
            });

            // Initial setup
            updateEditWizardUI();
            $(window).resize(updateEditWizardUI);
            // Quotation template edit link logic
            const templateSelect = $('#template_id');
            const editTemplateLink = $('#edit_template_link');

            function updateTemplateEditLink() {
                const val = templateSelect.val();
                if (val) {
                    editTemplateLink.attr('href', '/pdfbuilder/edit/' + val);
                    editTemplateLink.removeClass('d-none');
                } else {
                    editTemplateLink.attr('href', '#');
                    editTemplateLink.addClass('d-none');
                }
            }

            templateSelect.on('change', updateTemplateEditLink);
            updateTemplateEditLink();

            // Auto-select Invoice Format based on Invoice Type
            $('#type').on('change', function() {
                var selectedType = $(this).find('option:selected').text().trim().toLowerCase();
                if (!selectedType || selectedType === 'select type') return;
                if (!['residential', 'commercial', 'industrial', 'common meter'].includes(selectedType)) {
                    selectedType = 'basic';
                }

                $('#template_id option').each(function() {
                    var templateName = $(this).text().trim().toLowerCase();
                    if (templateName === selectedType || templateName === selectedType + ' template') {
                        $('#template_id').val($(this).val()).trigger('change');
                        return false;
                    }
                });
            });
        });
    </script>
@endpush
