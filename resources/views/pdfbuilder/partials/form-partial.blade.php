@php
    $imageExists = function($path) {
        if (empty($path)) return false;
        $path = ltrim($path, '/');
        if (file_exists(public_path($path))) return true;
        if (strpos($path, 'storage/') === 0 && file_exists(storage_path('app/public/' . substr($path, 8)))) return true;
        if (file_exists(base_path($path))) return true;
        return false;
    };

    $formData = [];
    if (isset($template) && isset($template->form_data)) {
        $formData = is_array($template->form_data) ? $template->form_data : (json_decode($template->form_data, true) ?: []);
    }

    $companyInfo = $companyInfo ?? (isset($template->company_information) ? (is_array($template->company_information) ? $template->company_information : json_decode($template->company_information, true)) : []);
    $companyInfo = is_array($companyInfo) ? $companyInfo : [];

    $timeLine = $timeLine ?? (isset($template->time_line) ? (is_array($template->time_line) ? $template->time_line : json_decode($template->time_line, true)) : []);
    $timeLine = is_array($timeLine) ? $timeLine : [];

    $paymentTerms = $paymentTerms ?? (
        isset($template->payment_terms)
            ? (is_array($template->payment_terms) ? $template->payment_terms : json_decode($template->payment_terms, true))
            : ($formData['payment_terms'] ?? [])
    );
    $paymentTerms = is_array($paymentTerms) ? $paymentTerms : [];

    $environmentImpact = $environmentImpact ?? (
        isset($template->environment_impact)
            ? (is_array($template->environment_impact) ? $template->environment_impact : json_decode($template->environment_impact, true))
            : ($formData['environment_impact'] ?? [])
    );
    $environmentImpact = is_array($environmentImpact) ? $environmentImpact : [];

    $footer = $footer ?? (
        isset($template->footer)
            ? (is_array($template->footer) ? $template->footer : json_decode($template->footer, true))
            : ($formData['footer'] ?? [])
    );
    $footer = is_array($footer) ? $footer : [];

    $components = $components ?? (
        isset($template->components)
            ? (is_array($template->components) ? $template->components : json_decode($template->components, true))
            : ($formData['components'] ?? [])
    );
    $components = is_array($components) ? $components : [];

    $generationSection = $formData['generation'] ?? [];
    $generationSection = is_array($generationSection) ? $generationSection : [];

    $ongridRoiSection = $formData['ongrid_roi'] ?? [];
    $ongridRoiSection = is_array($ongridRoiSection) ? $ongridRoiSection : [];

    $estimateCommentSection = $formData['estimate_comment'] ?? [];
    $estimateCommentSection = is_array($estimateCommentSection) ? $estimateCommentSection : [];
@endphp

@push('styles')
<style>
    .border-dashed-blue {
        border: 3px dashed #4b9349;
    }

    .bg-blue {
        background: #bdc5f121;
    }

    #pdf-builder-form .form-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    #pdf-builder-form .form-label i,
    #pdf-builder-form h5 i {
        margin-right: 0px;
        flex-shrink: 0;
    }

    #pdf-builder-form .form-label .text-danger {
        margin-left: 2px;
    }

    /* 🔵 CKEditor Styling */
    .cke {
        border-radius: 6px !important;
        overflow: hidden;
    }

    .cke_top {
        border-radius: 8px 8px 0 0 !important;
    }

    .cke_bottom {
        border-radius: 0 0 8px 8px !important;
    }

    /* Light border for form blocks */
    .block {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px 18px 0 18px;
        background: #fff;
        margin-bottom: 1.5rem;
    }

    .company-info-block {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px;
        background: #fff;
        margin-bottom: 1.5rem;
    }
    .time-line-block {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px;
        background: #fff;
        margin-bottom: 1.5rem;
    }
    .payment-terms-block{
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px;
        background: #fff;
        margin-bottom: 1.5rem;
    }
    .environment-impact-block{
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px;
        background: #fff;
        margin-bottom: 1.5rem;   
    }
    .footer-block{
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 18px;
        background: #fff;
        margin-bottom: 1.5rem;
    }

    .addmore:focus {
        color: white;
    }   

    .text-primary
    {
        color: #4b9349 !important;
    }
    .btn-outline-primary
    {
        color: #4b9349 !important;
        border-color: #4b9349 !important;
    }
    .btn-outline-primary:not(:disabled):not(.disabled).active, .btn-outline-primary:not(:disabled):not(.disabled):active, .show>.btn-outline-primary.dropdown-toggle{
        background-color:#4b9349 !important;
    }
    /* Mobile-only multi-step wizard (do not affect desktop) */
    @media (max-width: 767px) {
        .pdf-mobile-step {
            display: none;
        }
        .pdf-mobile-step.active {
            display: block;
        }
        .pdf-step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 18px;
            padding: 12px 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .pdf-step-item {
            display: flex;
            align-items: center;
            flex: 1;
            justify-content: center;
            min-width: 0;
        }
        .pdf-step-number {
            width: 30px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            margin-right: 6px;
            color: #6c757d;
        }
        .pdf-step-item.active .pdf-step-number {
            color: #4b9349;
        }
        .pdf-step-item.completed .pdf-step-number {
            color: #28a745;
        }
        .pdf-step-title {
            font-size: 11px;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pdf-step-item.active .pdf-step-title {
            color: #4b9349;
            font-weight: 700;
        }
        .pdf-step-item.completed .pdf-step-title {
            color: #28a745;
        }
        .pdf-step-connector {
            width: 24px;
            height: 2px;
            background: #dee2e6;
            margin: 0 6px;
            flex: 0 0 auto;
        }
        .pdf-step-navigation {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 12px;
            background: #fff;
            border-top: 1px solid #dee2e6;
            position: sticky;
            bottom: 0;
            z-index: 100;
        }
        .pdf-step-navigation button {
            min-width: 110px;
        }
        /* leave room for sticky nav */
        form.pdfbuilder-wizard-form {
            padding-bottom: 72px;
        }
    }

    /* Desktop: show everything, hide wizard UI */
    @media (min-width: 768px) {
        .pdf-mobile-step {
            display: block !important;
        }
        .pdf-step-indicator,
        .pdf-step-navigation {
            display: none !important;
        }
        form.pdfbuilder-wizard-form {
            padding-bottom: 0;
        }

    }
</style>
@endpush

<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden pdfbuilder-form-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Manage PDF Template</h1>
                    <p class="text-muted small mb-0">Create or edit your custom PDF template record.</p>
                </div>
                <a href="{{ route('pdfbuilder.index') }}" class="btn btn-dark-blue back-btn">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
                    <form action="{{ $action }}" method="post" enctype="multipart/form-data" id="pdf-builder-form" class="m-3 pdfbuilder-wizard-form needs-validation" novalidate>
                        @csrf
                        @if (isset($edit_mode) && $edit_mode)
                            <input type="hidden" name="id" value="{{ $template->id }}">
                        @endif

                        <!-- Step Indicator (Mobile Only) -->
                        <div class="pdf-step-indicator">
                            <div class="pdf-step-item active" data-step="1">
                                <div class="pdf-step-number">1</div>
                                <span class="pdf-step-title d-none">Basic</span>
                            </div>
                            <div class="pdf-step-connector"></div>
                            <div class="pdf-step-item" data-step="2">
                                <div class="pdf-step-number">2</div>
                                <span class="pdf-step-title d-none">ROI</span>
                            </div>
                            <div class="pdf-step-connector"></div>
                            <div class="pdf-step-item" data-step="3">
                                <div class="pdf-step-number">3</div>
                                <span class="pdf-step-title d-none">Terms</span>
                            </div>
                            <div class="pdf-step-connector"></div>
                            <div class="pdf-step-item" data-step="4">
                                <div class="pdf-step-number">4</div>
                                <span class="pdf-step-title d-none">Footer</span>
                            </div>
                        </div>

                        <!-- STEP 1 -->
                        <div class="pdf-mobile-step active" data-step="1">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="template_name" class="form-label"><i class="fa-solid fa-file-pdf"></i> Template Name <span class="text-danger">*</span></label>
                                <input type="text" id="template_name" name="template_name" class="form-control" value="{{ old('template_name', $template->template_name ?? '') }}" required>
                                <div class="invalid-feedback text-danger small mt-1" id="template_name-error"></div>
                            </div>
                        </div>

                        <!-- Header image (first page) upload -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><i class="fas fa-image"></i> Header Image (First Page)</label>
                                @if (isset($edit_mode) && $edit_mode && !empty($template->first_img) && $imageExists($template->first_img))
                                    <div class="mb-2">
                                        <span class="small text-muted d-block">Current image (kept if you do not upload a new one):</span>
                                        <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $template->first_img) }}" alt="Current header image" style="width: 300px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <span class="small text-muted d-block">Default image (will be used if not uploaded):</span>
                                        <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/header_Image.jpg') }}" alt="Default header image" style="width: 300px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                    </div>
                                @endif
                                <input type="hidden" id="first_img_existing" value="{{ $template->first_img ?? '' }}">
                                <input type="file" name="first_img" accept="image/*" class="form-control">
                                <div class="invalid-feedback text-danger small mt-1" id="first_img-error"></div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <!-- COMPANY INFORMATION SECTION -->
                        <div class="company-info-block mt-4" id="block-company-info">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-building"></i> Company Information</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="company_info_active" name="company_info_active" value="{{ (int)($companyInfo['active'] ?? 1) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-company-info"
                                           data-target="#block-company-info-body"
                                           data-active-input="#company_info_active"
                                           {{ ((int)($companyInfo['active'] ?? 1) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-company-info">Active/Inactive</label>
                                </div>
                            </div>

                            <div id="block-company-info-body" class="block-body">
                            
                            <!-- Textarea for company description -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label class="form-label"><i class="fas fa-align-left"></i> Company Description</label>
                                    <textarea class="form-control" id="company_description" name="company_description" rows="4" placeholder="Enter company description...">{{ $companyInfo['company_description'] ?? '' }}</textarea>
                                </div>
                            </div>

                            <!-- Three input fields -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-phone"></i> Total capacity installed</label>
                                    <input type="number" class="form-control" name="company_capacity_installed" value="{{ $companyInfo['company_capacity_installed'] ?? '' }}" placeholder="Enter total capacity installed">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-envelope"></i> Happy customers</label>
                                    <input type="number" class="form-control" name="happy_customers" value="{{ $companyInfo['happy_customers'] ?? '' }}" placeholder="Enter happy customers">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-globe"></i> Cities</label>
                                    <input type="number" class="form-control" name="cities" value="{{ $companyInfo['cities'] ?? '' }}" placeholder="Enter cities">
                                </div>
                            </div>

                            <!-- Three image uploads -->
                            <div class="row mb-4">
                                <!-- Image 1 -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image 1</label>
                                    @if (isset($edit_mode) && $edit_mode && !empty($companyInfo['image1']) && $imageExists($companyInfo['image1']))
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Current Image 1:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $companyInfo['image1']) }}" alt="Image 1" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                            <input type="hidden" name="image1_old" value="{{ $companyInfo['image1'] }}">
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Default Image 1:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/seconpage_1.png') }}" alt="Default Image 1" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                        </div>
                                    @endif
                                    <div class="border-dashed-blue rounded-2 px-3 py-2 text-center bg-blue upload-area"
                                         style="cursor: pointer; min-height: 100px; display: flex; flex-direction: column; justify-content: center;"
                                         onclick="this.querySelector('input[type=file]').click()"
                                         ondragover="event.preventDefault(); this.classList.add('bg-secondary-subtle');"
                                         ondragleave="this.classList.remove('bg-secondary-subtle');"
                                         ondrop="handleCompanyFileDrop(event, this, 'image1')">
                                        <i class="fas fa-cloud-upload-alt mb-2 text-primary"></i>
                                        <p class="mb-1 small">Drag & drop or <span class="text-primary text-decoration-underline">Browse</span></p>
                                        <input type="file" class="d-none" name="image1" accept="image/*" onchange="showCompanyFileName(this, 'image1')">
                                    </div>
                                    <div class="mt-1 text-success small company-file-name-image1">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($companyInfo['image1'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>

                                <!-- Image 2 -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image 2</label>
                                    @if (isset($edit_mode) && $edit_mode && !empty($companyInfo['image2']) && $imageExists($companyInfo['image2']))
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Current Image 2:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $companyInfo['image2']) }}" alt="Image 2" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                            <input type="hidden" name="image2_old" value="{{ $companyInfo['image2'] }}">
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Default Image 2:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/secondpage_2.png') }}" alt="Default Image 2" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                        </div>
                                    @endif
                                    <div class="border-dashed-blue rounded-2 px-3 py-2 text-center bg-blue upload-area"
                                         style="cursor: pointer; min-height: 100px; display: flex; flex-direction: column; justify-content: center;"
                                         onclick="this.querySelector('input[type=file]').click()"
                                         ondragover="event.preventDefault(); this.classList.add('bg-secondary-subtle');"
                                         ondragleave="this.classList.remove('bg-secondary-subtle');"
                                         ondrop="handleCompanyFileDrop(event, this, 'image2')">
                                        <i class="fas fa-cloud-upload-alt mb-2 text-primary"></i>
                                        <p class="mb-1 small">Drag & drop or <span class="text-primary text-decoration-underline">Browse</span></p>
                                        <input type="file" class="d-none" name="image2" accept="image/*" onchange="showCompanyFileName(this, 'image2')">
                                    </div>
                                    <div class="mt-1 text-success small company-file-name-image2">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($companyInfo['image2'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>

                                <!-- Image 3 -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image 3</label>
                                    @if (isset($edit_mode) && $edit_mode && !empty($companyInfo['image3']) && $imageExists($companyInfo['image3']))
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Current Image 3:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $companyInfo['image3']) }}" alt="Image 3" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                            <input type="hidden" name="image3_old" value="{{ $companyInfo['image3'] }}">
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Default Image 3:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/secondpage_3.png') }}" alt="Default Image 3" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                        </div>
                                    @endif
                                    <div class="border-dashed-blue rounded-2 px-3 py-2 text-center bg-blue upload-area"
                                         style="cursor: pointer; min-height: 100px; display: flex; flex-direction: column; justify-content: center;"
                                         onclick="this.querySelector('input[type=file]').click()"
                                         ondragover="event.preventDefault(); this.classList.add('bg-secondary-subtle');"
                                         ondragleave="this.classList.remove('bg-secondary-subtle');"
                                         ondrop="handleCompanyFileDrop(event, this, 'image3')">
                                        <i class="fas fa-cloud-upload-alt mb-2 text-primary"></i>
                                        <p class="mb-1 small">Drag & drop or <span class="text-primary text-decoration-underline">Browse</span></p>
                                        <input type="file" class="d-none" name="image3" accept="image/*" onchange="showCompanyFileName(this, 'image3')">
                                    </div>
                                    <div class="mt-1 text-success small company-file-name-image3">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($companyInfo['image3'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        </div>

                        <!-- STEP 2 -->
                        <div class="pdf-mobile-step" data-step="2">

                        <!-- Generation is disabled globally. Keep the hidden value so old templates save inactive. -->
                        <input type="hidden" id="generation_active" name="generation_active" value="0">

                        <!-- ONGRID ROI SECTION -->
                        <div class="time-line-block mt-4" id="block-ongrid-roi">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-chart-line"></i> Ongrid ROI</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="ongrid_roi_active" name="ongrid_roi_active" value="{{ (int)($ongridRoiSection['active'] ?? 0) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-ongrid-roi"
                                           data-target="#block-ongrid-roi-body"
                                           data-active-input="#ongrid_roi_active"
                                           {{ ((int)($ongridRoiSection['active'] ?? 0) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-ongrid-roi">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-ongrid-roi-body" class="block-body">
                                <div class="row mb-4">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                                        <input type="text" class="form-control" name="ongrid_roi_title" value="{{ $ongridRoiSection['title'] ?? '' }}" placeholder="ROI">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-text-width"></i> Sub Title</label>
                                        <input type="text" class="form-control" name="ongrid_roi_sub_title" value="{{ $ongridRoiSection['sub_title'] ?? '' }}" placeholder="Ongrid ROI">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-percent"></i> Residential starts %</label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="residential_starts_percent" value="{{ $ongridRoiSection['residential_starts_percent'] ?? '' }}" placeholder="e.g. 80">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><i class="fas fa-sticky-note"></i> Note</label>
                                        <input type="text" class="form-control" name="ongrid_roi_note" value="{{ $ongridRoiSection['note'] ?? '' }}" placeholder="Optional note for ROI page">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">

                        <!-- Time line section -->
                        <div class="time-line-block mt-4" id="block-timeline">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-clock"></i> Offer & Terms</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="timeline_active" name="timeline_active" value="{{ (int)($timeLine['active'] ?? 1) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-timeline"
                                           data-target="#block-timeline-body"
                                           data-active-input="#timeline_active"
                                           {{ ((int)($timeLine['active'] ?? 1) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-timeline">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-timeline-body" class="block-body">
                            <div class="row mb-4">
                            <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i>MainTitle</label>
                                    <input type="text" class="form-control" name="main_title" value="{{ $timeLine['main_title'] ?? '' }}" placeholder="Enter title">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i>Title</label>
                                    <input type="text" class="form-control" name="title" value="{{ $timeLine['title'] ?? '' }}" placeholder="Enter title">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image</label>
                                    @if (isset($edit_mode) && $edit_mode && !empty($timeLine['image1']) && $imageExists($timeLine['image1']))
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Current Image:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $timeLine['image1']) }}" alt="Timeline Image 1" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                            <input type="hidden" name="timeline_image1_old" value="{{ $timeLine['image1'] }}">
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Default Image:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/page-5-1.png') }}" alt="Default Timeline Image 1" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="timeline_image1" accept="image/*" onchange="showCompanyFileName(this, 'timeline_image1')">
                                    <div class="mt-1 text-success small company-file-name-timeline_image1">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($timeLine['image1'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i> Title </label>
                                    <input type="text" class="form-control" name="title2" value="{{ $timeLine['title2'] ?? '' }}" placeholder="Enter title">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image</label>
                                    @if (isset($edit_mode) && $edit_mode && !empty($timeLine['image2']) && $imageExists($timeLine['image2']))
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Current Image:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $timeLine['image2']) }}" alt="Timeline Image 2" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                            <input type="hidden" name="timeline_image2_old" value="{{ $timeLine['image2'] }}">
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <span class="small text-muted d-block">Default Image:</span>
                                            <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/page-5-2.png') }}" alt="Default Timeline Image 2" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="timeline_image2" accept="image/*" onchange="showCompanyFileName(this, 'timeline_image2')">
                                    <div class="mt-1 text-success small company-file-name-timeline_image2">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($timeLine['image2'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-align-left"></i> Note</label>
                                    <input type="text" class="form-control" name="timeline_note" value="{{ $timeLine['note'] ?? '' }}" placeholder="Enter note">
                                </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        </div>

                        <!-- STEP 3 -->
                        <div class="pdf-mobile-step" data-step="3">

                        <!-- COMPONENTS SECTION -->
                        <div class="time-line-block mt-4" id="block-components">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-solar-panel"></i> Components</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="components_active" name="components_active" value="{{ (int)($components['active'] ?? 1) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-components"
                                           data-target="#block-components-body"
                                           data-active-input="#components_active"
                                           {{ ((int)($components['active'] ?? 1) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-components">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-components-body" class="block-body">
                                <div class="row mb-4">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                                        <input type="text"
                                               class="form-control"
                                               name="components_title"
                                               value="{{ $components['title'] ?? '' }}"
                                               placeholder="Enter title">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                                        <textarea class="form-control"
                                                  id="components_description"
                                                  name="components_description"
                                                  rows="4"
                                                  placeholder="Enter description...">{{ $components['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">

                        <!-- PAYMENT TERMS -->
                        <div class="payment-terms-block mt-4" id="block-estimate-comment">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-comment-dots"></i> Estimate Comment</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="estimate_comment_active" name="estimate_comment_active" value="{{ (int)($estimateCommentSection['active'] ?? 0) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-estimate-comment"
                                           data-target="#block-estimate-comment-body"
                                           data-active-input="#estimate_comment_active"
                                           {{ ((int)($estimateCommentSection['active'] ?? 0) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-estimate-comment">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-estimate-comment-body" class="block-body">
                                <div class="row mb-4">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><i class="fas fa-align-left"></i> Comment</label>
                                        <textarea class="form-control"
                                                  id="estimate_template_comment"
                                                  name="estimate_template_comment"
                                                  rows="4"
                                                  placeholder="Enter default estimate comment...">{{ $estimateCommentSection['content'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">

                        <div class="payment-terms-block mt-4" id="block-payment-terms">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-clock"></i> Payment Terms</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="payment_terms_active" name="payment_terms_active" value="{{ (int)(($paymentTerms['active'] ?? 1)) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-payment-terms"
                                           data-target="#block-payment-terms-body"
                                           data-active-input="#payment_terms_active"
                                           {{ ((int)(($paymentTerms['active'] ?? 1)) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-payment-terms">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-payment-terms-body" class="block-body">
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i>SCOPE</label>
                                    <input type="text" class="form-control" name="scope" value="{{ $paymentTerms['scope'] ?? '' }}" placeholder="Enter scope">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-list"></i> What we cover under our services</label>
                                    @php
                                        $servicesRows = [];
                                        $rawServices = $paymentTerms['services'] ?? [];
                                        if (is_array($rawServices)) {
                                            foreach ($rawServices as $row) {
                                                if (is_array($row)) {
                                                    $servicesRows[] = [
                                                        'left' => (string)($row['left'] ?? ''),
                                                        'right' => (string)($row['right'] ?? ''),
                                                    ];
                                                } else {
                                                    $servicesRows[] = [
                                                        'left' => (string)$row,
                                                        'right' => '',
                                                    ];
                                                }
                                            }
                                        }
                                        if (empty($servicesRows)) {
                                            $servicesRows = [['left' => '', 'right' => '']];
                                        }
                                    @endphp
                                    <div id="services-container">
                                        @foreach ($servicesRows as $sr)
                                            <div class="row g-2 align-items-center mb-2 service-row">
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" name="services_left[]" value="{{ $sr['left'] ?? '' }}" placeholder="Service">
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" name="services_right[]" value="{{ $sr['right'] ?? '' }}" placeholder="Details">
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeServiceRow(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-outline-dark-blue btn-sm mt-2" onclick="addServiceRow()">
                                        <i class="fa fa-plus"></i> Add More
                                    </button>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i>Note</label>
                                    <input type="text" class="form-control" name="payment_terms_note" value="{{ $paymentTerms['note'] ?? '' }}" placeholder="Enter note">
                                </div>
                                </div>

                                <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i> Title</label>
                                    <input type="text" class="form-control" name="payment_terms_title" value="{{ $paymentTerms['title'] ?? '' }}" placeholder="Enter title">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image</label>
                                     @if (isset($edit_mode) && $edit_mode && !empty($paymentTerms['image']) && $imageExists($paymentTerms['image']))
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Current Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $paymentTerms['image']) }}" alt="Payment Terms Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                             <input type="hidden" name="payment_terms_image_old" value="{{ $paymentTerms['image'] }}">
                                         </div>
                                     @else
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Default Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/page_7.png') }}" alt="Default Payment Terms Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                         </div>
                                     @endif
                                    <input type="file" class="form-control" name="payment_terms_image" accept="image/*" onchange="showCompanyFileName(this, 'payment_terms_image')">
                                    <div class="mt-1 text-success small company-file-name-payment_terms_image">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($paymentTerms['image'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        </div>

                        <!-- STEP 4 -->
                        <div class="pdf-mobile-step" data-step="4">
                        <!-- ENVIRONMENT IMPACT -->
                        <div class="environment-impact-block mt-4" id="block-environment-impact">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-clock"></i> Environment Impact</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="environment_impact_active" name="environment_impact_active" value="{{ (int)(($environmentImpact['active'] ?? 1)) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-environment-impact"
                                           data-target="#block-environment-impact-body"
                                           data-active-input="#environment_impact_active"
                                           {{ ((int)(($environmentImpact['active'] ?? 1)) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-environment-impact">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-environment-impact-body" class="block-body">
                                <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-clock"></i> Title</label>
                                    <input type="text" class="form-control" name="environment_impact_title" value="{{ $environmentImpact['title'] ?? '' }}" placeholder="Enter title">
                                </div>
                            
                            <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-align-left"></i> Content</label>
                                    <textarea class="form-control" id="environment_impact_content" name="environment_impact_content" rows="4" placeholder="Enter content...">{{ $environmentImpact['content'] ?? '' }}</textarea>
                                </div>
                            <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image</label>
                                     @if (isset($edit_mode) && $edit_mode && !empty($environmentImpact['image']) && $imageExists($environmentImpact['image']))
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Current Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $environmentImpact['image']) }}" alt="Environment Impact Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                             <input type="hidden" name="environment_impact_image_old" value="{{ $environmentImpact['image'] }}">
                                         </div>
                                     @else
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Default Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/page_8.png') }}" alt="Default Environment Impact Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                         </div>
                                     @endif
                                    <input type="file" class="form-control" name="environment_impact_image" accept="image/*" onchange="showCompanyFileName(this, 'environment_impact_image')">
                                    <div class="mt-1 text-success small company-file-name-environment_impact_image">
                                        {{ (isset($edit_mode) && $edit_mode && !empty($environmentImpact['image'])) ? 'Current file uploaded' : '' }}
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <!-- footer -->
                        <div class="footer-block mt-4" id="block-footer">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="text-primary mb-0"><i class="fas fa-clock"></i> Footer</h5>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" id="footer_active" name="footer_active" value="{{ (int)(($footer['active'] ?? 1)) }}">
                                    <input class="form-check-input block-toggle"
                                           type="checkbox"
                                           role="switch"
                                           id="toggle-footer"
                                           data-target="#block-footer-body"
                                           data-active-input="#footer_active"
                                           {{ ((int)(($footer['active'] ?? 1)) === 1) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="toggle-footer">Active/Inactive</label>
                                </div>
                            </div>
                            <div id="block-footer-body" class="block-body">
                                <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label"><i class="fas fa-image"></i> Image</label>
                                     @if (isset($edit_mode) && $edit_mode && !empty($footer['image']) && $imageExists($footer['image']))
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Current Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . $footer['image']) }}" alt="Footer Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                             <input type="hidden" name="footer_image_old" value="{{ $footer['image'] }}">
                                         </div>
                                     @else
                                         <div class="mb-2">
                                             <span class="small text-muted d-block">Default Image:</span>
                                             <img src="{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'assets/img/footer.png') }}" alt="Default Footer Image" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                         </div>
                                     @endif
                                        <input type="file" class="form-control" name="footer_image" accept="image/*" onchange="showCompanyFileName(this, 'footer_image')">
                                        <div class="mt-1 text-success small company-file-name-footer_image">
                                            {{ (isset($edit_mode) && $edit_mode && !empty($footer['image'])) ? 'Current file uploaded' : '' }}
                                        </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                            <label class="form-label"><i class="fas fa-clock"></i> Title</label>
                                            <input type="text" class="form-control" name="footer_title" value="{{ $footer['title'] ?? '' }}" placeholder="Enter title">
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label"><i class="fas fa-clock"></i> Sub Title</label>
                                            <input type="text" class="form-control" name="footer_sub_title" value="{{ $footer['sub_title'] ?? '' }}" placeholder="Enter sub title">
                                        </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                            <!-- Mobile-only inline previous (only visible in Step 4) -->
                            <button type="button" class="btn btn-secondary d-md-none" id="pdf-prev-step-inline">
                                Previous
                            </button>
                            <a href="{{ route('pdfbuilder.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                            <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                                <span id="btnText">Save Template</span>
                            </button>
                        </div>
                        </div>

                        <!-- Step Navigation Buttons (Mobile Only) -->
                        <div class="pdf-step-navigation">
                            <button type="button" class="btn btn-secondary" id="pdf-prev-step" style="display:none;">
                                Previous
                            </button>
                            <button type="button" class="btn btn-dark-blue" id="pdf-next-step">
                                Next
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@push('scripts')
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script id="pdfbuilder-data" type="application/json">
{
    "edit_mode": {{ isset($edit_mode) && $edit_mode ? "true" : "false" }}
}
</script>
<script src="{{ crm_asset('js/pdfbuilder.js') }}"></script>
@endpush
