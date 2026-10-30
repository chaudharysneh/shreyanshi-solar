@extends('layouts.app')

@section('page_title', 'Add Vendor')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/product-form.css') }}">
    <style>
        /* Fix file input height to match other form controls */
        .form-control[type="file"] {
            height: calc(1.5em + 0.75rem + 2px) !important;
            padding: 0.375rem 0.75rem !important;
            line-height: 1.5 !important;
            border: 1px solid #dee2e6 !important;
            background-color: #fff !important;
        }
        
        .form-control[type="file"]::-webkit-file-upload-button {
            padding: 0.375rem 0.75rem;
            margin: -0.375rem -0.75rem -0.375rem -0.75rem;
            margin-inline-end: 0.75rem;
            color: #212529;
            background-color: #e9ecef;
            border: 0;
            border-inline-end: 1px solid #dee2e6;
            border-radius: 0.375rem 0 0 0.375rem;
        }
        
        .form-control[type="file"]:hover::-webkit-file-upload-button {
            background-color: #ddd;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Add Vendor</h1>
                    <p class="text-muted small mb-0">Create a vendor entry for inventory operations.</p>
                </div>
                <a href="{{ route('vendors.index') }}" class="btn btn-dark-blue back-btn">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="/api/vendors" enctype="multipart/form-data" class="ajax-vendor-form" novalidate id="vendorCreateForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter vendor name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address">
                        <div class="invalid-feedback" id="email-error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" required>
                        <div class="invalid-feedback" id="phone-error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Image</label>
                        <input type="file" name="image" id="image" class="form-control" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" style="height: calc(1.5em + 0.75rem + 2px); padding: 0.375rem 0.75rem; line-height: 1.5;">
                        <div class="invalid-feedback d-block" id="image-error"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="Enter address details"></textarea>
                        <div class="invalid-feedback" id="address-error"></div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('vendors.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                        <span id="btnText">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ crm_asset('js/vendor-form.js') }}"></script>
@endpush
