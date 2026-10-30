(function () {
    const PRODUCT_API_BASE = '/api/products';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductIndex);
    } else {
        initProductIndex();
    }

    function initProductIndex() {
        const permissions = window.crmUserPermissions?.products || {};
        const tableBody = document.querySelector('#productsTable tbody');
        const paginationContainer = document.getElementById('productsPagination');
        const searchInput = document.getElementById('productsSearch');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        function formatDate(dateValue) {
            if (!dateValue) return '-';

            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return '-';

            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showToast(message, type) {
            if (typeof window.showAlert === 'function') {
                window.showAlert(type || 'info', message);
                return;
            }

            alert(message);
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No products found.</p>
                            ${permissions.create ? '<a href="/products/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Product</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (product, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const category = escapeHtml(product.category?.name || '-');
                const productName = escapeHtml(product.name || '-');
                const createdAt = escapeHtml(formatDate(product.created_at));
                // Get current stock from inventory, fallback to product quantity
                const currentStock = product.current_stock !== undefined ? product.current_stock : (product.quantity || 0);

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold small">${productName}</div>
                            <div class="text-muted small mt-1">${category}</div>
                        </td>
                        <td class="d-none d-md-table-cell text-center">${category}</td>
                        <td class="d-none d-md-table-cell text-center">${currentStock}</td>
                        <td class="d-none d-md-table-cell text-center">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.edit ? `<a href="/products/${product.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/products/${product.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${product.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-product-id="${product.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border" id="details-${product.id}" style="display: none;">
                        <td colspan="7" class="p-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-regular fa-folder-open"></i> Category :</div>
                                        <div class="expand-value text-end">${category}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-boxes"></i> Quantity :</div>
                                        <div class="expand-value text-end">${currentStock}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<a href="/products/${product.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                            ${permissions.view ? `<a href="/products/${product.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${product.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            bindDeleteButtons();

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.productId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector('i');

                    if (!detailsRow) {
                        return;
                    }

                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = 'table-row';
                        icon.classList.replace('fa-plus', 'fa-minus');
                        this.classList.add('active');
                    } else {
                        detailsRow.style.display = 'none';
                        icon.classList.replace('fa-minus', 'fa-plus');
                        this.classList.remove('active');
                    }
                });
            });
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('#productsPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchProducts(this.dataset.page);
                });
            });
        }

        function fetchProducts(page) {
            if (window.moduleListFilters && !window.moduleListFilters.bound) {
                window.moduleListFilters.bound = true;
                window.moduleListFilters.bind(fetchProducts, 'productsSearch');
            }
            const url = new URL(PRODUCT_API_BASE, window.location.origin);
            url.searchParams.set('page', page || 1);
            Object.entries(window.moduleListFilters?.values() || {}).forEach(([key, value]) => url.searchParams.set(key, value));

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            $.ajax({
                url: url.toString(),
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading products</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        function deleteProduct(id, button) {
            window.showDeleteConfirm('This product will be deleted!').then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: `${PRODUCT_API_BASE}/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    success: function (response) {
                        if (response.success) {
                            showToast(response.message || 'Product deleted successfully.', 'success');
                            fetchProducts(1);
                        } else {
                            showToast(response.message || 'Delete failed.', 'error');
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        showToast('Something went wrong while deleting the product.', 'error');
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        function bindDeleteButtons() {
            document.querySelectorAll('#productsTable .delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteProduct(this.dataset.id, this);
                });
            });
        }

        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchProducts(1);
            }, 400);
        });

        fetchProducts(1);
    }
})();

$(document).ready(function () {
    function showToast(message, type) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type || 'info', message);
            return;
        }

        alert(message);
    }

    function handleValidationErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            const fieldEl = $form.find('[name="' + field + '"]');
            if (!fieldEl.length) {
                return;
            }

            fieldEl.addClass('is-invalid');
            fieldEl.closest('.product-category-inline').addClass('is-invalid');
            const feedback = $form.find('#' + field + '-error');
            if (feedback.length) {
                feedback.html(errors[field][0]);
            }
        });
    }

    $('.ajax-product-form').each(function () {
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.product-category-inline.is-invalid').removeClass('is-invalid');
        $form.find('.ajax-alert').remove();
        $form.find('.invalid-feedback').html('');
    });

    $('body').on('submit', '.ajax-product-form', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalHtml = $button.html();
        const formData = new FormData(this);

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.product-category-inline.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').html('');
        $form.find('.ajax-alert').remove();

        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (typeof window.showAlert === 'function') {
                    window.showAlert('success', response.message || 'Product saved successfully.', 'Success!', response.redirect || '/products');
                    return;
                }

                showToast(response.message || 'Product saved successfully.', 'success');
                setTimeout(function () {
                    window.location.href = response.redirect || '/products';
                }, 3000);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                    return;
                }

                const message = xhr.responseJSON?.message || 'Something went wrong while saving the product.';
                $form.prepend('<div class="alert alert-danger ajax-alert" role="alert">' + message + '</div>');
            },
            complete: function () {
                $button.prop('disabled', false).html(originalHtml);
            },
        });
    });

    $('#add-category-btn').on('click', function () {
        const form = document.getElementById('add-category-form');
        if (form) {
            form.reset();
        }

        $('#new-category-name').removeClass('is-valid is-invalid');
        $('#new-category-name-error').text('');
        const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        modal.show();
    });

    $('#save-category-btn').on('click', function () {
        const $name = $('#new-category-name');
        const $error = $('#new-category-name-error');
        const $select = $('#product_category_id');

        $name.removeClass('is-valid is-invalid');
        $error.text('');

        $.ajax({
            url: '/api/products/categories',
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: {
                name: $name.val(),
                description: $('#new-category-description').val(),
            },
            success: function (response) {
                if (response.success && response.data) {
                    const option = $('<option></option>').attr('value', response.data.id).text(response.data.name);
                    $select.append(option).val(response.data.id);
                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                    showToast(response.message || 'Product category created successfully.', 'success');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors?.name?.length) {
                    $name.addClass('is-invalid');
                    $error.text(xhr.responseJSON.errors.name[0]);
                    return;
                }

                showToast('Unable to create category right now.', 'error');
            },
        });
    });

    $('body').on('input change', 'input, select, textarea', function () {
        $(this).removeClass('is-invalid');
        $(this).closest('.product-category-inline').removeClass('is-invalid');
        $('#' + $(this).attr('id') + '-error').html('');
    });

    $('#image').on('change', function () {
        const file = this.files && this.files[0];
        const previewWrap = document.getElementById('product-image-preview-wrap');
        const previewImage = document.getElementById('product-image-preview');

        if (!previewWrap || !previewImage) {
            return;
        }

        if (!file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        previewImage.src = objectUrl;
        previewWrap.classList.remove('d-none');

        previewImage.onload = function () {
            URL.revokeObjectURL(objectUrl);
        };
    });

    // Barcode Scanning - Prefill Product Data
    const serialNoInput = document.getElementById('serial_no');
    let prefillCheckTimeout;

    if (serialNoInput) {
        serialNoInput.addEventListener('change', function() {
            const serialNo = this.value.trim();
            
            // Only search if we have a serial number
            if (!serialNo) {
                return;
            }

            // Debounce the API call
            clearTimeout(prefillCheckTimeout);
            prefillCheckTimeout = setTimeout(() => {
                checkAndPrefillProduct(serialNo);
            }, 500);
        });

        // Also trigger on blur for manual entries
        serialNoInput.addEventListener('blur', function() {
            const serialNo = this.value.trim();
            if (serialNo) {
                checkAndPrefillProduct(serialNo);
            }
        });
    }

    function checkAndPrefillProduct(serialNo) {
        // Don't search if serial number is empty
        if (!serialNo) {
            return;
        }

        // Reset form fields to clear old data
        resetProductForm();

        $.ajax({
            url: `/api/products/by-serial/${encodeURIComponent(serialNo)}`,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            success: function(response) {
                if (response.success && response.data) {
                    prefillProductForm(response.data);
                }
                // If product not found, that's okay - user can add it manually
            },
            error: function(xhr) {
                // 404 is expected for new products, don't show error
                if (xhr.status !== 404) {
                    console.error('Error checking product:', xhr);
                }
            },
        });
    }

    function resetProductForm() {
        // Reset all form fields to empty state (except serial_no which is being set by scan)
        const form = document.getElementById('productCreateForm');
        if (!form) {
            return;
        }

        // Clear name
        const nameInput = document.getElementById('name');
        if (nameInput) {
            nameInput.value = '';
            nameInput.classList.remove('is-invalid');
        }

        // Reset category to default (empty)
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.value = '';
            categorySelect.classList.remove('is-invalid');
            if (window.jQuery && $.fn.select2 && $(categorySelect).hasClass('select2-hidden-accessible')) {
                $(categorySelect).trigger('change');
            }
        }

        // Clear/reset quantity to empty
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.value = '';
            quantityInput.classList.remove('is-invalid');
        }

        // Reset availability to default (empty)
        const availabilitySelect = document.getElementById('availability');
        if (availabilitySelect) {
            availabilitySelect.value = '';
            availabilitySelect.classList.remove('is-invalid');
        }

        // Reset status to default (empty)
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            statusSelect.value = '';
            statusSelect.classList.remove('is-invalid');
        }

        // Clear description
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.value = '';
            descriptionTextarea.classList.remove('is-invalid');
        }

        // Clear all validation error messages and classes
        form.querySelectorAll('.invalid-feedback').forEach(function(el) {
            el.textContent = '';
        });
        form.querySelectorAll('.is-invalid').forEach(function(el) {
            el.classList.remove('is-invalid');
        });

        console.log('Form reset completed');
    }

    function prefillProductForm(productData) {
        // Prefill form fields with product data
        const form = document.getElementById('productCreateForm');
        if (!form) {
            return;
        }

        // Ensure serial number is set (should already be set, but confirm)
        const serialInput = document.getElementById('serial_no');
        if (serialInput) {
            serialInput.value = productData.serial_no || '';
        }

        // Prefill name - set value even if empty
        const nameInput = document.getElementById('name');
        if (nameInput) {
            nameInput.value = productData.name || '';
        }

        // Prefill category - set value even if empty
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.value = productData.category_id || '';
            if (window.jQuery && $.fn.select2 && $(categorySelect).hasClass('select2-hidden-accessible')) {
                $(categorySelect).trigger('change');
            }
        }

        // Prefill quantity - set value even if empty
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.value = productData.quantity || '';
        }

        // Prefill availability/stock status - set value even if empty
        const availabilitySelect = document.getElementById('availability');
        if (availabilitySelect) {
            availabilitySelect.value = productData.availability || '';
        }

        // Prefill status - set value even if empty
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            statusSelect.value = productData.status || '';
        }

        // Prefill description - set value even if empty
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.value = productData.description || '';
        }

        // Show a toast notification
        showToast('Product data loaded from existing record!', 'info');
        console.log('Form prefilled with product data:', productData);
    }
});
