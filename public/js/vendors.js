(function () {
    const VENDOR_API_BASE = '/api/vendors';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVendorsIndex);
    } else {
        initVendorsIndex();
    }

    function initVendorsIndex() {
        const permissions = window.crmUserPermissions?.vendors || {};
        const tableBody = document.querySelector('#vendorsTable tbody');
        const paginationContainer = document.getElementById('vendorsPagination');
        const searchInput = document.getElementById('vendorsSearch');

        if (!tableBody || !paginationContainer || !searchInput) return;

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
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No vendors found.</p>
                            ${permissions.create ? '<a href="/add-vendor" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Vendor</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (vendor, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const name = escapeHtml(vendor.name || '-');
                const email = vendor.email ? '<a href="mailto:' + escapeHtml(vendor.email) + '" class="text-decoration-none">' + escapeHtml(vendor.email) + '</a>' : '--';
                const phone = vendor.phone ? '<a href="tel:' + escapeHtml(vendor.phone) + '" class="text-decoration-none">' + escapeHtml(vendor.phone) + '</a>' : '--';
                const createdAt = escapeHtml(formatDate(vendor.created_at));

                return `
                    <tr>
                        <td class="ps-4 text-center"><span class="text-muted small fw-medium">${srNo}</span></td>
                        <td class="text-center"><div class="fw-bold small">${name}</div></td>
                        <td class="d-none d-md-table-cell text-center">${email}</td>
                        <td class="d-none d-md-table-cell text-center">${phone}</td>
                        <td class="d-none d-md-table-cell text-center">${createdAt}</td>
                        <td class="text-center pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.edit ? `<a href="/add-vendor/${vendor.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/all-vendor/${vendor.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${vendor.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-vendor-id="${vendor.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="vendor-details-${vendor.id}" style="display: none;">
                        <td colspan="7" class="p-0 border-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label">Email :</div><div class="expand-value text-end">${email}</div></div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label">Phone :</div><div class="expand-value text-end">${phone}</div></div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label">Created :</div><div class="expand-value text-end">${createdAt}</div></div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label">Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.edit ? `<a href="/add-vendor/${vendor.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                        ${permissions.view ? `<a href="/all-vendor/${vendor.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${vendor.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            bindDeleteButtons();

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.vendorId;
                    const detailsRow = document.getElementById(`vendor-details-${id}`);
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

            let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} entries</div><ul class="pagination crm-pagination mb-0">`;
            html += data.prev_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>` : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            html += data.next_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>` : '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('#vendorsPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchVendors(this.dataset.page);
                });
            });
        }

        function fetchVendors(page) {
            const url = new URL(VENDOR_API_BASE, window.location.origin);
            url.searchParams.set('page', page || 1);

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((response) => response.json())
                .then((response) => {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                })
                .catch(function () {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading vendors</td></tr>';
                    paginationContainer.innerHTML = '';
                });
        }

        function deleteVendor(id, button) {
            window.showDeleteConfirm('This vendor will be deleted!').then(function (result) {
                if (!result.isConfirmed) return;

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                fetch(`${VENDOR_API_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (response.success) {
                            showToast(response.message || 'Vendor deleted successfully.', 'success');
                            fetchVendors(1);
                            return;
                        }

                        showToast(response.message || 'Failed to delete vendor.', 'error');
                    })
                    .catch(function () {
                        showToast('Failed to delete vendor.', 'error');
                    })
                    .finally(function () {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    });
            });
        }

        function bindDeleteButtons() {
            tableBody.querySelectorAll('.delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteVendor(this.dataset.id, this);
                });
            });
        }

        let searchTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchVendors(1);
            }, 300);
        });

        fetchVendors(1);
    }
})();
