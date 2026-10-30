(function () {
    const INVENTORY_API_BASE = '/api/product-inventory';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInventoryIndex);
    } else {
        initInventoryIndex();
    }

    function initInventoryIndex() {
        const tableBody = document.querySelector('#inventoryTable tbody');
        const paginationContainer = document.getElementById('inventoryPagination');
        const searchInput = document.getElementById('inventorySearch');
        const permissions = window.crmUserPermissions?.inventory || {};
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
            if (value === null || value === undefined) return '';
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
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No inventory records found.</p>
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (inventory, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const productName = escapeHtml(inventory.product?.name || '-');
                const createdAt = escapeHtml(formatDate(inventory.created_at));
                const currentStock = inventory.current_stock || 0;

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold small">${productName}</div>
                        </td>
                        <td class="d-none d-md-table-cell text-center">${currentStock}</td>
                        <td class="d-none d-md-table-cell text-center">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-flex align-items-center gap-2 justify-content-end">
                                ${permissions.view ? `
                                <a href="/inventory/history/${inventory.product_id}" class="btn btn-dark-blue btn-sm" style="white-space: nowrap;" title="View History">
                                    <i class="bi bi-clock-history"></i> View History
                                </a>` : ''}
                                ${permissions.edit ? `
                                <button type="button" class="btn btn-dark-blue btn-sm edit-stock-btn" style="white-space: nowrap;" data-id="${inventory.id}" data-product-id="${inventory.product_id}" data-product-name="${productName}" data-current-stock="${currentStock}" title="Edit Stock">
                                    <i class="bi bi-pencil"></i> Add / Edit Stock
                                </button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-inventory-id="${inventory.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${inventory.id}" style="display: none;">
                        <td colspan="6" class="p-0 border-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-boxes"></i> Current Stock :</div>
                                        <div class="expand-value text-end">${currentStock}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center align-items-center pt-3 mt-3 border-top gap-2">
                                        ${permissions.view ? `
                                        <a href="/inventory/history/${inventory.product_id}" class="btn btn-dark-blue btn-sm" style="white-space: nowrap;" title="View History">
                                            <i class="bi bi-clock-history"></i> View History
                                        </a>` : ''}
                                        ${permissions.edit ? `
                                        <button type="button" class="btn btn-dark-blue btn-sm edit-stock-btn" style="white-space: nowrap;" data-id="${inventory.id}" data-product-id="${inventory.product_id}" data-product-name="${productName}" data-current-stock="${currentStock}" title="Edit Stock">
                                            <i class="bi bi-pencil"></i> Add / Edit Stock
                                        </button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            bindActionButtons();
            bindExpandButtons();
        }

        function bindExpandButtons() {
            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.inventoryId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector('i');

                    if (!detailsRow) return;

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

            document.querySelectorAll('#inventoryPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchInventories(this.dataset.page);
                });
            });
        }

        function fetchInventories(page) {
            const url = new URL(INVENTORY_API_BASE, window.location.origin);
            url.searchParams.set('page', page || 1);

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
                            <td colspan="6" class="text-center py-5">
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
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-5">Error loading inventory</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        function bindActionButtons() {
            document.querySelectorAll('.edit-stock-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const inventoryId = this.dataset.id;
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    const currentStock = this.dataset.currentStock;

                    document.getElementById('modalProductName').value = productName;
                    document.getElementById('modalCurrentStock').value = currentStock;
                    document.getElementById('modalQuantityChange').value = 0;
                    document.getElementById('editStockForm').dataset.inventoryId = inventoryId;
                    document.getElementById('editStockForm').dataset.productId = productId;
                    document.getElementById('editStockForm').dataset.currentStock = currentStock;

                    const modal = new bootstrap.Modal(document.getElementById('editStockModal'));
                    modal.show();
                });
            });
        }

        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchInventories(1);
            }, 400);
        });

        // Plus/Minus quantity buttons
        document.getElementById('increaseQty').addEventListener('click', function (e) {
            e.preventDefault();
            const input = document.getElementById('modalQuantityChange');
            input.value = (parseInt(input.value) || 0) + 1;
        });

        document.getElementById('decreaseQty').addEventListener('click', function (e) {
            e.preventDefault();
            const input = document.getElementById('modalQuantityChange');
            const newValue = Math.max(0, (parseInt(input.value) || 0) - 1);
            input.value = newValue;
        });

        // Update label based on radio selection
        document.querySelectorAll('input[name="updateType"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                const label = document.getElementById('quantityLabel');
                if (this.value === 'add') {
                    label.textContent = 'How much to Add?';
                } else {
                    label.textContent = 'How much to Minus?';
                }
            });
        });

        document.getElementById('editStockForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const inventoryId = this.dataset.inventoryId;
            const productId = this.dataset.productId;
            const currentStock = parseInt(this.dataset.currentStock) || 0;
            const updateType = document.querySelector('input[name="updateType"]:checked').value;
            const quantityChange = parseInt(document.getElementById('modalQuantityChange').value) || 0;

            if (quantityChange === 0) {
                showToast('Please enter a quantity greater than 0', 'warning');
                return;
            }

            let newStock = currentStock;
            if (updateType === 'add') {
                newStock = currentStock + quantityChange;
            } else {
                newStock = currentStock - quantityChange;
            }

            if (newStock < 0) {
                showToast('Stock cannot be negative!', 'error');
                return;
            }

            const data = {
                product_id: productId,
                initial_stock: quantityChange,
                current_stock: newStock,
                type: updateType === 'add' ? 'increase' : 'decrease',
                date: new Date().toISOString().split('T')[0],
            };

            const isNewInventory = inventoryId.startsWith('prod_') || inventoryId.startsWith('new_');
            const url = isNewInventory ? INVENTORY_API_BASE : `${INVENTORY_API_BASE}/${inventoryId}`;
            const method = isNewInventory ? 'POST' : 'PUT';

            $.ajax({
                url: url,
                type: method,
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function (response) {
                    if (response.success) {
                        showToast(response.message || 'Stock updated successfully.', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editStockModal')).hide();
                        fetchInventories(1);
                    }
                },
                error: function (xhr) {
                    showToast(xhr.responseJSON?.message || 'Error updating stock.', 'error');
                },
            });
        });

        fetchInventories(1);
    }
})();
