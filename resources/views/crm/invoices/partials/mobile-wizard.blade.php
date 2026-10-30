{{-- Shared mobile multistep wizard styles for invoice create/edit --}}
<style>
    @media (max-width: 767.98px) {
        #{{ $formId }} .create-step-1,
        #{{ $formId }} .create-step-2,
        #{{ $formId }} .create-step-3 {
            display: none !important;
        }
        #{{ $formId }} .active-step {
            display: block !important;
        }
        #{{ $formId }} label.form-label {
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
        #{{ $formId }} .crm-label-with-icon {
            flex-wrap: nowrap;
        }
        #{{ $formId }} .crm-label-icon,
        #{{ $formId }} label.form-label .text-danger {
            flex-shrink: 0;
        }
        #{{ $formId }} .row.g-3 > [class*="col-"] {
            min-width: 0;
        }
        #{{ $formId }} .active-step > .row {
            display: flex !important;
            flex-wrap: wrap;
        }
        #{{ $formId }} .estimate-charges-date-group > .row {
            min-height: 0 !important;
        }
        #{{ $formId }} > .row.g-3 {
            min-height: 420px;
        }
        #{{ $formId }} .form-actions .mobile-create-wizard-btn {
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
</style>
