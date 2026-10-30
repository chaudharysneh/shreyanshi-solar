@extends('layouts.app')

@section('page_title', 'BOM - Create')

@section('content')
<div class="container-fluid p-0">
    @include('crm.bom.partials.form', [
        'title' => 'Add BOM',
        'subtitle' => 'Create a BOM entry in Solar CRM.',
        'action' => route('api.bom-products.store'),
        'method' => null,
        'product' => null,
    ])
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/product-form.css') }}">
<style>
    .bom-form-card .bom-label {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .bom-form-card .bom-label i {
        width: 14px;
        text-align: center;
        font-size: 0.95rem;
    }

    /* Make tags styling */
    .make-input-wrapper {
        border: 1px solid #dee2e6 !important;
    }

    .make-input-wrapper:focus-within {
        border-color: #5e72e4 !important;
        box-shadow: 0 0 0 0.2rem rgba(94, 114, 228, 0.25);
    }

    .make-input-wrapper.is-invalid {
        border-color: var(--bs-danger) !important;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.15) !important;
    }

    .make-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #e7f1ff;
        color: #1e3a8a;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .make-tag .remove-tag {
        cursor: pointer;
        font-weight: bold;
        color: #1e3a8a;
        margin-left: 0.25rem;
        font-size: 1.1rem;
        line-height: 1;
    }

    .make-tag .remove-tag:hover {
        color: #dc2626;
    }

    .make-dropdown-list {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .make-option {
        transition: background-color 0.2s;
    }

    .make-option:hover {
        background-color: #f0f0f0;
    }

    .make-option.selected {
        background-color: #e7f1ff;
        color: #1e3a8a;
        font-weight: 500;
    }

    /* Technology Modal Error Styling */
    #addTechnologyModal .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    #addTechnologyModal .form-control.is-invalid,
    #addTechnologyModal .form-select.is-invalid {
        border-color: #dc3545;
    }

    /* Warranty Modal Error Styling */
    #addWarrantyModal .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    #addWarrantyModal .form-control.is-invalid,
    #addWarrantyModal .form-select.is-invalid {
        border-color: #dc3545;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-results__options {
        max-height: 200px !important;
        overflow-y: auto !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-searchable').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });

        // Clear validation errors on select2 change
        $('.select2-searchable').on('change', function() {
            if ($(this).hasClass('is-invalid')) {
                $(this).removeClass('is-invalid');
                const $container = $(this).next('.select2-container');
                if ($container.length) {
                    $container.find('.select2-selection').removeClass('is-invalid');
                }
                const fieldId = $(this).attr('id') || $(this).attr('name').replace('[]', '');
                const $errorDiv = $(`#${fieldId}-error`);
                if ($errorDiv.length) {
                    $errorDiv.text('');
                }
            }
        });
    });
</script>
<script>
    window.bomFormConfig = {
        redirectUrl: @json(route('bom-products.index'))
    };
</script>
<script src="{{ crm_asset('js/bom-products.js') }}"></script>
@endpush
