(function () {
    const INVENTORY_API_BASE = '/api/product-inventory';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHistoryIndex);
    } else {
        initHistoryIndex();
    }

    function initHistoryIndex() {
        const tableBody = document.querySelector('#historyTable tbody');
        const paginationContainer = document.getElementById('historyPagination');
        const productId = document.getElementById('productId').value;

        if (!tableBody || !paginationContainer || !productId) {
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
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No history records found.</p>
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (history, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                let type = 'Create';
                let typeClass = 'bg-secondary';
                
                if (history.type === 'increase') {
                    type = 'IN';
                    typeClass = 'bg-success';
                } else if (history.type === 'decrease') {
                    type = 'OUT';
                    typeClass = 'bg-danger';
                } else if (history.type === 'create') {
                    type = 'Create';
                    typeClass = 'bg-info';
                } else if (history.type === 'update') {
                    type = 'Update';
                    typeClass = 'bg-primary';
                }
                
                const creator = escapeHtml(history.creator || '-');
                const date = escapeHtml(formatDate(history.date));
                let stockChange = '0';
                
                if (history.type === 'increase') {
                    stockChange = `+${history.initial_stock}`;
                } else if (history.type === 'decrease') {
                    stockChange = `-${history.initial_stock}`;
                } else if (history.type === 'create') {
                    stockChange = `+${history.initial_stock}`;
                } else if (history.type === 'update') {
                    stockChange = `±${history.initial_stock}`;
                }

                const historyId = history.id || `hist-${index}`;

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge ${typeClass}">${type}</span>
                        </td>
                        <td class="d-none d-md-table-cell text-center">${history.current_stock || 0}</td>
                        <td class="d-none d-md-table-cell text-center">${stockChange}</td>
                        <td class="d-none d-md-table-cell text-center">${creator}</td>
                        <td class="d-none d-md-table-cell text-center text-end pe-4">${date}</td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-history-id="${historyId}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${historyId}" style="display: none;">
                        <td colspan="7" class="p-0 border-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-boxes"></i> Current Stock :</div>
                                        <div class="expand-value text-end">${history.current_stock || 0}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-plus-minus"></i> Stock Change :</div>
                                        <div class="expand-value text-end">${stockChange}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div>
                                        <div class="expand-value text-end">${creator}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Date :</div>
                                        <div class="expand-value text-end">${date}</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.historyId;
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

            document.querySelectorAll('#historyPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchHistory(this.dataset.page);
                });
            });
        }

        function fetchHistory(page) {
            const url = new URL(`${INVENTORY_API_BASE}/history/${productId}`, window.location.origin);
            url.searchParams.set('page', page || 1);

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
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading history</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        fetchHistory(1);
    }
})();
