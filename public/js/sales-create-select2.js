(function () {
    let quickProductTargetSelect = null;

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function initSelect2() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            return false;
        }

        const categorySelect = window.jQuery('#quick_product_category');
        if (categorySelect.length && !categorySelect.hasClass('select2-hidden-accessible')) {
            categorySelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                dropdownParent: window.jQuery('#quickProductModal'),
                placeholder: 'Select or Add Category',
                minimumResultsForSearch: 0,
                createTag: function (params) {
                    const term = window.jQuery.trim(params.term);

                    if (!term) {
                        return null;
                    }

                    return {
                        id: term,
                        text: term,
                        newTag: true,
                    };
                },
                templateResult: function (data) {
                    if (data.newTag) {
                        return window.jQuery('<span>Create category: <strong></strong></span>').find('strong').text(data.text).end();
                    }

                    return data.text;
                },
            });
        }

        window.jQuery('select.js-select2, select.searchable-select, select.product-select').each(function () {
            if (this.id === 'quick_product_category') {
                return;
            }

            if (window.jQuery(this).hasClass('select2-hidden-accessible')) {
                return;
            }

            window.jQuery(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: this.dataset.placeholder || this.querySelector('option[value=""]')?.textContent || 'Select',
                allowClear: false,
                minimumResultsForSearch: 0,
            });
        });

        return true;
    }

    function initFallback() {
        document.querySelectorAll('select.js-select2, select.searchable-select, select.product-select, #quick_product_category').forEach(function (select) {
            if (select.dataset.searchFallback === '1' || select.classList.contains('select2-hidden-accessible')) {
                return;
            }

            select.dataset.searchFallback = '1';
            select.classList.add('sales-search-select__native');

            const wrapper = document.createElement('div');
            wrapper.className = 'sales-search-select';
            wrapper.innerHTML = [
                '<button type="button" class="sales-search-select__toggle">',
                '<span class="sales-search-select__label"></span>',
                '<i class="bi bi-chevron-down"></i>',
                '</button>',
                '<div class="sales-search-select__dropdown">',
                '<input type="text" class="form-control form-control-sm sales-search-select__search" placeholder="' + (select.id === 'quick_product_category' ? 'Search or type new category...' : 'Search...') + '">',
                '<div class="sales-search-select__options"></div>',
                '</div>',
            ].join('');

            select.insertAdjacentElement('afterend', wrapper);

            const label = wrapper.querySelector('.sales-search-select__label');
            const toggle = wrapper.querySelector('.sales-search-select__toggle');
            const search = wrapper.querySelector('.sales-search-select__search');
            const optionsContainer = wrapper.querySelector('.sales-search-select__options');

            function updateLabel() {
                const selectedOption = select.options[select.selectedIndex];
                label.textContent = selectedOption?.textContent || select.dataset.placeholder || 'Select';
            }

            function renderOptions(filter) {
                const normalizedFilter = String(filter || '').trim().toLowerCase();
                optionsContainer.innerHTML = '';

                Array.from(select.options).forEach(function (option) {
                    const text = option.textContent || '';
                    if (option.disabled || (normalizedFilter && !text.toLowerCase().includes(normalizedFilter))) {
                        return;
                    }

                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'sales-search-select__option' + (option.selected ? ' active' : '');
                    item.textContent = text;
                    item.addEventListener('click', function () {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        wrapper.classList.remove('open');
                        search.value = '';
                        updateLabel();
                        renderOptions();
                    });
                    optionsContainer.appendChild(item);
                });

                if (!optionsContainer.children.length) {
                    optionsContainer.innerHTML = '<div class="text-muted small px-2 py-1">No results found</div>';
                }
            }

            toggle.addEventListener('click', function () {
                document.querySelectorAll('.sales-search-select.open').forEach(function (openWrapper) {
                    if (openWrapper !== wrapper) {
                        openWrapper.classList.remove('open');
                    }
                });

                wrapper.classList.toggle('open');
                renderOptions(search.value);

                if (wrapper.classList.contains('open')) {
                    setTimeout(function () {
                        search.focus();
                    }, 0);
                }
            });

            search.addEventListener('input', function () {
                renderOptions(search.value);
            });

            search.addEventListener('keydown', function (event) {
                const term = search.value.trim();

                if (select.id !== 'quick_product_category' || event.key !== 'Enter' || !term) {
                    return;
                }

                event.preventDefault();

                let option = Array.from(select.options).find(function (item) {
                    return item.textContent.trim().toLowerCase() === term.toLowerCase();
                });

                if (!option) {
                    option = new Option(term, term, true, true);
                    select.appendChild(option);
                }

                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                wrapper.classList.remove('open');
                search.value = '';
                updateLabel();
                renderOptions();
            });

            select.addEventListener('change', updateLabel);

            updateLabel();
            renderOptions();
        });
    }

    function clearQuickProductErrors() {
        ['quick_product_name', 'quick_product_category', 'quick_product_quantity'].forEach(function (id) {
            const input = document.getElementById(id);
            const error = document.getElementById(id + '-error');

            if (input) {
                input.classList.remove('is-invalid');
            }
            if (error) {
                error.textContent = '';
            }
        });
    }

    function showQuickProductError(id, message) {
        const input = document.getElementById(id);
        const error = document.getElementById(id + '-error');

        if (input) {
            input.classList.add('is-invalid');
        }
        if (error) {
            error.textContent = message || 'Invalid value.';
        }
    }

    function closeQuickProductModal() {
        const modal = document.getElementById('quickProductModal');
        const closeButton = modal?.querySelector('[data-bs-dismiss="modal"]');

        if (window.bootstrap && modal) {
            const instance = window.bootstrap.Modal.getInstance(modal) || window.bootstrap.Modal.getOrCreateInstance(modal);
            instance.hide();
            return;
        }

        closeButton?.click();
    }

    function closeModalById(modalId) {
        const modal = document.getElementById(modalId);
        const closeButton = modal?.querySelector('[data-bs-dismiss="modal"]');

        if (window.bootstrap && modal) {
            const instance = window.bootstrap.Modal.getInstance(modal) || window.bootstrap.Modal.getOrCreateInstance(modal);
            instance.hide();
            return;
        }

        closeButton?.click();
    }

    function showFieldError(id, message) {
        const input = document.getElementById(id);
        const error = document.getElementById(id + '-error');

        if (input) {
            input.classList.add('is-invalid');
        }
        if (error) {
            error.textContent = message || 'Invalid value.';
        }
    }

    function clearFieldErrors(ids) {
        ids.forEach(function (id) {
            const input = document.getElementById(id);
            const error = document.getElementById(id + '-error');

            if (input) {
                input.classList.remove('is-invalid');
            }
            if (error) {
                error.textContent = '';
            }
        });
    }

    function addOptionToSelect(select, id, text, selected) {
        if (!select || !id) {
            return;
        }

        let option = Array.from(select.options).find(function (item) {
            return String(item.value) === String(id);
        });

        if (!option) {
            option = new Option(text, id, selected, selected);
            select.appendChild(option);
        }

        if (selected) {
            option.selected = true;
            select.value = id;
            select.dispatchEvent(new Event('change', { bubbles: true }));

            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(select).trigger('change');
            }
        }
    }

    function addProductToSalesSelects(product) {
        document.querySelectorAll('.product-select').forEach(function (select) {
            let option = Array.from(select.options).find(function (item) {
                return String(item.value) === String(product.id);
            });

            if (!option) {
                option = new Option(product.name, product.id, false, false);
                select.appendChild(option);
            }

            option.dataset.stock = product.current_stock ?? product.quantity ?? 0;
        });

        const targetSelect = quickProductTargetSelect || document.querySelector('.product-select');
        if (targetSelect) {
            targetSelect.value = product.id;
            targetSelect.dispatchEvent(new Event('change', { bubbles: true }));

            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(targetSelect).trigger('change');
            }
        }
    }

    function resolveProductCategoryId(form, categoryInput) {
        const selectedValue = categoryInput?.value || '';
        const selectedOption = categoryInput?.options[categoryInput.selectedIndex];
        const selectedText = selectedOption?.textContent?.trim() || selectedValue;

        if (/^\d+$/.test(String(selectedValue))) {
            return Promise.resolve(selectedValue);
        }

        if (!selectedText) {
            return Promise.reject({ errors: { category_id: ['Please select a category.'] } });
        }

        return postJson(form.dataset.categoryStoreUrl || '/api/products/categories', {
            name: selectedText,
        }).then(function (payload) {
            const category = payload.data;

            if (!category?.id) {
                throw { errors: { category_id: ['Category was not created.'] } };
            }

            addOptionToSelect(categoryInput, category.id, category.name, true);
            return category.id;
        }).catch(function (error) {
            if (error.errors?.name) {
                throw { errors: { category_id: [error.errors.name[0]] } };
            }

            throw error;
        });
    }

    function bindQuickProductSubmit() {
        const button = document.getElementById('submitQuickProductBtn');
        const form = document.getElementById('quickProductForm');

        if (!button || !form || button.dataset.directProductHandler === '1') {
            return;
        }

        button.dataset.directProductHandler = '1';
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            if (button.disabled) {
                return;
            }

            const nameInput = document.getElementById('quick_product_name');
            const categoryInput = document.getElementById('quick_product_category');
            const quantityInput = document.getElementById('quick_product_quantity');
            const spinner = document.getElementById('quickProductSpinner');
            let hasError = false;

            clearQuickProductErrors();

            if (!nameInput?.value.trim()) {
                showQuickProductError('quick_product_name', 'Please enter product name.');
                hasError = true;
            }

            if (!categoryInput?.value) {
                showQuickProductError('quick_product_category', 'Please select a category.');
                hasError = true;
            }

            if (!quantityInput?.value || Number(quantityInput.value) < 0) {
                showQuickProductError('quick_product_quantity', 'Please enter a valid quantity.');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            button.disabled = true;
            spinner?.classList.remove('d-none');

            resolveProductCategoryId(form, categoryInput)
                .then(function (categoryId) {
                    return postJson(form.dataset.productStoreUrl || '/api/products', {
                    name: nameInput.value.trim(),
                        category_id: categoryId,
                    quantity: quantityInput.value,
                    status: 'active',
                    availability: Number(quantityInput.value || 0) > 0 ? 'in_stock' : 'out_of_stock',
                    });
                })
                .then(function (payload) {
                    if (!payload.success || !payload.data?.id) {
                        throw { message: payload.message || 'Product was not created.' };
                    }

                    addProductToSalesSelects(payload.data);
                    form.reset();

                    if (window.jQuery && window.jQuery.fn.select2) {
                        window.jQuery('#quick_product_category').val('').trigger('change');
                    }

                    closeQuickProductModal();

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || 'Product added successfully.');
                    }
                })
                .catch(function (error) {
                    const errors = error.errors || {};

                    if (errors.name) {
                        showQuickProductError('quick_product_name', errors.name[0]);
                    }
                    if (errors.category_id) {
                        showQuickProductError('quick_product_category', errors.category_id[0]);
                    }
                    if (errors.quantity) {
                        showQuickProductError('quick_product_quantity', errors.quantity[0]);
                    }

                    if (!Object.keys(errors).length) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', error.message || 'Unable to add product.');
                        } else {
                            alert(error.message || 'Unable to add product.');
                        }
                    }
                })
                .finally(function () {
                    button.disabled = false;
                    spinner?.classList.add('d-none');
                });
        }, true);
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw data;
                }

                return data;
            });
        });
    }

    function bindQuickCustomerSubmit() {
        const button = document.getElementById('submitQuickCustomerBtn');
        const form = document.getElementById('quickCustomerForm');

        if (!button || !form || button.dataset.directCustomerHandler === '1') {
            return;
        }

        button.dataset.directCustomerHandler = '1';
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const nameInput = document.getElementById('quick_customer_name');
            const phoneInput = document.getElementById('quick_customer_phone');
            const spinner = document.getElementById('quickCustomerSpinner');
            let hasError = false;

            clearFieldErrors(['quick_customer_name', 'quick_customer_phone']);

            if (!nameInput?.value.trim()) {
                showFieldError('quick_customer_name', 'Please enter customer name.');
                hasError = true;
            }

            if (!/^[0-9]{10}$/.test(phoneInput?.value.trim() || '')) {
                showFieldError('quick_customer_phone', 'Please enter 10 digit phone number.');
                hasError = true;
            }

            if (hasError || button.disabled) {
                return;
            }

            button.disabled = true;
            spinner?.classList.remove('d-none');

            postJson(form.dataset.customerStoreUrl || '/api/customers', {
                name: nameInput.value.trim(),
                phone: phoneInput.value.trim(),
                address: '',
            })
                .then(function (payload) {
                    const customer = payload.data;

                    if (!customer?.id) {
                        throw { message: payload.message || 'Customer was not created.' };
                    }

                    addOptionToSelect(document.getElementById('customer_id'), customer.id, customer.name, true);
                    form.reset();
                    closeModalById('quickCustomerModal');

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || 'Customer added successfully.');
                    }
                })
                .catch(function (error) {
                    const errors = error.errors || {};

                    if (errors.name) {
                        showFieldError('quick_customer_name', errors.name[0]);
                    }
                    if (errors.phone) {
                        showFieldError('quick_customer_phone', errors.phone[0]);
                    }
                    if (!Object.keys(errors).length) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', error.message || 'Unable to add customer.');
                        } else {
                            alert(error.message || 'Unable to add customer.');
                        }
                    }
                })
                .finally(function () {
                    button.disabled = false;
                    spinner?.classList.add('d-none');
                });
        }, true);
    }

    function bindQuickHandoverSubmit() {
        const button = document.getElementById('submitQuickHandoverBtn');
        const form = document.getElementById('quickHandoverForm');

        if (!button || !form || button.dataset.directHandoverHandler === '1') {
            return;
        }

        button.dataset.directHandoverHandler = '1';
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const nameInput = document.getElementById('quick_handover_name');
            const phoneInput = document.getElementById('quick_handover_phone');
            const spinner = document.getElementById('quickHandoverSpinner');
            let hasError = false;

            clearFieldErrors(['quick_handover_name', 'quick_handover_phone']);

            if ((nameInput?.value.trim() || '').length < 3) {
                showFieldError('quick_handover_name', 'Name must be at least 3 characters.');
                hasError = true;
            }

            if (!/^[0-9]{10}$/.test(phoneInput?.value.trim() || '')) {
                showFieldError('quick_handover_phone', 'Please enter 10 digit phone number.');
                hasError = true;
            }

            if (hasError || button.disabled) {
                return;
            }

            button.disabled = true;
            spinner?.classList.remove('d-none');

            postJson(form.dataset.handoverStoreUrl || '/api/handover-persons', {
                name: nameInput.value.trim(),
                phone: phoneInput.value.trim(),
                status: 'active',
            })
                .then(function (payload) {
                    const person = payload.data;

                    if (!person?.id) {
                        throw { message: payload.message || 'Handover person was not created.' };
                    }

                    addOptionToSelect(document.getElementById('handover_id'), person.id, person.name, true);
                    form.reset();
                    closeModalById('quickHandoverModal');

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || 'Handover person added successfully.');
                    }
                })
                .catch(function (error) {
                    const errors = error.errors || {};

                    if (errors.name) {
                        showFieldError('quick_handover_name', errors.name[0]);
                    }
                    if (errors.phone) {
                        showFieldError('quick_handover_phone', errors.phone[0]);
                    }
                    if (!Object.keys(errors).length) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', error.message || 'Unable to add handover person.');
                        } else {
                            alert(error.message || 'Unable to add handover person.');
                        }
                    }
                })
                .finally(function () {
                    button.disabled = false;
                    spinner?.classList.add('d-none');
                });
        }, true);
    }

    function getNextProductIndex() {
        const indexes = Array.from(document.querySelectorAll('.product-item'))
            .map(function (item) {
                return Number(item.dataset.itemIndex || 0);
            })
            .filter(function (index) {
                return !Number.isNaN(index);
            });

        return indexes.length ? Math.max.apply(null, indexes) + 1 : 0;
    }

    function updateProductRowsUi() {
        const productItems = document.querySelectorAll('.product-item');
        const productCount = document.getElementById('productCount');

        if (productCount) {
            productCount.textContent = productItems.length;
        }

        document.querySelectorAll('.remove-product-btn').forEach(function (button) {
            button.style.display = productItems.length > 1 ? 'block' : 'none';
        });
    }

    function resetEnhancedSelect(select) {
        if (!select) {
            return;
        }

        select.classList.remove('select2-hidden-accessible', 'sales-search-select__native');
        select.removeAttribute('data-select2-id');
        select.removeAttribute('aria-hidden');
        select.removeAttribute('tabindex');
        select.dataset.searchFallback = '';

        select.querySelectorAll('option').forEach(function (option) {
            option.removeAttribute('data-select2-id');
            option.selected = option.value === '';
        });
    }

    function addProductRow() {
        const container = document.getElementById('productItemsContainer');
        const firstItem = container?.querySelector('.product-item');

        if (!container || !firstItem) {
            return;
        }

        const index = getNextProductIndex();
        const firstSelect = firstItem.querySelector('.product-select');
        const productOptions = firstSelect ? firstSelect.innerHTML : '<option value="">Select Product</option>';
        const newProductItem = document.createElement('div');

        newProductItem.className = 'product-item border rounded p-3 mb-3';
        newProductItem.dataset.itemIndex = String(index);
        newProductItem.innerHTML = [
            '<div class="row g-3">',
            '<div class="col-md-6">',
            '<label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>',
            '<div class="input-group">',
            '<select name="products[' + index + '][product_id]" class="form-select product-select js-select2" data-placeholder="Select Product" required>',
            productOptions,
            '</select>',
            '<button type="button" class="btn btn-dark-blue quick-add-product-btn" data-bs-toggle="modal" data-bs-target="#quickProductModal" title="Add Product">',
            '<i class="bi bi-plus-lg"></i>',
            '</button>',
            '</div>',
            '<div class="invalid-feedback">Please select a product.</div>',
            '</div>',
            '<div class="col-md-4">',
            '<label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>',
            '<input type="number" min="0" name="products[' + index + '][quantity]" class="form-control quantity-input" placeholder="0" required>',
            '<div class="invalid-feedback">Please enter a valid quantity.</div>',
            '</div>',
            '<div class="col-md-2 d-flex align-items-end">',
            '<button type="button" class="btn btn-danger btn-sm remove-product-btn">',
            '<i class="bi bi-trash"></i>',
            '</button>',
            '</div>',
            '</div>',
        ].join('');

        resetEnhancedSelect(newProductItem.querySelector('.product-select'));
        container.appendChild(newProductItem);

        if (!initSelect2()) {
            initFallback();
        }

        updateProductRowsUi();
    }

    function bindProductRows() {
        const addButton = document.getElementById('addProductBtn');

        if (addButton && addButton.dataset.directAddRowHandler !== '1') {
            addButton.dataset.directAddRowHandler = '1';
            addButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                addProductRow();
            }, true);
        }

        document.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-product-btn');

            if (!removeButton) {
                return;
            }

            const productItems = document.querySelectorAll('.product-item');
            if (productItems.length <= 1) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            removeButton.closest('.product-item')?.remove();
            updateProductRowsUi();
        }, true);

        updateProductRowsUi();
    }

    function setSalesSubmitLoading(loading) {
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('btnSpinner');

        if (submitBtn) {
            submitBtn.disabled = loading;
        }

        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
    }

    function showSalesFieldErrors(errors) {
        Object.keys(errors || {}).forEach(function (field) {
            const input = document.querySelector('[name="' + field + '"]')
                || document.querySelector('[name="' + field.replace(/\.(\d+)\./g, '[$1][').replace(/\./g, '][') + ']"]')
                || document.getElementById(field);

            if (!input) {
                return;
            }

            input.classList.add('is-invalid');

            const feedback = input.closest('.product-item')?.querySelector('.invalid-feedback')
                || input.parentElement?.nextElementSibling
                || input.nextElementSibling;

            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = errors[field][0] || 'Invalid value.';
                feedback.style.display = 'block';
            }
        });
    }

    function bindSalesFormSubmit() {
        const form = document.getElementById('salesCreateForm') || document.getElementById('salesEditForm');

        if (!form || form.dataset.directSubmitHandler === '1') {
            return;
        }

        form.dataset.directSubmitHandler = '1';
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            form.querySelectorAll('.is-invalid').forEach(function (input) {
                input.classList.remove('is-invalid');
            });

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            setSalesSubmitLoading(true);

            fetch(form.getAttribute('action') || '/api/v1/sales', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(form),
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw payload;
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    const successMessage = form.id === 'salesEditForm'
                        ? 'Material OUT updated successfully.'
                        : 'Material OUT created successfully.';

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || successMessage, 'Success!', '/sales');
                        return;
                    }

                    window.location.href = '/sales';
                })
                .catch(function (error) {
                    if (error.errors) {
                        showSalesFieldErrors(error.errors);
                        form.classList.add('was-validated');
                        return;
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', error.message || 'Something went wrong.');
                    } else {
                        alert(error.message || 'Something went wrong.');
                    }
                })
                .finally(function () {
                    setSalesSubmitLoading(false);
                });
        }, true);
    }

    document.addEventListener('click', function (event) {
        const quickProductButton = event.target.closest('.quick-add-product-btn');
        if (quickProductButton) {
            quickProductTargetSelect = quickProductButton.closest('.product-item')?.querySelector('.product-select') || document.querySelector('.product-select');
        }

        if (!event.target.closest('.sales-search-select')) {
            document.querySelectorAll('.sales-search-select.open').forEach(function (wrapper) {
                wrapper.classList.remove('open');
            });
        }
    });

    ready(function () {
        bindQuickCustomerSubmit();
        bindQuickHandoverSubmit();
        bindQuickProductSubmit();
        bindProductRows();
        bindSalesFormSubmit();

        let attempts = 0;
        const timer = setInterval(function () {
            attempts += 1;
            if (initSelect2()) {
                clearInterval(timer);
                return;
            }

            if (attempts >= 10) {
                clearInterval(timer);
                initFallback();
            }
        }, 200);
    });
}());
