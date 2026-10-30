/**
 * Estimates Form Handler
 * Handles create/edit form submission with BOM, GST, and calculations
 */

(function () {
    const API_BASE = '/api/v1/estimates';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initFormHandlers();
        initBomHandlers();
        initCalculations();
    }

    // ============ Form Submission ============
    function initFormHandlers() {
        const form = document.getElementById('estimateCreateForm') || document.getElementById('estimateEditForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            submitForm(form);
        });
    }

    function submitForm(form) {
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const formData = new FormData(form);
        const isEdit = form.id === 'estimateEditForm';

        // Collect BOM data
        const bomData = collectBomData();
        formData.set('products', JSON.stringify(bomData));

        // Collect totals
        formData.set('subtotal', document.getElementById('subtotal')?.value || '0');
        formData.set('final_total', document.getElementById('final_total')?.value || '0');
        
        // Get GST checkbox state
        const applyGst = document.getElementById('apply_gst')?.checked ? 1 : 0;
        formData.set('apply_gst', applyGst);
        
        if (applyGst) {
            // Calculate GST components
            const subtotal = parseFloat(document.getElementById('subtotal')?.value || 0);
            const cgst = (subtotal * 2.5) / 100;
            const sgst = (subtotal * 2.5) / 100;
            const igst = (subtotal * 5) / 100;
            formData.set('cgst', cgst.toFixed(2));
            formData.set('sgst', sgst.toFixed(2));
            formData.set('igst', igst.toFixed(2));
            formData.set('gst', (cgst + sgst + igst).toFixed(2));
        } else {
            formData.set('cgst', '0');
            formData.set('sgst', '0');
            formData.set('igst', '0');
            formData.set('gst', '0');
        }

        // Ensure estimate type is included
        const estimateType = document.getElementById('type')?.value;
        if (!estimateType) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('error', 'Please select an estimate type');
            }
            return;
        }

        // Ensure template is selected
        const templateId = document.getElementById('template_id')?.value;
        if (!templateId) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('error', 'Please select a quotation template');
            }
            return;
        }

        // Ensure solar meter charges is selected
        const solarMeterCharges = document.getElementById('solar_meter_select')?.value;
        if (!solarMeterCharges) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('error', 'Please select solar meter charges option');
            }
            return;
        }

        submitBtn.disabled = true;
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Saving...';

        const url = isEdit ? form.getAttribute('action') : '/api/v1/estimates';
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                if (response.success) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Estimate saved successfully.', 'Success!');
                    }
                    setTimeout(() => {
                        window.location.href = response.redirect || '/estimates';
                    }, 1000);
                }
            },
            error: function (xhr) {
                submitBtn.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = isEdit ? 'Update Estimate' : 'Create Estimate';

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    handleValidationErrors(form, xhr.responseJSON.errors);
                } else {
                    const message = xhr.responseJSON?.message || 'Something went wrong.';
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', message);
                    } else {
                        alert(message);
                    }
                }
            }
        });
    }

    function handleValidationErrors(form, errors) {
        console.log('Validation errors:', errors);
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = form.querySelector(`#${field}-error`);
                if (feedback) {
                    feedback.textContent = errors[field][0];
                    feedback.style.display = 'block';
                }
            } else {
                console.warn(`Field not found: ${field}`);
            }
        });
    }

    // ============ BOM Handlers ============
    function initBomHandlers() {
        const addBtn = document.getElementById('add_more_bom');
        const container = document.getElementById('bomContainer');

        if (!addBtn || !container) return;

        addBtn.addEventListener('click', function () {
            const firstRow = container.querySelector('.bom-row');
            if (!firstRow) return;

            const newRow = firstRow.cloneNode(true);
            
            // Reset values
            newRow.querySelectorAll('input, select').forEach(el => {
                if (el.type === 'number') {
                    el.value = '0';
                } else if (el.tagName === 'SELECT') {
                    el.value = '';
                }
            });

            // Show delete button
            const deleteBtn = newRow.querySelector('.delete-bom-row');
            if (deleteBtn) {
                deleteBtn.style.display = 'block';
            }

            container.appendChild(newRow);
            attachBomRowHandlers(newRow);
        });

        // Attach handlers to existing rows
        container.querySelectorAll('.bom-row').forEach(row => {
            attachBomRowHandlers(row);
        });
    }

    function attachBomRowHandlers(row) {
        const productSelect = row.querySelector('.product-select');
        const makeSelect = row.querySelector('.product-make');
        const deleteBtn = row.querySelector('.delete-bom-row');

        if (productSelect) {
            productSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const categories = selectedOption.dataset.categories;
                
                if (makeSelect && categories) {
                    try {
                        const categoryList = JSON.parse(categories);
                        makeSelect.innerHTML = '<option value="">Select Make</option>';
                        categoryList.forEach(cat => {
                            const opt = document.createElement('option');
                            opt.value = cat;
                            opt.textContent = cat;
                            makeSelect.appendChild(opt);
                        });
                        makeSelect.disabled = false;
                    } catch (e) {
                        console.error('Error parsing categories:', e);
                    }
                }

                calculateTotals();
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                const container = document.getElementById('bomContainer');
                if (container.querySelectorAll('.bom-row').length > 1) {
                    row.remove();
                    calculateTotals();
                }
            });
        }

        // Recalculate on quantity change
        const qtyInput = row.querySelector('input[name="product_qty[]"]');
        if (qtyInput) {
            qtyInput.addEventListener('change', calculateTotals);
            qtyInput.addEventListener('input', calculateTotals);
        }
    }

    function collectBomData() {
        const container = document.getElementById('bomContainer');
        const rows = container?.querySelectorAll('.bom-row') || [];
        const products = [];

        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');

            if (productSelect && productSelect.value) {
                const option = productSelect.options[productSelect.selectedIndex];
                products.push({
                    ...(window.getEstimateBomSpecifications?.(row, row.querySelector('.product-select, .quick-bom-select')?.value) || {}),
                    product_id: productSelect.value,
                    name: option.dataset.name || '',
                    description: row.querySelector('.product-description')?.value ?? option.dataset.desc ?? '',
                    category_name: makeSelect?.value || '',
                    quantity: parseFloat(qtyInput?.value || 0),
                    price: parseFloat(option.dataset.price || 0)
                });
            }
        });

        return products;
    }

    // ============ Calculations ============
    function initCalculations() {
        const priceInput = document.getElementById('price');
        const quantityInput = document.getElementById('quantity');
        const structureCheckbox = document.getElementById('solar_structure_charges_check');
        const structureInput = document.getElementById('solar_structure_charges');
        const gstCheckbox = document.getElementById('apply_gst');
        const discountInput = document.getElementById('discount');
        const subsidyInput = document.getElementById('subsidy_amount');

        const inputs = [priceInput, quantityInput, structureCheckbox, structureInput, gstCheckbox, discountInput, subsidyInput];

        inputs.forEach(input => {
            if (input) {
                input.addEventListener('change', calculateTotals);
                input.addEventListener('input', calculateTotals);
            }
        });

        // Handle structure charges visibility
        if (structureCheckbox) {
            structureCheckbox.addEventListener('change', function () {
                const box = document.getElementById('structure-charges-input');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });
        }

        // Handle GST fields visibility
        if (gstCheckbox) {
            gstCheckbox.addEventListener('change', function () {
                const box = document.getElementById('gst_fields_box');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });
        }

        // Initial calculation
        calculateTotals();
    }

    function calculateTotals() {
        const price = parseFloat(document.getElementById('price')?.value || 0);
        const quantity = parseFloat(document.getElementById('quantity')?.value || 0);
        const structureCharges = document.getElementById('solar_structure_charges_check')?.checked 
            ? parseFloat(document.getElementById('solar_structure_charges')?.value || 0) 
            : 0;
        const applyGst = document.getElementById('apply_gst')?.checked || false;
        const discount = parseFloat(document.getElementById('discount')?.value || 0);
        const subsidy = parseFloat(document.getElementById('subsidy_amount')?.value || 0);

        // Calculate subtotal (price * quantity + structure charges)
        const subtotal = (price * quantity) + structureCharges;

        // Calculate GST components
        let cgstAmount = 0;
        let sgstAmount = 0;
        let igstAmount = 0;
        let totalGst = 0;

        if (applyGst) {
            // CGST and SGST: 2.5% each (for intra-state)
            cgstAmount = (subtotal * 2.5) / 100;
            sgstAmount = (subtotal * 2.5) / 100;
            // IGST: 5% (for inter-state)
            igstAmount = (subtotal * 5) / 100;
            // Total GST is CGST + SGST (or IGST if inter-state, but showing all)
            totalGst = cgstAmount + sgstAmount + igstAmount;
        }

        // Calculate final total
        const finalTotal = subtotal + cgstAmount + sgstAmount + igstAmount - discount - subsidy;

        // Update display
        document.getElementById('subtotal_display').textContent = subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Update GST component displays
        if (document.getElementById('cgst_display')) {
            document.getElementById('cgst_display').textContent = cgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (document.getElementById('sgst_display')) {
            document.getElementById('sgst_display').textContent = sgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (document.getElementById('igst_display')) {
            document.getElementById('igst_display').textContent = igstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        
        document.getElementById('final_total_display').textContent = finalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Update hidden fields
        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('final_total').value = finalTotal.toFixed(2);
        document.getElementById('gst').value = applyGst ? totalGst.toFixed(2) : 0;
    }
})();
