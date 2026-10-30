// Invoice form behavior follows the estimate form; kept separate to leave estimates unchanged.
(function () {
    if (window.invoiceFormJsInitialized) {
        return;
    }
    window.invoiceFormJsInitialized = true;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    async function suggestDocumentName(select, input, type) {
        if (!select?.value || !input) return;
        const customerId = select.value;
        const oldValue = input.value;
        const requestId = String((Number(input.dataset.nameRequest) || 0) + 1);
        input.dataset.nameRequest = requestId;
        try {
            const response = await fetch('/api/document-name?' + new URLSearchParams({customer_id: customerId, type}), {credentials:'same-origin', headers:{Accept:'application/json'}});
            if (!response.ok) return;
            const payload = await response.json();
            if (select.value === customerId && input.value === oldValue && input.dataset.nameRequest === requestId) input.value = payload.name;
        } catch (_) { /* The server assigns the final automatic name on save. */ }
    }

    function init() {
        initDocumentForm();
        initEditBom();
    }

    function resolveDocumentFormConfig() {
        const customConfig = window.documentFormConfig;
        if (customConfig && document.querySelector(customConfig.formSelector)) {
            return customConfig;
        }

        if (document.querySelector('.ajax-invoice-form')) {
            return {
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
        }

        return null;
    }

    function getActiveDocumentFormConfig() {
        return resolveDocumentFormConfig() || {
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
    }

    function getDocumentPriceMode() {
        const mode = window.documentEstimatePriceMode || window.estimatePriceMode;
        return mode === 'base' ? 'base' : 'bom';
    }

    function setDocumentPriceMode(mode) {
        window.documentEstimatePriceMode = mode === 'base' ? 'base' : 'bom';
    }

    function syncPriceModeButtons(scope, mode, optionSelector, helpSelector) {
        const activeMode = mode === 'base' ? 'base' : 'bom';
        const root = scope || document;
        root.querySelectorAll(optionSelector).forEach(function (button) {
            const buttonMode = button.dataset.priceModeOption || button.dataset.quickPriceModeOption || '';
            const isActive = buttonMode === activeMode;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        const help = root.querySelector(helpSelector);
        if (help) {
            help.textContent = '';
        }
    }

    function getQuickSelectValue(el) {
        if (!el) {
            return '';
        }
        if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
            return window.jQuery(el).val() || '';
        }
        return el.value || '';
    }

    function markQuickEstimateFieldInvalid(el, invalid) {
        if (!el) {
            return;
        }
        const $ = window.jQuery;
        if (invalid) {
            el.classList.add('is-invalid');
            if ($ && $(el).hasClass('select2-hidden-accessible')) {
                $(el).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        } else {
            el.classList.remove('is-invalid');
            if ($ && $(el).hasClass('select2-hidden-accessible')) {
                $(el).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }
        }
    }

    function clearQuickEstimateValidationState(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (field) {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('.select2-selection.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.quick-bom-make-error').forEach(function (el) {
            el.classList.remove('d-block');
        });
    }

    function updateQuickBomErrorVisibility(form) {
        const bomError = document.getElementById('invoice_quick_bom_id-error');
        if (!bomError || !form) {
            return;
        }
        const anyBom = Array.from(form.querySelectorAll('.quick-bom-select')).some(function (select) {
            return !!getQuickSelectValue(select);
        });
        if (anyBom) {
            bomError.style.display = 'none';
        }
    }

    function validateQuickEstimateBeforeBom() {
        const form = document.getElementById('quickEstimateForm');
        if (!form) {
            return true;
        }

        clearQuickEstimateValidationState(form);

        let isValid = true;
        const customerSelect = document.getElementById('quick_estimate_customer_id');
        const typeSelect = document.getElementById('quick_estimate_type');
        const quantity = parseFloat(form.quantity?.value || 0);
        const price = parseFloat(form.price?.value || 0);
        const templateSelect = document.getElementById('quick_template_id');

        if (!getQuickSelectValue(customerSelect)) {
            markQuickEstimateFieldInvalid(customerSelect, true);
            isValid = false;
        }
        if (!getQuickSelectValue(typeSelect)) {
            markQuickEstimateFieldInvalid(typeSelect, true);
            isValid = false;
        }
        if (!(quantity > 0)) {
            markQuickEstimateFieldInvalid(document.getElementById('quick_quantity'), true);
            isValid = false;
        }
        if (window.estimatePriceMode !== 'bom' && !(price > 0)) {
            markQuickEstimateFieldInvalid(document.getElementById('quick_price'), true);
            isValid = false;
        }
        if (!getQuickSelectValue(templateSelect)) {
            markQuickEstimateFieldInvalid(templateSelect, true);
            isValid = false;
        }

        if (!isValid && typeof window.showAlert === 'function') {
            window.showAlert('error', 'Please fill required Quick Estimate fields before adding BOM.');
        }

        return isValid;
    }

    function validateQuickEstimateWizardStep(step) {
        const form = document.getElementById('quickEstimateForm');
        if (!form) {
            return true;
        }

        clearQuickEstimateValidationState(form);

        if (step === 1) {
            let isValid = true;
            const customerSelect = document.getElementById('quick_estimate_customer_id');
            const typeSelect = document.getElementById('quick_estimate_type');
            const quantityEl = document.getElementById('quick_quantity');
            const priceEl = document.getElementById('quick_price');
            const templateSelect = document.getElementById('quick_template_id');

            if (!getQuickSelectValue(customerSelect)) {
                markQuickEstimateFieldInvalid(customerSelect, true);
                isValid = false;
            }
            if (!getQuickSelectValue(typeSelect)) {
                markQuickEstimateFieldInvalid(typeSelect, true);
                isValid = false;
            }
            if (!(parseFloat(quantityEl?.value || 0) > 0)) {
                markQuickEstimateFieldInvalid(quantityEl, true);
                isValid = false;
            }
            if (window.estimatePriceMode !== 'bom' && !(parseFloat(priceEl?.value || 0) > 0)) {
                markQuickEstimateFieldInvalid(priceEl, true);
                isValid = false;
            }
            if (!getQuickSelectValue(templateSelect)) {
                markQuickEstimateFieldInvalid(templateSelect, true);
                isValid = false;
            }
            return isValid;
        }

        if (step === 2) {
            let isValid = true;
            let selectedBomCount = 0;
            const bomError = document.getElementById('invoice_quick_bom_id-error');

            form.querySelectorAll('.quick-bom-row').forEach(function (row) {
                const select = row.querySelector('.quick-bom-select');
                const bomId = getQuickSelectValue(select);
                const qtyInput = row.querySelector('.quick-bom-qty');
                const priceInput = row.querySelector('.quick-bom-price');
                const makeSelect = row.querySelector('.quick-bom-make-select');

                if (!bomId) {
                    return;
                }

                selectedBomCount++;

                if (!(parseFloat(qtyInput?.value || 0) > 0)) {
                    markQuickEstimateFieldInvalid(qtyInput, true);
                    isValid = false;
                }
                if (parseFloat(priceInput?.value || 0) < 0) {
                    markQuickEstimateFieldInvalid(priceInput, true);
                    isValid = false;
                }
            });

            if (selectedBomCount === 0) {
                if (bomError) {
                    bomError.style.display = 'block';
                }
                markQuickEstimateFieldInvalid(form.querySelector('.quick-bom-select'), true);
                isValid = false;
            } else if (bomError) {
                bomError.style.display = 'none';
            }

            return isValid;
        }

        return true;
    }

    window.validateQuickEstimateBeforeBom = validateQuickEstimateBeforeBom;
    window.validateQuickEstimateWizardStep = validateQuickEstimateWizardStep;
    window.updateQuickBomErrorVisibility = updateQuickBomErrorVisibility;
    window.markQuickEstimateFieldInvalid = markQuickEstimateFieldInvalid;

    function restrictNegative(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === '-') {
                e.preventDefault();
            }
        });
        inputEl.addEventListener('input', function() {
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
    }

    function formatStepOneInputValue(value) {
        return String(Math.round(parseFloat(value || 0)));
    }

    function initDocumentForm() {
        if (documentFormInitialized) {
            return;
        }

        const config = resolveDocumentFormConfig();
        if (!config) {
            return;
        }

        const form = document.querySelector(config.formSelector);
        if (!form) {
            return;
        }

        documentFormInitialized = true;

        initBomHandlers();
        initCalculations();
        initEstimateSuggestions(form);
        initQuickAddBom();
        initDocumentNameFromCustomer();
        initTemplateCommentAutofill();
        initEstimatePriceModeSelector();
        applyEstimatePriceMode(form);

        const eventNs = config.eventNs || 'document';
        $('body').off('submit.' + eventNs).on('submit.' + eventNs, config.formSelector, function (e) {
            e.preventDefault();
            const $form = $(this);
            const btn = $form.find('button[type="submit"]');
            const originalText = btn.data('original-text') || btn.html();
                    btn.data('original-text', originalText);

            clearErrors($form);

            if (!runExactValidation($form)) {
                this.classList.add('was-validated');
                return;
            }

            const bomProducts = collectBomData();
            const formData = new FormData(this);
            const totalTaxRate = getSelectedTaxBreakdown(null).totalRate;
            formData.set('price_mode', getDocumentPriceMode());
            formData.set('products', JSON.stringify(bomProducts));
            formData.set('apply_gst', document.getElementById('apply_gst')?.checked ? '1' : '0');
            formData.set('gst', document.getElementById('apply_gst')?.checked ? totalTaxRate.toFixed(2) : '0');
            formData.set('total', document.getElementById('subtotal')?.value || '0');
            formData.set('final_total', document.getElementById('final_total')?.value || '0');
            formData.set('solar_structure_charges', document.getElementById('solar_structure_charges_check')?.checked ? (document.getElementById('solar_structure_charges')?.value || '0') : '0');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    Accept: 'application/json',
                },
                success: function (response) {
                    const activeConfig = getActiveDocumentFormConfig();
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || activeConfig.saveSuccessMessage, 'Success!');
                    }
                    setTimeout(function () {
                        window.location.href = response.redirect || activeConfig.defaultRedirect;
                    }, 300);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    const activeConfig = getActiveDocumentFormConfig();
                    const message = xhr.responseJSON?.message || activeConfig.saveErrorMessage;
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', message);
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                },
            });
        });

        $('body').off('input.' + eventNs + ' change.' + eventNs).on('input.' + eventNs + ' change.' + eventNs, config.formSelector + ' input, ' + config.formSelector + ' select, ' + config.formSelector + ' textarea', function () {
            const $field = $(this);
            $field.removeClass('is-invalid');
            const id = $field.attr('id');
            if (id) {
                $('#' + id + '-error').html('');
            }
            if ($field.closest('#bomContainer').length) {
                toggleBomError(false);
            }
            $field.siblings('.invalid-feedback.ajax-error').remove();
        });
    }

    function applyEstimatePriceMode(form) {
        const mode = getDocumentPriceMode();
        const selector = document.getElementById('estimate_price_mode_selector');
        if (selector) {
            selector.value = mode;
        }
        syncPriceModeButtons(document, mode, '[data-price-mode-option]', '[data-price-mode-help]');

        form.querySelectorAll('[name="price"]').forEach(function (field) {
            field.required = mode === 'base';
        });
        document.querySelectorAll('.estimate-base-price-col, .estimate-global-tax-col').forEach(function (el) {
            el.classList.toggle('d-none', mode !== 'base');
        });
        document.querySelectorAll('.estimate-bom-money-col').forEach(function (el) {
            el.classList.toggle('d-none', mode === 'base');
        });
        document.querySelectorAll('.estimate-charges-col').forEach(function (el) {
            el.classList.toggle('col-md-4', mode === 'base');
            el.classList.toggle('col-md-8', mode !== 'base');
        });
        if (form.classList.contains('ajax-invoice-form')) {
            const template = form.querySelector('#template_id')?.closest('.estimate-form-field-col');
            const row = form.querySelector('.estimate-charges-date-group > .row');
            if (template && row) {
                if (!form.estimateTemplateAnchor) {
                    form.estimateTemplateAnchor = document.createComment('Quotation template original position');
                    template.before(form.estimateTemplateAnchor);
                }
                if (mode === 'base') row.prepend(template);
                else form.estimateTemplateAnchor.after(template);

                [template, ...row.querySelectorAll('.estimate-charges-col, .estimate-global-tax-col, .estimate-created-date-col')].forEach(function (column) {
                    column.classList.toggle('col-xl-3', mode === 'base');
                    column.classList.toggle('col-md-6', mode === 'base');
                    column.classList.toggle('col-md-4', mode !== 'base' && !column.classList.contains('estimate-charges-col'));
                    if (column.classList.contains('estimate-charges-col')) {
                        column.classList.remove('col-md-4');
                        column.classList.toggle('col-md-8', mode !== 'base');
                    }
                });
            }
        }
        document.querySelectorAll('#bomContainer .bom-row-grid').forEach(function (grid) {
            grid.classList.toggle('estimate-base-price-grid', mode === 'base');
            grid.style.gridTemplateColumns = mode === 'base'
                ? 'minmax(180px, 2fr) minmax(130px, 1.2fr) minmax(90px, .7fr) minmax(70px, auto)'
                : '';
        });

        if (mode === 'bom') {
            const basePrice = form.querySelector('[name="price"]');
            if (basePrice) {
                if (parseFloat(basePrice.value || 0) > 0) {
                    basePrice.dataset.previousBasePrice = basePrice.value;
                }
                basePrice.value = '0';
            }
            form.querySelectorAll('.product-select').forEach(function (select) {
                const row = select.closest('.bom-row');
                const option = select.options[select.selectedIndex];
                const priceField = row?.querySelector('.product-price');
                const taxField = row?.querySelector('.product-tax-rate');
                if (priceField && select.value && parseFloat(priceField.value || 0) <= 0) {
                    priceField.value = formatStepOneInputValue(option?.dataset?.price || 0);
                }
                if (taxField && select.value && option?.dataset?.taxRate !== undefined && parseFloat(taxField.value || 0) <= 0) {
                    taxField.value = option.dataset.taxRate || '0';
                }
            });
            calculateTotals();
            return;
        }

        const savedPricing = window.estimateSavedPricing || {};
        const basePrice = form.querySelector('[name="price"]');
        if (basePrice) {
            const restoredBasePrice = basePrice.dataset.previousBasePrice
                ?? savedPricing.basePrice
                ?? '';
            if (restoredBasePrice !== undefined && restoredBasePrice !== null && restoredBasePrice !== '') {
                basePrice.value = String(restoredBasePrice);
            }
        }
        const globalTaxRate = document.getElementById('global_tax_rate');
        if (globalTaxRate && savedPricing.globalTaxRate !== undefined && savedPricing.globalTaxRate !== null) {
            globalTaxRate.value = String(savedPricing.globalTaxRate);
        }

        form.querySelectorAll('.product-price').forEach(function (field) {
            field.value = '0';
        });
        form.querySelectorAll('.product-tax-rate').forEach(function (field) {
            field.value = '0';
        });
        calculateTotals();
    }

    function initEstimateSuggestions(form) {
        if (!form.classList.contains('ajax-invoice-form')) return;
        const quantity = form.querySelector('[name="quantity"]');
        const price = form.querySelector('[name="price"]');
        if (!quantity || !price || quantity.dataset.suggestionsInit === '1') return;
        quantity.dataset.suggestionsInit = '1';

        function group(input, label) {
            const container = document.createElement('div');
            container.className = 'estimate-field-suggestions';
            container.setAttribute('role', 'group');
            container.setAttribute('aria-label', label);
            input.insertAdjacentElement('afterend', container);
            return container;
        }
        function button(input, value, label, title) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'estimate-suggestion';
            option.textContent = label;
            option.title = title || label;
            option.setAttribute('aria-label', title || label);
            option.addEventListener('click', function () {
                input.value = String(value);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
            });
            return option;
        }
        const quantities = group(quantity, 'Suggested system capacities');
        [3, 5, 7, 8, 10].forEach(value => quantities.appendChild(button(quantity, value, `${value} kW`)));
        const prices = group(price, 'Suggested total prices based on capacity');
        prices.setAttribute('aria-live', 'polite');
        let suggestionTimer;
        let suggestionRequest;
        let suggestionVersion = 0;
        function hint(text) {
            const message = document.createElement('span');
            message.className = 'small text-muted';
            message.textContent = text;
            prices.replaceChildren(message);
        }
        function updatePrices() {
            clearTimeout(suggestionTimer);
            suggestionRequest?.abort();
            const version = ++suggestionVersion;
            const kw = Number(quantity.value);
            if (!Number.isFinite(kw) || kw <= 0) {
                hint('Enter kW to see price suggestions.');
                return;
            }
            hint('Loading price suggestions...');
            suggestionTimer = setTimeout(async function () {
                suggestionRequest = new AbortController();
                try {
                    const url = new URL(form.dataset.priceSuggestionsUrl, window.location.origin);
                    url.searchParams.set('quantity', String(kw));
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                        signal: suggestionRequest.signal,
                    });
                    if (!response.ok) throw new Error('Price suggestions unavailable');
                    const payload = await response.json();
                    if (version !== suggestionVersion || Number(quantity.value) !== kw) return;
                    if (!payload.success || !Array.isArray(payload.suggestions)) throw new Error('Invalid suggestions');
                    prices.replaceChildren();
                    payload.suggestions.forEach(function (suggestion) {
                        const total = Number(suggestion.price);
                        const rate = Number(suggestion.rate_per_kw);
                        if (!Number.isFinite(total) || !Number.isFinite(rate)) return;
                        prices.appendChild(button(price, total, total.toLocaleString('en-IN'),
                            `Set price to ${total.toLocaleString('en-IN')} (${rate.toLocaleString('en-IN')} per kW)`));
                    });
                } catch (error) {
                    if (error.name !== 'AbortError' && version === suggestionVersion) {
                        hint('You can enter a price manually. Change kW to retry suggestions.');
                    }
                }
            }, 180);
        }
        quantity.addEventListener('input', updatePrices);
        quantity.addEventListener('change', updatePrices);
        updatePrices();
    }

    function initEstimatePriceModeSelector() {
        const selector = document.getElementById('estimate_price_mode_selector');
        const config = getActiveDocumentFormConfig();
        const form = document.querySelector(config.formSelector);
        if (!selector || !form || selector.dataset.priceModeInit === '1') {
            return;
        }

        selector.dataset.priceModeInit = '1';
        selector.value = getDocumentPriceMode();
        syncPriceModeButtons(document, getDocumentPriceMode(), '[data-price-mode-option]', '[data-price-mode-help]');
        selector.addEventListener('change', function () {
            setDocumentPriceMode(this.value);
            applyEstimatePriceMode(form);
            hideProductsError();
            form.querySelectorAll('.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });
        });
        document.querySelectorAll('[data-price-mode-option]').forEach(function (button) {
            button.addEventListener('click', function () {
                selector.value = this.dataset.priceModeOption === 'base' ? 'base' : 'bom';
                selector.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function initDocumentNameFromCustomer() {
        const config = getActiveDocumentFormConfig();
        const customerSelect = document.getElementById('select_customer');
        const nameInput = document.getElementById(config.nameField);

        if (!customerSelect || !nameInput) {
            return;
        }

        $(customerSelect).off('change.documentName').on('change.documentName', function () {
            const selectedOption = this.options[this.selectedIndex];
            const customerName = (selectedOption?.textContent || '').trim();

            if (!this.value || !customerName) {
                return;
            }

            nameInput.value = (config.namePrefix || '') + customerName;
            suggestDocumentName(customerSelect, nameInput, config.nameField === 'invoice_name' ? 'INV' : 'EST');
            nameInput.classList.remove('is-invalid');
            const error = document.getElementById(config.nameErrorId);
            if (error) {
                error.textContent = '';
            }
        });
    }

    let quickBomTargetRow = null;
    let quickEstimateBomTargetRow = null;
    let quickEstimateNestedModalActive = false;
    let quickAddBomInitialized = false;
    let documentFormInitialized = false;
    let bomHandlersInitialized = false;

    function setupQuickEstimateNestedModals() {
        const parentEl = document.getElementById('quickEstimateModal');
        if (!parentEl || parentEl.dataset.nestedModalsInit === '1') {
            return;
        }

        parentEl.dataset.nestedModalsInit = '1';
        parentEl.addEventListener('hide.bs.modal', function (event) {
            if (quickEstimateNestedModalActive) {
                event.preventDefault();
            }
        });

        ['invoiceAddCustomerModal', 'invoiceQuickAddBomModal', 'invoiceEditBomModal'].forEach(function (childId) {
            const childEl = document.getElementById(childId);
            if (!childEl) {
                return;
            }

            childEl.addEventListener('show.bs.modal', function () {
                quickEstimateNestedModalActive = true;
            });
            childEl.addEventListener('shown.bs.modal', function () {
                // Raise the most-recently-added backdrop above the Quick Estimate modal
                // so clicks inside this child modal are not swallowed by the parent backdrop.
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    backdrops[backdrops.length - 1].style.zIndex = '1062';
                }
            });
            childEl.addEventListener('hidden.bs.modal', function () {
                quickEstimateNestedModalActive = false;
                if (parentEl.classList.contains('show')) {
                    document.body.classList.add('modal-open');
                }
            });
        });
    }

    function openQuickEstimateChildModal(childId) {
        const childEl = document.getElementById(childId);
        if (!childEl || !window.bootstrap) {
            return;
        }

        setupQuickEstimateNestedModals();
        bootstrap.Modal.getOrCreateInstance(childEl, {
            backdrop: 'static',
            focus: true,
        }).show();
    }

    function initQuickAddBom() {
        if (quickAddBomInitialized) {
            return;
        }

        const form = document.getElementById('invoiceQuickAddBomForm');
        const saveBtn = document.getElementById('invoiceSaveQuickBomBtn');
        const formConfig = getActiveDocumentFormConfig();
        const config = window[formConfig.bomQuickAddConfigKey] || window.estimateBomQuickAddConfig || {};

        if (!form || !saveBtn || !config.storeUrl) {
            return;
        }

        quickAddBomInitialized = true;

        const nameInput = document.getElementById('invoice_quick_bom_name');
        const makeSelect = document.getElementById('invoice_quick_bom_category_id');
        const priceInput = document.getElementById('invoice_quick_bom_price');

        initQuickBomMakeSelect(makeSelect);

        // BOM Image preview for Add New BOM modal
        const bomImageInput = document.getElementById('invoice_quick_bom_image');
        if (bomImageInput) {
            bomImageInput.addEventListener('change', function () {
                const file = this.files[0];
                const preview = document.getElementById('invoice_quick_bom_image_preview');
                const thumb = document.getElementById('invoice_quick_bom_image_thumb');
                if (file) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/jfif', 'image/pjpeg'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (!allowedTypes.includes(file.type)) {
                        if (typeof window.showAlert === 'function') window.showAlert('error', 'Only JPG, PNG, JPEG images are allowed.');
                        this.value = '';
                        if (preview) preview.style.display = 'none';
                        return;
                    }
                    if (file.size > maxSize) {
                        if (typeof window.showAlert === 'function') window.showAlert('error', 'Image must be under 5MB.');
                        this.value = '';
                        if (preview) preview.style.display = 'none';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (thumb) thumb.src = e.target.result;
                        if (preview) preview.style.display = '';
                    };
                    reader.readAsDataURL(file);
                } else {
                    if (preview) preview.style.display = 'none';
                }
            });
        }

        document.addEventListener('click', function (event) {
            const quickEstimateBtn = event.target.closest('.quick-estimate-add-bom-btn');
            const estimateBtn = event.target.closest('.quick-add-bom-row');

            if (quickEstimateBtn) {
                if (typeof window.validateQuickEstimateBeforeBom === 'function' && !window.validateQuickEstimateBeforeBom()) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    return;
                }

                quickBomTargetRow = null;
                quickEstimateBomTargetRow = quickEstimateBtn.closest('.quick-bom-row');
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                openQuickEstimateChildModal('invoiceQuickAddBomModal');
                return;
            }

            if (!estimateBtn) {
                return;
            }

            if (!validateTopFieldsBeforeBom(true)) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                return;
            }

            quickEstimateBomTargetRow = null;
            quickBomTargetRow = estimateBtn.closest('.bom-row');
        }, true);

        [nameInput, makeSelect, priceInput].forEach(function (field) {
            if (!field) {
                return;
            }

            const eventName = field.tagName === 'SELECT' ? 'change' : 'input';
            field.addEventListener(eventName, function () {
                field.classList.remove('is-invalid');
                const error = document.getElementById(field.id + '-error');
                if (error) {
                    error.textContent = '';
                }
            });
        });

        saveBtn.addEventListener('click', function () {
            const name = (nameInput?.value || '').trim();
            const price = parseFloat(priceInput?.value || 0);
            const description = document.getElementById('invoice_quick_bom_description')?.value || '';
            const taxRate = document.getElementById('invoice_quick_bom_tax_rate')?.value || '0';
            let isValid = true;

            if (!name) {
                showQuickBomFieldError(nameInput, 'Please enter BOM name');
                isValid = false;
            }

            if (!(price >= 0) || priceInput?.value === '') {
                showQuickBomFieldError(priceInput, 'Please enter unit price');
                isValid = false;
            }

            if (!isValid) {
                // Scroll the first invalid field into view so the user sees the error
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus({ preventScroll: true });
                }
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            resolveQuickBomMake(makeSelect, config)
                .then(function (make) {
                    const formData = new FormData();
                    formData.append('product_name', name);
                    formData.append('price', formatStepOneInputValue(price));
                    formData.append('description', description);
                    formData.append('hsn_sac', document.getElementById('invoice_quick_bom_hsn_sac')?.value || '');
                    ['technology_id','warranty_id','height','fitting_material','fitting_type','thickness','size_of_pipe','capacity','meter','nos'].forEach(function (field) { formData.append(field, document.getElementById('invoice_quick_bom_' + field)?.value || ''); });
                    if (taxRate !== '0') {
                        formData.append('tax_rate', taxRate);
                    }
                    if (make?.id) {
                        formData.append('category_id[]', make.id);
                    }
                    // Append BOM image if selected
                    const bomImgInput = document.getElementById('invoice_quick_bom_image');
                    if (bomImgInput && bomImgInput.files && bomImgInput.files[0]) {
                        formData.append('image', bomImgInput.files[0]);
                    }

                    return fetch(config.storeUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        credentials: 'same-origin',
                    }).then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw payload;
                            }
                            return { payload, make };
                        });
                    });
                })
                .then(function (result) {
                    const payload = result.payload;
                    const product = payload.data;
                    if (!product) {
                        throw { message: 'BOM created but response was invalid.' };
                    }

                    addBomProductToEstimateRows(product, result.make);
                    form.reset();
                    // Clear BOM image preview
                    const imgPreview = document.getElementById('invoice_quick_bom_image_preview');
                    const imgThumb = document.getElementById('invoice_quick_bom_image_thumb');
                    if (imgPreview) imgPreview.style.display = 'none';
                    if (imgThumb) imgThumb.src = '';
                    if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
                        $(makeSelect).val(null).trigger('change');
                    }
                    const modal = bootstrap.Modal.getInstance(document.getElementById('invoiceQuickAddBomModal'));
                    if (modal) {
                        modal.hide();
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || 'BOM added successfully.');
                    }
                })
                .catch(function (error) {
                    if (error?.errors) {
                        showQuickBomApiErrors(error.errors);
                        return;
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', error?.message || 'Unable to add BOM.');
                    } else {
                        alert(error?.message || 'Unable to add BOM.');
                    }
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                });
        });
    }

    function initQuickBomMakeSelect(makeSelect) {
        if (!makeSelect || !$.fn.select2) {
            return;
        }

        const modal = $(makeSelect).closest('.modal');
        const $makeSelect = $(makeSelect);

        // This field may already have been initialized by the page-wide Select2
        // setup. Reinitialize it so tagging (creating a new Make) is enabled.
        if ($makeSelect.hasClass('select2-hidden-accessible')) {
            $makeSelect.select2('destroy');
        }

        $makeSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: modal.length ? modal : $(document.body),
            tags: true,
            placeholder: 'Search or type new Make',
            createTag: function (params) {
                const term = $.trim(params.term);
                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true,
                };
            },
            insertTag: function (data, tag) {
                data.unshift(tag);
            },
            templateResult: function (data) {
                if (data.newTag) {
                    return $('<span>Add new make: <strong></strong></span>').find('strong').text(data.text).end();
                }

                return data.text;
            },
        });
    }

    function resolveQuickBomMake(makeSelect, config) {
        const selectedOption = makeSelect?.options[makeSelect.selectedIndex];
        const selectedValue = makeSelect?.value || '';
        const isExistingMake = selectedValue !== '' && /^\d+$/.test(String(selectedValue));

        if (isExistingMake) {
            return Promise.resolve({
                id: selectedValue,
                name: selectedOption?.textContent?.trim() || '',
            });
        }

        const makeName = selectedOption?.textContent?.trim() || selectedValue.trim();
        if (!makeName) {
            return Promise.resolve(null);
        }

        if (!config.makeStoreUrl) {
            return Promise.reject({ message: 'Make create URL is not configured.' });
        }

        const formData = new FormData();
        formData.append('name', makeName);

        return fetch(config.makeStoreUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok) {
                    throw payload;
                }

                addCreatedMakeToQuickBomSelect(makeSelect, payload.data);
                return payload.data;
            });
        });
    }

    function addCreatedMakeToQuickBomSelect(makeSelect, make) {
        if (!makeSelect || !make) {
            return;
        }

        let option = makeSelect.querySelector('option[value="' + make.id + '"]');
        if (!option) {
            option = new Option(make.name, make.id, true, true);
            option.dataset.name = make.name;
            makeSelect.appendChild(option);
        }

        option.selected = true;
        if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
            $(makeSelect).trigger('change');
        }
    }

    function showQuickBomFieldError(field, message) {
        if (!field) {
            return;
        }

        field.classList.add('is-invalid');
        const error = document.getElementById(field.id + '-error');
        if (error) {
            error.textContent = message;
        }
    }

    function showQuickBomApiErrors(errors) {
        const fieldMap = {
            product_name: 'invoice_quick_bom_name',
            category_id: 'invoice_quick_bom_category_id',
            name: 'invoice_quick_bom_category_id',
            price: 'invoice_quick_bom_price',
        };

        Object.keys(errors).forEach(function (field) {
            const fieldId = fieldMap[field] || fieldMap[field.replace(/\.\d+$/, '')];
            const input = fieldId ? document.getElementById(fieldId) : null;
            showQuickBomFieldError(input, errors[field][0] || 'Invalid value');
        });
    }

    function addBomProductToEstimateRows(product, selectedMake) {
        const categories = Array.isArray(product.categories) ? product.categories : [];
        let categoryNames = categories.map(function (category) {
            return category.name;
        }).filter(Boolean);
        const selectedMakeName = selectedMake?.name || '';
        if (!categoryNames.length && selectedMakeName) {
            categoryNames = [selectedMakeName];
        }
        const price = formatStepOneInputValue(product.price || 0);

        document.querySelectorAll('.product-select').forEach(function (select) {
            let option = select.querySelector('option[value="' + product.id + '"]');
            if (!option) {
                option = new Option(product.product_name || 'New BOM', product.id, false, false);
                select.appendChild(option);
            }

            option.dataset.name = product.product_name || '';
            option.dataset.desc = product.description || '';
            option.dataset.hsn = product.hsn_sac || product.hsn || '';
            option.dataset.categories = JSON.stringify(categoryNames);
            option.dataset.price = price;
            option.dataset.meter = product.meter || '';
            option.dataset.nos = product.nos || '';
        });

        document.querySelectorAll('.quick-bom-select').forEach(function (select) {
            let option = select.querySelector('option[value="' + product.id + '"]');
            if (!option) {
                option = new Option(product.product_name || 'New BOM', product.id, false, false);
                select.appendChild(option);
            }

            option.dataset.name = product.product_name || '';
            option.dataset.price = price;
            option.dataset.categories = JSON.stringify(categoryNames);
        });

        const quickRow = getQuickEstimateBomTargetRow();
        if (quickRow) {
            const productSelect = quickRow.querySelector('.quick-bom-select');

            if (productSelect) {
                productSelect.value = String(product.id);
                if (typeof window.syncQuickEstimateBomRow === 'function') {
                    window.syncQuickEstimateBomRow(quickRow, selectedMakeName);
                } else if (window.jQuery) {
                    window.jQuery(productSelect).trigger('change');
                } else {
                    productSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            quickEstimateBomTargetRow = null;
            return;
        }

        const row = getQuickBomTargetRow();
        const productSelect = row?.querySelector('.product-select');
        const makeSelect = row?.querySelector('.product-make');
        const priceInput = row?.querySelector('.product-price');

        if (!row || !productSelect) {
            return;
        }

        productSelect.value = String(product.id);
        if (priceInput) {
            priceInput.value = price;
        }

        $(productSelect).trigger('change');
        if (makeSelect && selectedMakeName) {
            makeSelect.value = selectedMakeName;
            if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
                $(makeSelect).trigger('change.select2');
            }
            $(makeSelect).trigger('change');
        }
        quickBomTargetRow = null;
        calculateTotals();
    }

    function getQuickBomTargetRow() {
        const container = document.getElementById('bomContainer');
        if (!container) {
            return null;
        }

        if (quickBomTargetRow && container.contains(quickBomTargetRow)) {
            return quickBomTargetRow;
        }

        const emptyRow = Array.from(container.querySelectorAll('.bom-row')).find(function (row) {
            const select = row.querySelector('.product-select');
            return select && !select.value;
        });

        if (emptyRow) {
            return emptyRow;
        }

        const addBtn = document.getElementById('add_more_bom');
        if (addBtn) {
            addBtn.click();
        }

        const rows = container.querySelectorAll('.bom-row');
        return rows[rows.length - 1] || null;
    }

    function getQuickEstimateBomTargetRow() {
        const container = document.getElementById('quickBomRows');
        if (!container) {
            return null;
        }

        if (quickEstimateBomTargetRow && container.contains(quickEstimateBomTargetRow)) {
            return quickEstimateBomTargetRow;
        }

        const emptyRow = Array.from(container.querySelectorAll('.quick-bom-row')).find(function (row) {
            const select = row.querySelector('.quick-bom-select');
            return select && !select.value;
        });

        if (emptyRow) {
            return emptyRow;
        }

        const addBtn = document.getElementById('quickAddBomRow');
        if (addBtn) {
            addBtn.click();
        }

        const rows = container.querySelectorAll('.quick-bom-row');
        return rows[rows.length - 1] || null;
    }

    function setProductsErrorMessage(message) {
        const defaultMessage = 'Please select at least one BOM.';
        const nextMessage = message || defaultMessage;
        const textEl = document.querySelector('#products-error .products-error-text');
        if (textEl) {
            textEl.textContent = nextMessage;
            return;
        }
        if (window.jQuery) {
            window.jQuery('#products-error').text(nextMessage);
        }
    }

    function hideProductsError() {
        if (window.jQuery) {
            window.jQuery('#products-error').removeClass('d-block').hide();
        } else {
            const el = document.getElementById('products-error');
            if (el) {
                el.style.display = 'none';
            }
        }
    }

    function showProductsError(message) {
        setProductsErrorMessage(message);
        if (window.jQuery) {
            window.jQuery('#products-error').addClass('d-block').show();
        } else {
            const el = document.getElementById('products-error');
            if (el) {
                el.style.display = 'block';
            }
        }
    }

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        setProductsErrorMessage('Please select at least one BOM.');
        hideProductsError();
    }

    function showErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            if (field === 'products') {
                showProductsError(errors[field][0]);
                return;
            }

            const $field = $form.find(`[name="${field}"]`);
            if ($field.length) {
                $field.addClass('is-invalid');
                const feedback = $form.find('#' + field + '-error');
                if (feedback.length) {
                    feedback.html(errors[field][0]);
                } else {
                    $field.after(`<div class="invalid-feedback ajax-error">${errors[field][0]}</div>`);
                }
            }
        });
    }

    function setFieldError($form, selector, feedbackId, message) {
        const $field = $form.find(selector);
        if ($field.length) {
            $field.addClass('is-invalid');
        }
        const $feedback = $('#' + feedbackId);
        if ($feedback.length) {
            $feedback.text(message).show();
        }
    }

    function markEstimateBomFieldInvalid(el, invalid) {
        if (!el) {
            return;
        }

        if (invalid) {
            el.classList.add('is-invalid');
            if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
                window.jQuery(el).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        } else {
            el.classList.remove('is-invalid');
            if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
                window.jQuery(el).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }
        }
    }

    function clearEstimateBomRowValidation(row) {
        if (!row) {
            return;
        }

        row.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        row.querySelectorAll('.select2-selection.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        row.querySelectorAll('.bom-make-error').forEach(function (el) {
            el.classList.remove('d-block');
        });
    }

    function validateEstimateBomRows(options) {
        options = options || {};
        let isValid = true;
        let selectedBomCount = 0;
        const rows = document.querySelectorAll('#bomContainer .bom-row');

        if (options.clearFirst !== false) {
            rows.forEach(clearEstimateBomRowValidation);
            hideProductsError();
        }

        rows.forEach(function (row) {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');
            const priceInput = row.querySelector('.product-price');

            if (!productSelect?.value) {
                return;
            }

            selectedBomCount++;

            if (!(parseFloat(qtyInput?.value || 0) > 0)) {
                markEstimateBomFieldInvalid(qtyInput, true);
                isValid = false;
            }
            if (parseFloat(priceInput?.value || 0) < 0) {
                markEstimateBomFieldInvalid(priceInput, true);
                isValid = false;
            }
        });

        if (selectedBomCount === 0) {
            showProductsError('Please select at least one BOM.');
            markEstimateBomFieldInvalid(document.querySelector('#bomContainer .product-select'), true);
            isValid = false;
        }

        return { isValid: isValid, selectedBomCount: selectedBomCount };
    }

    window.validateEstimateBomRows = validateEstimateBomRows;
    window.markEstimateBomFieldInvalid = markEstimateBomFieldInvalid;

    function runExactValidation($form) {
        const config = getActiveDocumentFormConfig();
        let isValid = true;

        const customerId = ($form.find('[name="customer_id"]').val() || '').trim();
        const documentName = ($form.find('[name="' + config.nameField + '"]').val() || '').trim();
        const type = ($form.find('[name="type"]').val() || '').trim();
        const quantity = parseFloat($form.find('[name="quantity"]').val() || 0);
        const price = parseFloat($form.find('[name="price"]').val() || 0);
        const solarMeterCharges = ($form.find('[name="solar_meter_charges"]').val() || '').trim();
        const templateId = ($form.find('[name="template_id"]').val() || '').trim();
        const currencyId = ($form.find('[name="currency_id"]').val() || '').trim();
        const bomProducts = collectBomData();

        if (!customerId) {
            setFieldError($form, '[name="customer_id"]', 'customer_id-error', 'Please select a customer');
            isValid = false;
        }

        if (!documentName) {
            setFieldError($form, '[name="' + config.nameField + '"]', config.nameErrorId, 'Please enter ' + config.nameLabel);
            isValid = false;
        }

        if (config.requireCurrency && !currencyId) {
            setFieldError($form, '[name="currency_id"]', 'currency_id-error', 'Please select currency');
            isValid = false;
        }

        if (!type) {
            setFieldError($form, '[name="type"]', 'type-error', config.typeErrorMessage || 'Please select type');
            isValid = false;
        }

        if (!(quantity > 0)) {
            setFieldError($form, '[name="quantity"]', 'quantity-error', 'Please enter valid quantity (kW)');
            isValid = false;
        }

        if (getDocumentPriceMode() !== 'bom' && !(price > 0)) {
            setFieldError($form, '[name="price"]', 'price-error', 'Please enter valid price');
            isValid = false;
        }

        if (!solarMeterCharges) {
            setFieldError($form, '[name="solar_meter_charges"]', 'solar_meter_charges-error', 'Please select solar meter charges');
            isValid = false;
        }

        if (!templateId) {
            setFieldError($form, '[name="template_id"]', 'template_id-error', 'Please select invoice format');
            isValid = false;
        }

        const bomValidation = validateEstimateBomRows();
        if (!bomValidation.isValid) {
            isValid = false;
        }

        return isValid;
    }

    function htmlToPlainText(html) {
        const holder = document.createElement('div');
        holder.innerHTML = String(html || '')
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/p>/gi, '\n')
            .replace(/<\/div>/gi, '\n');
        return (holder.textContent || holder.innerText || '')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function initTemplateCommentAutofill() {
        const templateSelect = document.getElementById('template_id');
        const commentField = document.getElementById('comment');
        const updateTemplateCommentField = document.getElementById('update_template_comment');
        const editTemplateCommentBtn = document.getElementById('edit_template_comment_btn');
        const formConfig = getActiveDocumentFormConfig();
        const templates = window[formConfig.templateCommentsKey] || window.estimateTemplateComments || {};

        if (!templateSelect || !commentField) {
            return;
        }

        const updateCommentEditButton = function () {
            if (!editTemplateCommentBtn) {
                return;
            }

            if (templateSelect.value) {
                editTemplateCommentBtn.classList.remove('d-none');
            } else {
                editTemplateCommentBtn.classList.add('d-none');
                if (updateTemplateCommentField) {
                    updateTemplateCommentField.value = '0';
                }
                editTemplateCommentBtn.textContent = 'Edit';
                editTemplateCommentBtn.classList.remove('fw-semibold');
            }
        };

        const resetTemplateCommentEditState = function () {
            if (updateTemplateCommentField) {
                updateTemplateCommentField.value = '0';
            }
            if (editTemplateCommentBtn) {
                editTemplateCommentBtn.textContent = 'Edit';
                editTemplateCommentBtn.classList.remove('fw-semibold');
            }
        };

        const fillFromSelectedTemplate = function (overwrite) {
            const config = templates[String(templateSelect.value)] || {};
            updateCommentEditButton();
            resetTemplateCommentEditState();

            if (parseInt(config.active || 0, 10) !== 1) {
                return;
            }

            const commentText = htmlToPlainText(config.content || '');
            if (commentText && (overwrite || !commentField.value.trim())) {
                commentField.value = commentText;
                commentField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        if (editTemplateCommentBtn) {
            editTemplateCommentBtn.addEventListener('click', function () {
                if (!templateSelect.value) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select invoice format first.');
                    }
                    return;
                }

                if (updateTemplateCommentField) {
                    updateTemplateCommentField.value = '1';
                }
                editTemplateCommentBtn.textContent = 'Editing';
                editTemplateCommentBtn.classList.add('fw-semibold');
                commentField.focus();
                commentField.select();
            });
        }

        templateSelect.addEventListener('change', function () {
            fillFromSelectedTemplate(true);
        });

        if (window.jQuery) {
            window.jQuery(templateSelect).on('change select2:select', function () {
                fillFromSelectedTemplate(true);
            });
        }

        updateCommentEditButton();
        fillFromSelectedTemplate(false);
    }

    function validateTopFieldsBeforeBom(showErrors) {
        // Estimates can be filled in any order; validate required fields on submit.
        if (document.querySelector('#invoiceCreateForm, #invoiceEditForm')) {
            return true;
        }
        const config = getActiveDocumentFormConfig();
        const $form = $(config.formSelector).first();
        if (!$form.length) {
            return true;
        }

        let isValid = true;
        const quantity = parseFloat($form.find('[name="quantity"]').val() || 0);
        const price = parseFloat($form.find('[name="price"]').val() || 0);
        const requiredFields = [
            ['[name="customer_id"]', 'customer_id-error', 'Please select a customer', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="' + config.nameField + '"]', config.nameErrorId, 'Please enter ' + config.nameLabel, function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="type"]', 'type-error', config.typeErrorMessage || 'Please select type', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="quantity"]', 'quantity-error', 'Please enter valid quantity (kW)', function () { return quantity > 0; }],
            ['[name="solar_meter_charges"]', 'solar_meter_charges-error', 'Please select solar meter charges', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="template_id"]', 'template_id-error', 'Please select invoice format', function ($field) { return !!($field.val() || '').trim(); }],
        ];

        if (getDocumentPriceMode() !== 'bom') {
            requiredFields.splice(4, 0, ['[name="price"]', 'price-error', 'Please enter valid price', function () { return price > 0; }]);
        }

        if (config.requireCurrency) {
            requiredFields.splice(2, 0, [
                '[name="currency_id"]',
                'currency_id-error',
                'Please select currency',
                function ($field) { return !!($field.val() || '').trim(); },
            ]);
        }

        requiredFields.forEach(function (fieldConfig) {
            const $field = $form.find(fieldConfig[0]);
            if (!fieldConfig[3]($field)) {
                isValid = false;
                if (showErrors) {
                    setFieldError($form, fieldConfig[0], fieldConfig[1], fieldConfig[2]);
                }
            }
        });

        if (!isValid && showErrors) {
            const config = getActiveDocumentFormConfig();
            showProductsError(config.bomPrereqMessage || 'Please fill required details before adding BOM');
        }

        return isValid;
    }

    function toggleBomError(forceShow) {
        const bomError = window.jQuery ? window.jQuery('#products-error') : null;
        if (!bomError?.length && !document.getElementById('products-error')) {
            return;
        }

        if (forceShow === true) {
            showProductsError('Please select at least one BOM.');
            return;
        }

        if (collectBomData().length > 0) {
            hideProductsError();
        }
    }

    function initBomHandlers() {
        if (bomHandlersInitialized) {
            return;
        }

        const addBtn = document.getElementById('add_more_bom');
        const container = document.getElementById('bomContainer');
        if (!addBtn || !container) {
            return;
        }

        bomHandlersInitialized = true;
        $(container).on('select2:open.bomSearchFocus', '.product-select', function () {
            const select = this;
            // Wait until Select2 has displayed its dropdown and finished moving focus.
            setTimeout(function () {
                const instance = $(select).data('select2');
                if (!instance?.isOpen()) return;
                const search = instance.$dropdown?.find('.select2-search__field')[0];
                search?.focus({ preventScroll: true });
            }, 0);
        });
        const bomSearch = document.getElementById('estimateBomSearch');
        let searchTimer;
        if (bomSearch) {
            const filterBomRows = () => {
                const query = bomSearch.value.trim().toLocaleLowerCase();
                let matches = 0;
                container.querySelectorAll('.bom-row').forEach(row => {
                    const select = row.querySelector('.product-select');
                    const option = select?.selectedOptions[0];
                    const name = select?.value ? (option?.dataset.name || option?.textContent || '') : '';
                    const visible = !query || name.toLocaleLowerCase().includes(query);
                    row.classList.toggle('d-none', !visible);
                    if (visible) matches++;
                });
                document.getElementById('estimateBomSearchEmpty')?.classList.toggle('d-none', !query || matches > 0);
            };
            bomSearch.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => { filterBomRows(); container.scrollTop = 0; }, 120);
            });
            $(container).on('change', '.product-select', filterBomRows);
            new MutationObserver(filterBomRows).observe(container, { childList: true, subtree: true, characterData: true });
        }
        let prefillingBomRows = false;

        // Hydrate initial rows
        container.querySelectorAll('.bom-row').forEach(function (row) {
            hydrateBomRow(row);
        });

        // 1. Qty, Price, Tax Rate change/input -> Update calculations
        $(container).on('input change', 'input[name="product_qty[]"], .product-price, .product-tax-rate', function () {
            toggleBomError(false);
            calculateTotals();
        });

        // 2. Product select change -> populate makes, update units/price/calculations
        $(container).on('change', '.product-select', function () {
            const row = this.closest('.bom-row');
            const makeSelect = row.querySelector('.product-make');
            const priceInput = row.querySelector('.product-price');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');

            if (this.value && !prefillingBomRows && !validateTopFieldsBeforeBom(true)) {
                this.value = '';
                if ($.fn.select2 && $(this).hasClass('select2-hidden-accessible')) {
                    $(this).trigger('change.select2');
                }
                return;
            }

            populateMakeOptions(this, makeSelect, '');
            markEstimateBomFieldInvalid(makeSelect, false);
            row.querySelector('.bom-make-error')?.classList.remove('d-block');

            const option = this.querySelector('option[value="' + this.value + '"]');
            const description = row.querySelector('.product-description');
            if (description) description.value = this.value ? (option?.dataset.desc || '') : '';
            const hsnInput = row.querySelector('.product-hsn-sac');
            if (hsnInput) hsnInput.value = this.value ? (option?.dataset.hsn || '') : '';
            let labelText = 'Qty';
            if (option && this.value) {
                if (priceInput) {
                    priceInput.value = getDocumentPriceMode() === 'base'
                        ? '0'
                        : formatStepOneInputValue(option.dataset.price || 0);
                }
                const taxSelect = row.querySelector('.product-tax-rate');
                if (taxSelect && getDocumentPriceMode() !== 'base' && option.dataset.taxRate !== undefined) {
                    const rawTaxRate = parseFloat(option.dataset.taxRate || 0);
                    let found = false;
                    for (let i = 0; i < taxSelect.options.length; i++) {
                        const optVal = parseFloat(taxSelect.options[i].value || 0);
                        if (Math.abs(optVal - rawTaxRate) < 0.001) {
                            taxSelect.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        taxSelect.value = '0';
                    }
                }

                const meter = option.dataset.meter;
                const nos = option.dataset.nos;
                const hasMeter = meter && String(meter).trim() !== '' && String(meter).toLowerCase() !== 'null';
                const hasNos = nos && String(nos).trim() !== '' && String(nos).toLowerCase() !== 'null';

                if (hasMeter) {
                    labelText = 'Qty <small class="bom-qty-unit">(mt)</small>';
                } else if (hasNos) {
                    labelText = 'Qty <small class="bom-qty-unit">(nos)</small>';
                }
            }

            const label = row.querySelector('.product-qty-label') || qtyInput?.closest('div')?.querySelector('label');
            if (label) {
                const icon = label.querySelector('i');
                if (icon) {
                    label.innerHTML = '';
                    label.appendChild(icon);
                    label.insertAdjacentHTML('beforeend', ' ' + labelText);
                    label.insertAdjacentHTML('beforeend', ' <span class="text-danger">*</span>');
                } else {
                    label.innerHTML = labelText + ' <span class="text-danger">*</span>';
                }
            }

            toggleBomError(false);
            calculateTotals();
        });

        // Prevent opening BOM select if top fields are invalid
        $(container).on('select2:opening', '.product-select', function (event) {
            if (!validateTopFieldsBeforeBom(true)) {
                event.preventDefault();
            }
        });

        // 3. Make select change
        $(container).on('change select2:select', '.product-make', function () {
            const row = this.closest('.bom-row');
            markEstimateBomFieldInvalid(this, false);
            row.querySelector('.bom-make-error')?.classList.remove('d-block');
            toggleBomError(false);
            calculateTotals();
        });

        // 4. Delete row
        $(container).on('click', '.delete-bom-row', function () {
            const row = this.closest('.bom-row');
            if (container.querySelectorAll('.bom-row').length > 1) {
                row.remove();
                toggleBomError(false);
                calculateTotals();
            } else {
                $(row.querySelector('.product-select')).val('').trigger('change');
                row.querySelector('.product-price').value = '0';
                row.querySelector('.product-tax-rate').value = '0';
                calculateTotals();
            }
        });

        // 5. Restrict negative inputs
        $(container).on('keydown', 'input[type="number"]', function(e) {
            if (e.key === '-') {
                e.preventDefault();
            }
        });
        $(container).on('input', 'input[type="number"]', function() {
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });

        // Add More BOM button click
        $(addBtn).off('click').on('click', function () {
            if (!validateTopFieldsBeforeBom(true)) {
                return;
            }

            const firstRow = container.querySelector('.bom-row');
            if (!firstRow) {
                return;
            }

            const newRow = firstRow.cloneNode(true);
            delete newRow.dataset.bomSpecifications;
            newRow.classList.remove('bom-without-make', 'd-none');
            // A search must not hide the newly added, unselected row.
            clearTimeout(searchTimer);
            if (bomSearch) bomSearch.value = '';
            const description = newRow.querySelector('.product-description');
            if (description) description.value = '';
            const hsnInput = newRow.querySelector('.product-hsn-sac');
            if (hsnInput) hsnInput.value = '';
            clearEstimateBomRowValidation(newRow);
            newRow.querySelectorAll('input, select').forEach(function (el) {
                if (el.tagName === 'SELECT') {
                    el.value = el.classList.contains('product-tax-rate') ? '0' : '';
                    if (el.classList.contains('product-make')) {
                        el.closest('div').hidden = false;
                        el.innerHTML = '<option value="">Select Make</option>';
                        el.disabled = true;
                        el.dataset.selected = '';
                    }
                } else if (el.type === 'number') {
                    el.value = '0';
                }
            });
            newRow.querySelectorAll('input[name="product_qty[]"]').forEach(function (input) {
                input.value = '1';
                input.placeholder = 'Add Quantity';
            });

            const deleteBtn = newRow.querySelector('.delete-bom-row');
            if (deleteBtn) {
                deleteBtn.style.display = 'block';
            }

            container.appendChild(newRow);
            // Let the row observer finish initializing Select2 before opening it.
            requestAnimationFrame(function () {
                const select = newRow.querySelector('.product-select');
                const focusTarget = $(select).next('.select2-container').find('.select2-selection')[0] || select;
                focusTarget?.focus({ preventScroll: true });
                newRow.scrollIntoView({
                    // Select2 locks ancestor scrolling while open, so finish scrolling first.
                    behavior: 'instant',
                    block: 'center',
                    inline: 'nearest'
                });
                if ($.fn.select2 && $(select).data('select2')) {
                    $(select).select2('open');
                }
            });
        });

        if (container.dataset.prefillAll === 'true') {
            const firstRow = container.querySelector('.bom-row');
            const template = firstRow.cloneNode(true);
            const productIds = Array.from(firstRow.querySelector('.product-select').options)
                .map(option => option.value).filter(Boolean);
            prefillingBomRows = true;
            productIds.forEach(function (id, index) {
                const row = index === 0 ? firstRow : template.cloneNode(true);
                if (index > 0) container.appendChild(row);
                row.querySelector('.delete-bom-row').style.display = 'block';
                row.querySelector('.delete-bom-row').setAttribute('aria-label', 'Remove BOM item');
                $(row.querySelector('.product-select')).val(id).trigger('change');
                const make = row.querySelector('.product-make');
                if (make.options.length > 1) make.selectedIndex = 1;
            });
            prefillingBomRows = false;
            calculateTotals();
        }
    }

    function attachBomRowHandlers(row) {
        // Obsolete due to event delegation
    }

    function hydrateBomRow(row) {
        const productSelect = row.querySelector('.product-select');
        const makeSelect = row.querySelector('.product-make');
        if (!productSelect || !makeSelect || !productSelect.value) {
            return;
        }

        populateMakeOptions(productSelect, makeSelect, makeSelect.dataset.selected || '');
    }

    function populateMakeOptions(productSelect, makeSelect, selectedValue) {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const productName = (selectedOption?.dataset.name || selectedOption?.textContent || '').trim().toUpperCase();
        const hideMake = ['SUPPLY AND INSTALLATION', 'STRUCTURE FABRICATION WORK'].includes(productName);
        makeSelect.closest('div').hidden = hideMake;
        productSelect.closest('.bom-row')?.classList.toggle('bom-without-make', hideMake);
        const categories = selectedOption?.dataset?.categories;
        makeSelect.innerHTML = '<option value="">Select Make</option>';

        if (hideMake) {
            makeSelect.disabled = true;
            return;
        }

        if (!categories) {
            makeSelect.disabled = true;
            return;
        }

        try {
            const categoryList = JSON.parse(categories);
            categoryList.forEach(function (cat) {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                if (selectedValue && selectedValue === cat) {
                    option.selected = true;
                }
                makeSelect.appendChild(option);
            });
            makeSelect.disabled = false;
        } catch (error) {
            makeSelect.disabled = true;
        }

        if ($(makeSelect).hasClass('select2-hidden-accessible')) {
            $(makeSelect).trigger('change.select2');
        }
    }

    let isPriceManualOverride = false;

    function collectBomData() {
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        const products = [];

        rows.forEach(function (row) {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');
            const priceInput = row.querySelector('.product-price');
            const taxSelect = row.querySelector('.product-tax-rate');

            if (productSelect && productSelect.value) {
                const option = productSelect.options[productSelect.selectedIndex];

                let itemPrice = parseFloat(option.dataset.price || 0);
                if (priceInput && priceInput.value !== '') {
                    itemPrice = parseFloat(priceInput.value || 0);
                }

                products.push({
                    ...(window.getEstimateBomSpecifications?.(row, row.querySelector('.product-select, .quick-bom-select')?.value) || {}),
                    product_id: productSelect.value,
                    hsn_sac: row.querySelector('.product-hsn-sac')?.value ?? option.dataset.hsn ?? '',
                    name: option.dataset.name || '',
                    description: row.querySelector('.product-description')?.value ?? option.dataset.desc ?? '',
                    category_name: makeSelect?.value || '',
                    quantity: parseFloat(qtyInput?.value || 0),
                    price: itemPrice,
                    tax_rate: parseFloat(taxSelect?.value || 0),
                    tax_label: taxSelect?.options[taxSelect.selectedIndex]?.dataset?.label || '',
                });
            }
        });

        return products;
    }



    function getSelectedTaxBreakdown(taxableAmount) {
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        const fieldsBox = document.getElementById('gst_fields_box');
        if (getDocumentPriceMode() === 'base') {
            return getGlobalTaxBreakdown('global_tax_rate', taxableAmount, fieldsBox);
        }
        const buckets = {};
        let totalRate = 0;
        let totalAmount = 0;
        const shouldUpdateDisplay = taxableAmount !== null;
        const shouldApplyTaxes = taxableAmount !== 0;

        rows.forEach(function (row) {
            const select = row.querySelector('.product-select');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');
            const priceInput = row.querySelector('.product-price');
            const taxSelect = row.querySelector('.product-tax-rate');
            const rate = parseFloat(taxSelect?.value || 0);

            if (!select?.value || !rate || !shouldApplyTaxes) {
                return;
            }

            const qty = parseFloat(qtyInput?.value || 0);
            const price = parseFloat(priceInput?.value || 0);
            const rowBaseTotal = qty * price;
            if (rowBaseTotal <= 0) {
                return;
            }

            const selectedOption = taxSelect.options[taxSelect.selectedIndex];
            const label = (selectedOption?.dataset?.label || selectedOption?.textContent || 'GST').trim();

            if (label.toUpperCase().includes('CGST') && label.toUpperCase().includes('SGST')) {
                const halfRate = rate / 2;
                [
                    { label: 'CGST', rate: halfRate },
                    { label: 'SGST', rate: halfRate },
                ].forEach(function (taxLine) {
                    const key = taxLine.label + '|' + taxLine.rate.toFixed(4);
                    const amount = (rowBaseTotal * taxLine.rate) / 100;
                    buckets[key] = buckets[key] || { label: taxLine.label, rate: taxLine.rate, amount: 0 };
                    buckets[key].amount += amount;
                    totalAmount += amount;
                });
            } else {
                const normalizedLabel = label.toUpperCase().includes('IGST') ? 'IGST' : label;
                const key = normalizedLabel + '|' + rate.toFixed(4);
                const amount = (rowBaseTotal * rate) / 100;
                buckets[key] = buckets[key] || { label: normalizedLabel, rate, amount: 0 };
                buckets[key].amount += amount;
                totalAmount += amount;
            }
        });

        totalRate = Object.values(buckets).reduce(function (sum, line) {
            return sum + parseFloat(line.rate || 0);
        }, 0);

        if (fieldsBox && shouldUpdateDisplay) {
            const lines = Object.values(buckets);
            if (!lines.length) {
                fieldsBox.innerHTML = '<div class="totals-row"><span class="small text-muted">Select BOM tax to apply GST.</span><span class="small">0.00</span></div>';
            } else {
                fieldsBox.innerHTML = lines.map(function (line) {
                    const rateText = parseFloat(line.rate || 0).toFixed(2).replace(/\.?0+$/, '');
                    const amountText = parseFloat(line.amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return '<div class="totals-row gst-tax-row">' +
                        '<span class="small">' + line.label + ' (' + rateText + '%):</span>' +
                        '<span class="small gst-tax-amount">' + amountText + '</span>' +
                    '</div>';
                }).join('');
            }
        }

        return { totalRate, totalAmount };
    }

    function getGlobalTaxBreakdown(selectId, taxableAmount, fieldsBox) {
        const select = document.getElementById(selectId);
        const rate = parseFloat(select?.value || 0);
        const shouldUpdateDisplay = taxableAmount !== null;
        const taxable = parseFloat(taxableAmount || 0);
        const selectedOption = select?.options[select.selectedIndex];
        const label = (selectedOption?.dataset?.label || selectedOption?.textContent || 'GST').trim();
        const lines = [];

        if (rate > 0) {
            if (label.toUpperCase().includes('CGST') && label.toUpperCase().includes('SGST')) {
                const halfRate = rate / 2;
                lines.push(
                    { label: 'CGST', rate: halfRate, amount: (taxable * halfRate) / 100 },
                    { label: 'SGST', rate: halfRate, amount: (taxable * halfRate) / 100 }
                );
            } else {
                lines.push({
                    label: label.toUpperCase().includes('IGST') ? 'IGST' : label,
                    rate: rate,
                    amount: (taxable * rate) / 100,
                });
            }
        }

        if (fieldsBox && shouldUpdateDisplay) {
            fieldsBox.innerHTML = lines.length
                ? lines.map(function (line) {
                    const rateText = parseFloat(line.rate).toFixed(2).replace(/\.?0+$/, '');
                    const amountText = parseFloat(line.amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return '<div class="totals-row gst-tax-row"><span class="small">' + line.label + ' (' + rateText + '%):</span><span class="small gst-tax-amount">' + amountText + '</span></div>';
                }).join('')
                : '<div class="totals-row"><span class="small text-muted">No global tax selected.</span><span class="small">0.00</span></div>';
        }

        return {
            totalRate: rate,
            totalAmount: lines.reduce(function (sum, line) { return sum + line.amount; }, 0),
        };
    }

    function calculateTotals() {
        const subtotalField = document.getElementById('subtotal');
        const finalTotalField = document.getElementById('final_total');
        const subtotalDisplay = document.getElementById('subtotal_display');
        const finalTotalDisplay = document.getElementById('final_total_display');
        const priceInput = document.getElementById('price');

        if (!subtotalField || !finalTotalField || !subtotalDisplay || !finalTotalDisplay) {
            return;
        }

        // Dynamically sum up all products to sync total with main price input
        let productsTotal = 0;
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        rows.forEach(function (row) {
            const select = row.querySelector('.product-select');
            const qtyIn = row.querySelector('input[name="product_qty[]"]');
            const priceIn = row.querySelector('.product-price');
            const taxSelect = row.querySelector('.product-tax-rate');

            if (select && select.value && qtyIn) {
                const qty = parseFloat(qtyIn.value || 0);
                let p = 0;
                if (priceIn && priceIn.value !== '') {
                    p = parseFloat(priceIn.value || 0);
                } else {
                    const opt = select.options[select.selectedIndex];
                    p = parseFloat(opt?.dataset?.price || 0);
                }
                const rate = parseFloat(taxSelect?.value || 0);
                const taxAmount = (qty * p) * (rate / 100);
                const rowTotalWithTax = (qty * p) + taxAmount;
                productsTotal += (qty * p);

                // Update per-row readout if element exists
                const totalEl = row.querySelector('.product-total');
                if (totalEl) {
                    totalEl.value = formatStepOneInputValue(rowTotalWithTax);
                }
            }
        });

        const price = parseFloat(priceInput?.value || 0);
        const structureCharges = document.getElementById('solar_structure_charges_check')?.checked
            ? parseFloat(document.getElementById('solar_structure_charges')?.value || 0)
            : 0;
        const discount = parseFloat(document.getElementById('discount')?.value || 0);
        const subsidy = parseFloat(document.getElementById('subsidy_amount')?.value || 0);

        const basePrice = getDocumentPriceMode() === 'base' ? price : price + productsTotal;
        const subtotal = basePrice + structureCharges;
        const taxBreakdown = document.getElementById('apply_gst')?.checked
            ? getSelectedTaxBreakdown(subtotal)
            : getSelectedTaxBreakdown(0);
        const gstAmount = taxBreakdown.totalAmount;
        const subtotalTaxIncl = subtotal + gstAmount;
        const finalTotal = subtotalTaxIncl - discount - subsidy;

        subtotalDisplay.textContent = subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const subtotalTaxInclDisplay = document.getElementById('subtotal_tax_incl_display');
        if (subtotalTaxInclDisplay) {
            subtotalTaxInclDisplay.textContent = subtotalTaxIncl.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        finalTotalDisplay.textContent = finalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        subtotalField.value = subtotal.toFixed(2);
        finalTotalField.value = finalTotal.toFixed(2);

        const gstField = document.getElementById('gst');
        if (gstField) {
            gstField.value = document.getElementById('apply_gst')?.checked ? taxBreakdown.totalRate.toFixed(2) : '0';
        }

        const cgstDisplay = document.getElementById('cgst_display');
        const sgstDisplay = document.getElementById('sgst_display');
        const igstDisplay = document.getElementById('igst_display');
        const gstPercent = document.getElementById('apply_gst')?.checked ? taxBreakdown.totalRate : 0;

        if (cgstDisplay && sgstDisplay && igstDisplay) {
            const halfGst = gstPercent / 2;
            const cgstAmount = gstPercent > 0 ? (basePrice * halfGst) / 100 : 0;
            const sgstAmount = gstPercent > 0 ? (basePrice * halfGst) / 100 : 0;
            const igstAmount = gstPercent > 0 ? (basePrice * gstPercent) / 100 : 0;

            cgstDisplay.textContent = cgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            sgstDisplay.textContent = sgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            igstDisplay.textContent = igstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const cgstLabel = cgstDisplay.previousElementSibling;
            const sgstLabel = sgstDisplay.previousElementSibling;
            const igstLabel = igstDisplay.previousElementSibling;
            if (cgstLabel) cgstLabel.textContent = `CGST (${halfGst.toFixed(1)}%):`;
            if (sgstLabel) sgstLabel.textContent = `SGST (${halfGst.toFixed(1)}%):`;
            if (igstLabel) igstLabel.textContent = `IGST (${gstPercent.toFixed(1)}%):`;
        }
    }
    function autoCalculateSubsidy(options) {
        options = options || {};
        const typeField = options.typeField
            || document.getElementById(options.typeId || 'type')
            || (options.typeSelector ? document.querySelector(options.typeSelector) : null);
        const quantityField = document.getElementById(options.quantityId || 'quantity');
        const subsidyField = document.getElementById(options.subsidyId || 'subsidy_amount');

        if (!typeField || !quantityField || !subsidyField) {
            return;
        }

        const type = typeField.value;
        const kw = parseFloat(quantityField.value || 0);

        // Robust check for subsidiesData whether it comes as array or object
        const rawData = window.subsidiesData;
        let dataArr = [];
        if (Array.isArray(rawData)) {
            dataArr = rawData;
        } else if (rawData && typeof rawData === 'object') {
            dataArr = Object.values(rawData);
        }

        if (!dataArr || dataArr.length === 0) {
            return; // No data available
        }

        // Create a quick lookup map
        const rates = {};
        dataArr.forEach(function(item) {
            if (item && item.category) {
                rates[item.category] = parseFloat(item.amount || 0);
            }
        });

        let calculatedSubsidy = 0;

        if (type === 'residential') {
            const rate0_2 = rates['residential_0_2'] || 0;
            const rate2_3 = rates['residential_2_3'] || 0;
            const maxAbove3 = rates['residential_above_3'] || 0;

            if (kw < 2) {
                calculatedSubsidy = rate0_2;
            } else if (kw >= 2 && kw < 3) {
                calculatedSubsidy = rate2_3;
            } else if (kw >= 3) {
                calculatedSubsidy = maxAbove3;
            }
            // if (kw <= 0) {
            //     calculatedSubsidy = 0;
            // } else if (kw >= 3) {
            //     // Total Cap for 3kW and above
            //     calculatedSubsidy = maxAbove3 ;
            // } else if (kw > 2) {
            //     // Tiered calculation
            //     calculatedSubsidy = rate0_2;
            // } else {
            //     // Up to 2kW calculation
            //     calculatedSubsidy = rate0_2;
            // }
        } else if (type === 'common meter') {
            const rateCommon = rates['common_meter'] || 0;
            calculatedSubsidy = rateCommon * kw;
        } else {
            calculatedSubsidy = 0;
        }

        // Safely update the field value
        subsidyField.value = formatStepOneInputValue(calculatedSubsidy);

        if (typeof options.onUpdated === 'function') {
            options.onUpdated();
        }
    }

    function initCalculations() {
        // Determine if initial price was manually overridden
        const priceInput = document.getElementById('price');
        if (priceInput && priceInput.value !== '') {
            let initialProductsTotal = 0;
            const rows = document.querySelectorAll('#bomContainer .bom-row');
            rows.forEach(function (row) {
                const select = row.querySelector('.product-select');
                const qtyIn = row.querySelector('input[name="product_qty[]"]');
                const priceIn = row.querySelector('.product-price');

                if (select && select.value && qtyIn) {
                    const qty = parseFloat(qtyIn.value || 0);
                    let p = 0;
                    if (priceIn && priceIn.value !== '') {
                        p = parseFloat(priceIn.value || 0);
                    } else {
                        const opt = select.options[select.selectedIndex];
                        p = parseFloat(opt?.dataset?.price || 0);
                    }
                    initialProductsTotal += qty * p;
                }
            });

            const currentVal = parseFloat(priceInput.value || 0);
            if (currentVal > 0 && Math.abs(currentVal - initialProductsTotal) > 0.01) {
                isPriceManualOverride = true;
            }
        }

        // Attach robust listeners to all key fields
        const inputs = ['price', 'solar_structure_charges', 'discount', 'subsidy_amount'];
        inputs.forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                restrictNegative(input);
                ['input', 'change'].forEach(function(evt) {
                    input.addEventListener(evt, function() {
                        // Flag that user manually overridden price computation if edit made directly in field
                        if (id === 'price') {
                            const v = input.value;
                            isPriceManualOverride = (v !== null && String(v).trim() !== '');
                        }
                        calculateTotals();
                    });
                });
            }
        });

        // Use jQuery for type and quantity to fully support Select2 events
        $('#type').on('change select2:select', function () {
            autoCalculateSubsidy();
            calculateTotals();
        });

        $('#quantity').on('input change', function () {
            autoCalculateSubsidy();
            calculateTotals();
        });

        const structureCheckbox = document.getElementById('solar_structure_charges_check');
        const gstCheckbox = document.getElementById('apply_gst');
        document.getElementById('global_tax_rate')?.addEventListener('change', calculateTotals);

        if (structureCheckbox) {
            structureCheckbox.addEventListener('change', function () {
                const box = document.getElementById('structure-charges-input');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });

            const box = document.getElementById('structure-charges-input');
            if (box) {
                box.style.display = structureCheckbox.checked ? 'block' : 'none';
            }
        }

        if (gstCheckbox) {
            gstCheckbox.addEventListener('change', function () {
                const box = document.getElementById('gst_fields_box');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });

            const box = document.getElementById('gst_fields_box');
            if (box) {
                box.style.display = gstCheckbox.checked ? 'block' : 'none';
            }
        }

        // Initial calculation runs
        // If it's a brand new form, initialize subsidy.
        // For existing edit, let user alter quantity to recalculate.
        const quantityVal = document.getElementById('quantity')?.value;
        if (quantityVal && parseFloat(quantityVal) > 0 && !window.location.pathname.match(/\/invoices\/\d+\/edit\/?$/)) {
            // Check if user is creating a new form by verifying hidden input ID absence or looking at path?
            // Actually safe approach: Always ensure valid mathematical state on load
            autoCalculateSubsidy();
        }
        calculateTotals();
    }

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.bom-image-remove');
        if (!removeButton) return;
        const input = document.getElementById(removeButton.dataset.input);
        const preview = document.getElementById(removeButton.dataset.preview);
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    });

    function initEditBom() {
        if (!document.getElementById('invoiceEditBomModal')) return;

        document.getElementById('invoiceEditBomModal').addEventListener('shown.bs.modal', function () {
            initQuickBomMakeSelect(document.getElementById('invoice_edit_bom_category_id'));
        });

        // Toggle edit link visibility on BOM select
        if (window.jQuery) {
            window.jQuery(document).on('change', '.product-select', function() {
                const row = this.closest('.bom-row');
                if (row) {
                    const editLink = row.querySelector('.edit-bom-link');
                    if (editLink) {
                        if (this.value) {
                            editLink.classList.remove('d-none');
                        } else {
                            editLink.classList.add('d-none');
                        }
                    }
                }
            });

            // Initialize existing rows
            window.jQuery('.product-select').each(function() {
                if (this.value) {
                    const row = this.closest('.bom-row');
                    if (row) {
                        const editLink = row.querySelector('.edit-bom-link');
                        if (editLink) editLink.classList.remove('d-none');
                    }
                }
            });
        }

        // Open modal
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.edit-bom-link');
            if (link) {
                e.preventDefault();
                const row = link.closest('.bom-row') || link.closest('.quick-bom-row');
                if (!row) return;

                const select = row.querySelector('.product-select') || row.querySelector('.quick-bom-select');
                if (!select || !select.value) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select a BOM first.');
                    }
                    return;
                }

                const option = select.options[select.selectedIndex];
                if (!option) return;

                const modalEl = document.getElementById('invoiceEditBomModal');
                if (!modalEl) return;

                document.getElementById('invoice_edit_bom_id').value = select.value;
                const specifications = window.getEstimateBomSpecifications?.(row, select.value) || {};
                (window.bomSpecificationFields || []).forEach(field => {
                    const input = document.getElementById('invoice_edit_bom_' + field);
                    if (input) input.value = specifications[field] ?? '';
                });
                document.getElementById('invoice_edit_bom_name').value = option.dataset.name || '';
                document.getElementById('invoice_edit_bom_description').value = row.querySelector('.product-description')?.value ?? option.dataset.desc ?? '';
                const hsnInput = document.getElementById('invoice_edit_bom_hsn_sac');
                if (hsnInput) hsnInput.value = row.querySelector('.product-hsn-sac')?.value ?? '';

                const priceInput = row.querySelector('.product-price') || row.querySelector('.quick-bom-price');
                document.getElementById('invoice_edit_bom_price').value = priceInput ? priceInput.value : (option.dataset.price || '');

                const taxSelect = row.querySelector('.product-tax-rate') || row.querySelector('.quick-bom-tax-rate');
                if (taxSelect) {
                    document.getElementById('invoice_edit_bom_tax_rate').value = taxSelect.value;
                } else {
                    document.getElementById('invoice_edit_bom_tax_rate').value = option.dataset.taxRate || '0';
                }

                const makeSelect = row.querySelector('.product-make') || row.querySelector('.quick-bom-make-select');
                const editMakeSelect = document.getElementById('invoice_edit_bom_category_id');
                if (makeSelect && editMakeSelect) {
                    editMakeSelect.value = makeSelect.value;
                    if (window.jQuery && window.jQuery(editMakeSelect).hasClass('select2-hidden-accessible')) {
                        window.jQuery(editMakeSelect).val(makeSelect.value).trigger('change');
                    }
                }

                if (!row.id) {
                    row.id = 'bom-row-' + Date.now() + Math.random().toString(36).substr(2, 9);
                }
                modalEl.dataset.activeRowId = row.id;

                // Reset validation
                document.getElementById('invoice_edit_bom_name').classList.remove('is-invalid');
                document.getElementById('invoice_edit_bom_price').classList.remove('is-invalid');

                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                // Fix z-index if opened on top of another modal
                setTimeout(() => {
                    modalEl.style.zIndex = '1060';
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 1) {
                        backdrops[backdrops.length - 1].style.zIndex = '1059';
                    }
                }, 100);
            }
        });

        // Save modal
        const invoiceSaveEditBomBtn = document.getElementById('invoiceSaveEditBomBtn');
        if (invoiceSaveEditBomBtn) {
            invoiceSaveEditBomBtn.addEventListener('click', async function() {
                if (invoiceSaveEditBomBtn.disabled) return;
                const modalEl = document.getElementById('invoiceEditBomModal');
                const rowId = modalEl.dataset.activeRowId;
                const row = document.getElementById(rowId);

                const bomId = document.getElementById('invoice_edit_bom_id').value;
                const name = document.getElementById('invoice_edit_bom_name').value;
                const description = document.getElementById('invoice_edit_bom_description').value;
                const hsnSac = document.getElementById('invoice_edit_bom_hsn_sac')?.value || '';
                const price = document.getElementById('invoice_edit_bom_price').value;
                const taxRate = document.getElementById('invoice_edit_bom_tax_rate').value;

                const makeSelect = document.getElementById('invoice_edit_bom_category_id');
                let categoryId = makeSelect.value;
                let categoryName = makeSelect.options[makeSelect.selectedIndex]?.dataset?.name || makeSelect.selectedOptions[0]?.textContent?.trim() || '';

                const saveMode = document.querySelector('input[name="invoice_edit_bom_save_mode"]:checked').value;

                let isValid = true;
                if (!name.trim()) {
                    document.getElementById('invoice_edit_bom_name').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('invoice_edit_bom_name').classList.remove('is-invalid');
                }
                if (price === '' || parseFloat(price) < 0) {
                    document.getElementById('invoice_edit_bom_price').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('invoice_edit_bom_price').classList.remove('is-invalid');
                }

                if (!isValid) return;

                const saveSpinner = document.getElementById('invoiceSaveEditBomSpinner');

                const formConfig = getActiveDocumentFormConfig();
                const makeConfig = window[formConfig.bomQuickAddConfigKey] || window.estimateBomQuickAddConfig || {};
                invoiceSaveEditBomBtn.disabled = true;
                saveSpinner.classList.remove('d-none');
                try {
                    const make = await resolveQuickBomMake(makeSelect, makeConfig);
                    categoryId = make?.id || '';
                    categoryName = make?.name || '';
                } catch (error) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', error?.errors?.name?.[0] || error?.message || 'Unable to create Make.');
                    }
                    return;
                } finally {
                    invoiceSaveEditBomBtn.disabled = false;
                    saveSpinner.classList.add('d-none');
                }

                if (saveMode === 'master') {
                    invoiceSaveEditBomBtn.disabled = true;
                    saveSpinner.classList.remove('d-none');

                    const payload = {
                        ...Object.fromEntries((window.bomSpecificationFields || []).map(field => [field, document.getElementById('invoice_edit_bom_' + field)?.value || null])),
                        tax_rate: taxRate,
                        product_name: name,
                        description: description,
                        hsn_sac: hsnSac,
                        price: price,
                        category_id: categoryId ? [categoryId] : []
                    };

                    fetch('/api/bom-products/' + bomId, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            applyEditBomChangesToDOM(bomId, name, description, hsnSac, price, taxRate, categoryId, categoryName, row);
                            window.bomSpecificationDefaults[bomId] = window.getEstimateBomSpecifications(row, bomId);

                            // Also update the original option in ALL BOM selects
                            document.querySelectorAll('.product-select option[value="' + bomId + '"], .quick-bom-select option[value="' + bomId + '"]').forEach(opt => {
                                opt.dataset.name = name;
                                opt.dataset.desc = description;
                                opt.dataset.hsn = hsnSac;
                                opt.dataset.price = price;
                                if (categoryName) {
                                    opt.dataset.categories = JSON.stringify([categoryName]);
                                }
                                opt.textContent = name;
                            });
                            // Re-trigger select2 if used
                            if (window.jQuery) {
                                window.jQuery('.product-select, .quick-bom-select').trigger('change.select2');
                            }

                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', 'Master BOM record updated successfully.');
                            }
                        } else {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('error', data.message || 'Failed to update BOM.');
                            }
                        }
                    })
                    .catch(error => {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Network error occurred while updating BOM.');
                        }
                    })
                    .finally(() => {
                        invoiceSaveEditBomBtn.disabled = false;
                        saveSpinner.classList.add('d-none');
                    });
                } else {
                    applyEditBomChangesToDOM(bomId, name, description, hsnSac, price, taxRate, categoryId, categoryName, row);
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            });
        }

        // BOM Image preview for Edit BOM Details modal
        const editBomImageInput = document.getElementById('invoice_edit_bom_image');
        if (editBomImageInput) {
            editBomImageInput.addEventListener('change', function () {
                const file = this.files[0];
                const preview = document.getElementById('invoice_edit_bom_image_preview');
                const thumb = document.getElementById('invoice_edit_bom_image_thumb');
                if (file) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/jfif', 'image/pjpeg'];
                    const maxSize = 5 * 1024 * 1024;
                    if (!allowedTypes.includes(file.type)) {
                        if (typeof window.showAlert === 'function') window.showAlert('error', 'Only JPG, PNG, JPEG images are allowed.');
                        this.value = '';
                        if (preview) preview.style.display = 'none';
                        return;
                    }
                    if (file.size > maxSize) {
                        if (typeof window.showAlert === 'function') window.showAlert('error', 'Image must be under 5MB.');
                        this.value = '';
                        if (preview) preview.style.display = 'none';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (thumb) thumb.src = e.target.result;
                        if (preview) preview.style.display = '';
                    };
                    reader.readAsDataURL(file);
                } else {
                    if (preview) preview.style.display = 'none';
                }
            });
        }

        // Reset edit BOM image preview when modal closes
        const invoiceEditBomModalEl = document.getElementById('invoiceEditBomModal');
        if (invoiceEditBomModalEl) {
            invoiceEditBomModalEl.addEventListener('hidden.bs.modal', function () {
                const imgInput = document.getElementById('invoice_edit_bom_image');
                const imgPreview = document.getElementById('invoice_edit_bom_image_preview');
                const imgThumb = document.getElementById('invoice_edit_bom_image_thumb');
                if (imgInput) imgInput.value = '';
                if (imgPreview) imgPreview.style.display = 'none';
                if (imgThumb) imgThumb.src = '';
            });
        }

        function applyEditBomChangesToDOM(bomId, name, description, hsnSac, price, taxRate, categoryId, categoryName, row) {
            if (!row) return;
            const specifications = JSON.parse(row.dataset.bomSpecifications || '{}');
            specifications[bomId] = Object.fromEntries((window.bomSpecificationFields || []).map(field => [field, document.getElementById('invoice_edit_bom_' + field)?.value || null]));
            row.dataset.bomSpecifications = JSON.stringify(specifications);
            const descriptionInput = row.querySelector('.product-description');
            if (descriptionInput) descriptionInput.value = description;
            const hsnInput = row.querySelector('.product-hsn-sac');
            if (hsnInput) hsnInput.value = hsnSac;

            const select = row.querySelector('.product-select') || row.querySelector('.quick-bom-select');
            if (select) {
                const option = select.querySelector('option[value="' + bomId + '"]');
                if (option) {
                    option.dataset.name = name;
                    option.dataset.desc = description;
                    option.dataset.hsn = hsnSac;
                    option.dataset.price = price;
                    if (categoryName) {
                        option.dataset.categories = JSON.stringify([categoryName]);
                    }
                }
            }

            const priceInput = row.querySelector('.product-price') || row.querySelector('.quick-bom-price');
            if (priceInput) priceInput.value = price;

            const taxSelect = row.querySelector('.product-tax-rate') || row.querySelector('.quick-bom-tax-rate');
            if (taxSelect) taxSelect.value = taxRate;

            const makeSelect = row.querySelector('.product-make') || row.querySelector('.quick-bom-make-select');
            if (makeSelect && categoryId) {
                let option = makeSelect.querySelector('option[value="' + categoryId + '"]');
                if (!option) {
                    option = new Option(categoryName, categoryId, false, false);
                    makeSelect.add(option);
                }
                makeSelect.value = categoryId;
            }

            if (priceInput) {
                priceInput.dispatchEvent(new Event('input', {bubbles:true}));
            }
        }

        // Intercept form submission to include products JSON
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.classList.contains('ajax-invoice-form')) {
                const products = [];
                form.querySelectorAll('.bom-row').forEach(row => {
                    const select = row.querySelector('.product-select');
                    const bomId = select ? select.value : '';
                    if (!bomId) return;

                    const option = select.options[select.selectedIndex];

                    const makeSelect = row.querySelector('.product-make');
                    const categoryName = makeSelect && makeSelect.options[makeSelect.selectedIndex] ?
                        (makeSelect.options[makeSelect.selectedIndex].dataset.name || makeSelect.options[makeSelect.selectedIndex].text) : '';

                    const qtyInput = row.querySelector('input[name="product_qty[]"]');
                    const priceInput = row.querySelector('.product-price');
                    const taxSelect = row.querySelector('.product-tax-rate');
                    const taxLabel = taxSelect && taxSelect.options[taxSelect.selectedIndex] ? taxSelect.options[taxSelect.selectedIndex].dataset.label : '';

                    products.push({
                    ...(window.getEstimateBomSpecifications?.(row, row.querySelector('.product-select, .quick-bom-select')?.value) || {}),
                        product_id: bomId,
                        hsn_sac: row.querySelector('.product-hsn-sac')?.value ?? option.dataset.hsn ?? '',
                        name: option.dataset.name || '',
                        description: row.querySelector('.product-description')?.value ?? option.dataset.desc ?? '',
                        category_name: categoryName,
                        quantity: qtyInput ? parseFloat(qtyInput.value || 0) : 0,
                        price: priceInput ? parseFloat(priceInput.value || 0) : 0,
                        tax_rate: taxSelect ? parseFloat(taxSelect.value || 0) : 0,
                        tax_label: taxLabel
                    });
                });

                if (products.length > 0) {
                    let hiddenInput = form.querySelector('input[name="products"]');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'products';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = JSON.stringify(products);
                }
            }
        });
        document.getElementById('invoiceEditBomModal')?.addEventListener('hidden.bs.modal', function () {
            if (document.querySelectorAll('.modal.show').length > 0) {
                document.body.classList.add('modal-open');
            }
        });
    }
})();
