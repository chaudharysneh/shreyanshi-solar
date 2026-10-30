(function () {
    const API_BASE = '/api/estimates';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.estimates || {};
        const tableBody = document.querySelector('#estimatesTable tbody');
        const paginationContainer = document.getElementById('estimatesPagination');
        const searchInput = document.getElementById('estimatesSearch');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        let currentPage = 1;
        let searchQuery = searchInput.value;
        
        // Accept existing filter links, then keep the selection in history state.
        const urlParams = new URLSearchParams(window.location.search);
        let currentFilter = urlParams.get('filter') || window.history.state?.estimatesFilter || 'created_by_me';

        function rememberFilter() {
            const newUrl = new URL(window.location);
            newUrl.searchParams.delete('filter');
            window.history.replaceState(
                { ...window.history.state, estimatesFilter: currentFilter }, '', newUrl
            );
        }
        rememberFilter();

        // Activate the selected tab.
        if (currentFilter) {
            document.querySelectorAll('#estimateFilterTabs button[data-filter]').forEach(function(tab) {
                if (tab.dataset.filter === currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        // Tab click handlers
        document.querySelectorAll('#estimateFilterTabs button[data-filter]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                currentPage = 1;
                
                rememberFilter();
                
                loadEstimates();
            });
        });

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

        function getStatusBadge(status) {
            const statusMap = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'approved': '<span class="badge bg-success">Approved</span>',
                'rejected': '<span class="badge bg-danger">Rejected</span>',
                'converted': '<span class="badge bg-info">Converted</span>',
            };
            return statusMap[status] || `<span class="badge bg-secondary">${escapeHtml(status)}</span>`;
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No estimates found.</p>
                            ${permissions.create ? '<a href="/estimates/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Estimate</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (estimate, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const customerName = escapeHtml(estimate.customer?.name || '-');
                const estimateNo = escapeHtml(estimate.estimate_no || '-');
                const estimateDate = escapeHtml(formatDate(estimate.estimate_date));
                const statusBadge = getStatusBadge(estimate.status);

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold small">${customerName}</div>
                        </td>
                        <td class="text-center">${estimateNo}</td>
                        <td class="text-center">${estimateDate}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center pe-4">
                            <div class="d-inline-flex align-items-center gap-2 justify-content-center">
                                ${permissions.edit ? `<a href="/estimates/${estimate.estimate_id}/edit" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/estimates/${estimate.estimate_id}" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.view ? `<a href="/estimates/${estimate.estimate_id}/pdf" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="Download PDF"><i class="bi bi-file-pdf"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${estimate.estimate_id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                    </tr>`;
            }).join('');

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

            if (currentPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="1">First</a></li>`;
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            }

            for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
                if (i === currentPage) {
                    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
            }

            if (currentPage < lastPage) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">Last</a></li>`;
            }

            html += `</ul></div>`;
            paginationContainer.innerHTML = html;

            document.querySelectorAll('.crm-pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentPage = parseInt(this.dataset.page);
                    loadEstimates();
                });
            });
        }

        function loadEstimates() {
            const params = new URLSearchParams({
                search: searchQuery,
                page: currentPage,
            });

            // Add filter parameter for staff users
            if (currentFilter) {
                params.set('filter', currentFilter);
            }

            fetch(`${API_BASE}?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        renderRows(data.data.data, data.data);
                        renderPagination(data.data);
                    }
                })
                .catch(error => console.error('Error loading estimates:', error));
        }

        function bindDeleteButtons() {
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const estimateId = this.dataset.id;
                    if (confirm('Are you sure you want to delete this estimate?')) {
                        fetch(`${API_BASE}/${estimateId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('success', data.message || 'Estimate deleted successfully!', 'Success!');
                                }
                                setTimeout(() => location.reload(), 1000);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('error', 'Failed to delete estimate.');
                            }
                        });
                    }
                });
            });
        }

        searchInput.addEventListener('input', function() {
            searchQuery = this.value;
            currentPage = 1;
            loadEstimates();
        });

        loadEstimates();
    }
})();
