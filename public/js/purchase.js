(function () {
    const PURCHASE_API_BASE = '/api/v1/purchases';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPurchaseIndex);
    } else {
        initPurchaseIndex();
    }

    function initPurchaseIndex() {
        const permissions = window.crmUserPermissions?.purchases || {};
        const tableBody = document.querySelector('#purchasesTable tbody');
        const paginationContainer = document.getElementById('purchasesPagination');
        const searchInput = document.getElementById('purchasesSearch');
        const exportButton = document.getElementById('purchasesExportBtn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        function syncExportUrl() {
            if (!exportButton) {
                return;
            }

            const url = new URL(exportButton.href, window.location.origin);
            const search = searchInput.value.trim();

            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            exportButton.href = url.toString();
        }

        function formatDate(dateValue) {
            if (!dateValue) return '-';
            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
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

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No purchases found.</p>
                            ${permissions.create ? '<a href="/purchases/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Purchase</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (purchase, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const vendorName = escapeHtml(purchase.vendor?.name || '-');
                const invoiceNo = escapeHtml(purchase.invoice_no || '-');
                const inDate = escapeHtml(formatDate(purchase.invoice_date));

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold small">${vendorName}</div>
                            <div class="text-muted small d-md-none mt-1">${invoiceNo}</div>
                        </td>
                        <td class="d-none d-md-table-cell text-center">${invoiceNo}</td>
                        <td class="d-none d-md-table-cell text-center">${inDate}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2 justify-content-end">
                                ${permissions.edit ? `<a href="/purchases/${purchase.invoice_id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/purchases/${purchase.invoice_id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.view ? `<a href="/purchases/${purchase.invoice_id}/pdf" class="btn crm-action-btn btn-sm" title="Download PDF"><i class="bi bi-file-pdf"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${purchase.invoice_id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-purchase-id="${purchase.invoice_id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${purchase.invoice_id}" style="display: none;">
                        <td colspan="6" class="p-0 border-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-hashtag"></i> IN No :</div>
                                        <div class="expand-value text-end">${invoiceNo}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> IN Date :</div>
                                        <div class="expand-value text-end">${inDate}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<a href="/purchases/${purchase.invoice_id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                            ${permissions.view ? `<a href="/purchases/${purchase.invoice_id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                            ${permissions.view ? `<a href="/purchases/${purchase.invoice_id}/pdf" class="btn crm-action-btn btn-sm" title="Download PDF"><i class="bi bi-file-pdf"></i></a>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${purchase.invoice_id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.purchaseId;
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

            bindDeleteButtons();
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

            document.querySelectorAll('#purchasesPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchPurchases(this.dataset.page);
                });
            });
        }

        function fetchPurchases(page) {
            const url = new URL(PURCHASE_API_BASE, window.location.origin);
            url.searchParams.set('page', page || 1);
            url.searchParams.set('_t', Date.now()); // Cache busting

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            $.ajax({
                url: url.toString(),
                type: 'GET',
                dataType: 'json',
                cache: false, // Disable jQuery AJAX caching
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-5">
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
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5">Error loading purchases</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        function deletePurchase(id, button) {
            window.showDeleteConfirm('This purchase will be deleted!').then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: `${PURCHASE_API_BASE}/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    success: function (response) {
                        if (response.success) {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', response.message || 'Purchase deleted successfully.');
                            }
                            fetchPurchases(1);
                        } else {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('error', response.message || 'Delete failed.');
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Something went wrong while deleting the purchase.');
                        }
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        function bindDeleteButtons() {
            document.querySelectorAll('#purchasesTable .delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deletePurchase(this.dataset.id, this);
                });
            });
        }

        let searchTimer;
        searchInput.addEventListener('input', function () {
            syncExportUrl();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchPurchases(1);
            }, 400);
        });

        syncExportUrl();
        fetchPurchases(1);
    }
})();
