<style>
    #quickEstimateModal .modal-dialog {
        z-index: 1055;
    }

    #addCustomerModal,
    #quickAddBomModal,
    #editBomModal {
        z-index: 1065 !important;
    }

    body.modal-open .modal-backdrop.show ~ .modal-backdrop.show {
        z-index: 1060;
    }

    /* Ensure the 3rd+ backdrop (e.g. quickAddBomModal inside quickEstimateModal) is raised above all modals */
    #quickAddBomModal + .modal-backdrop,
    #editBomModal + .modal-backdrop {
        z-index: 1062 !important;
    }

    #quickEstimateModal .quick-bom-row .quick-bom-select-col {
        min-width: 0;
    }

    .select2-dropdown,
    .select2-container--open {
        z-index: 99999 !important;
    }

    #quickEstimateModal .d-flex:has(.is-invalid) ~ .invalid-feedback {
        display: block !important;
    }

    #quickEstimateModal .quick-price-mode-card {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 8px;
        background: rgba(255, 255, 255, .08);
        padding: 5px 6px 5px 10px;
    }

    #quickEstimateModal .quick-price-mode-title {
        color: rgba(255, 255, 255, .88);
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        margin-right: 2px;
    }

    #quickEstimateModal .quick-price-mode-options {
        display: inline-flex;
        gap: 4px;
        padding: 4px;
        border-radius: 10px;
        background: rgba(2, 6, 23, .35);
    }

    #quickEstimateModal .quick-price-mode-option {
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: rgba(255, 255, 255, .74);
        min-height: 28px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    #quickEstimateModal .quick-price-mode-option:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, .1);
    }

    #quickEstimateModal .quick-price-mode-option.active {
        color: #0f172a;
        background: linear-gradient(135deg, #ffffff 0%, #bfdbfe 100%);
        box-shadow: 0 4px 10px rgba(96, 165, 250, .18);
    }

    #quickEstimateModal .quick-price-mode-select {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 1px;
        height: 1px;
    }

    @media (min-width: 768px) {
        #quickEstimateModal .quick-comment-col,
        #quickEstimateModal .quick-totals-col {
            display: flex;
            flex-direction: column;
        }

        #quickEstimateModal .quick-comment-col textarea {
            flex: 1;
            min-height: 210px;
        }

        #quickEstimateModal .quick-totals-card {
            height: 100%;
        }
    }

    @media (max-width: 767.98px) {
        #addCustomerModal.modal,
        #quickAddBomModal.modal,
        #editBomModal.modal {
            padding-left: 1.25rem !important;
            padding-right: 1.25rem !important;
        }

        #addCustomerModal .modal-dialog,
        #quickAddBomModal .modal-dialog,
        #editBomModal .modal-dialog,
        .quick-estimate-nested-modal {
            margin: 1.5rem auto !important;
            max-width: min(340px, calc(100vw - 2.5rem)) !important;
            width: 100% !important;
        }

        #addCustomerModal .modal-body,
        #quickAddBomModal .modal-body,
        #editBomModal .modal-body {
            padding: 1rem;
        }
    }

    @media (max-width: 767.98px) {
        #quickEstimateModal {
            padding-bottom: 220px !important;
        }

        #quickEstimateModal .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
            align-items: flex-start;
            min-height: calc(100% - 1rem);
        }

        #quickEstimateModal .modal-content {
            margin-bottom: 3rem;
            overflow-x: clip;
        }

        #quickEstimateModal .modal-body {
            padding-bottom: 3rem !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            overflow-x: clip;
        }

        #quickEstimateModal .modal-header {
            align-items: flex-start;
            gap: .75rem;
        }

        #quickEstimateModal .quick-price-mode-card {
            min-width: 100%;
        }

        #quickEstimateModal .quick-totals-card .totals-row > .input-small {
            width: 120px;
            min-width: 120px;
            max-width: 160px;
        }

        #quickEstimateModal .quick-step-1,
        #quickEstimateModal .quick-step-2,
        #quickEstimateModal .quick-step-3 {
            display: none !important;
        }

        #quickEstimateModal .active-step {
            display: block !important;
        }

        #quickEstimateModal .active-step > .row {
            display: flex !important;
            flex-wrap: wrap;
        }

        .quick-bom-row-grid .quick-bom-select-col,
        .quick-bom-row-grid .quick-bom-make-col {
            grid-column: span 2;
        }

        .quick-step-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .quick-step-dot {
            height: 8px;
            width: 100%;
            background: #e9ecef;
            border-radius: 10px;
            transition: 0.3s;
        }

        .quick-step-dot.active {
            background: #121a33;
        }
    }

    @media (min-width: 768px) {
        .quick-step-indicator {
            display: none !important;
        }
    }
</style>
