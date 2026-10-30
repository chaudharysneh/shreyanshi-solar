@extends('layouts.app')

@section('page_title', 'Email Templates')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
    <style>
        @media (max-width: 767.98px) {
            .marketing-templates-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }

            .marketing-templates-actions {
                width: 100%;
                flex-direction: column;
            }

            .marketing-templates-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 marketing-templates-header">
                    <div>
                        <h4 class="fw-bold mb-0">Email Templates</h4>
                        <p class="text-muted small mb-0">Manage reusable email layouts for campaigns.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 marketing-templates-actions">
                        <a href="{{ route('marketing.templates.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Create Template
                        </a>

                        <a href="{{ route('marketing.campaigns.index') }}" class="btn btn-dark-blue">
                            <i class="bi bi-send-fill me-1"></i>Send Email
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Template Directory</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search templates..."
                            id="templatesSearch">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="templatesTable" class="table table-hover align-middle mb-0 responsive-table">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Template Name</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="templatesTableBody"></tbody>
                    </table>
                </div>
                <div id="templatesPagination" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const tableBody = document.getElementById('templatesTableBody');
            const paginationContainer = document.getElementById('templatesPagination');
            const searchInput = document.getElementById('templatesSearch');

            function renderStatus(status) {
                return status === 'active'
                    ? '<span class="badge crm-status-pill bg-success rounded-pill">Active</span>'
                    : '<span class="badge crm-status-pill bg-secondary rounded-pill">Inactive</span>';
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

            function renderRows(items, meta) {
                if (!items || !items.length) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-file-earmark-richtext display-1 opacity-25"></i></div>
                                <p class="text-muted">No templates found.</p>
                            </td>
                        </tr>`;
                    return;
                }

                tableBody.innerHTML = items.map((template, index) => {
                    const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                    const statusHtml = renderStatus(template.status);
                    const templateType = template.template_name || '-';

                    return `
                        <tr>
                            <td class="ps-4">${rowNumber}</td>
                            <td>
                                <div class="fw-bold small">${escapeHtml(template.name || '-')}</div>
                            </td>
                            <td class="d-none d-md-table-cell">${statusHtml}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <a href="{{ url('marketing/templates') }}/${template.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="{{ url('marketing/templates') }}/${template.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                    <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="{{ url('marketing/templates') }}/${template.id}" title="Delete"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-template-id="${template.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${template.id}" style="display: none;">
                            <td colspan="5" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                            <div class="expand-value">${statusHtml}</div>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ url('marketing/templates') }}/${template.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>
                                            <a href="{{ url('marketing/templates') }}/${template.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="{{ url('marketing/templates') }}/${template.id}"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                tableBody.querySelectorAll('.delete-template').forEach((button) => {
                    button.addEventListener('click', function () {
                        deleteTemplate(this.dataset.url);
                    });
                });

                tableBody.querySelectorAll('.btn-user-expand').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.templateId;
                        const detailsRow = document.getElementById(`details-${id}`);
                        const icon = this.querySelector('i');

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
                if (!paginationContainer || !data || data.total === 0) {
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

                html += data.prev_page_url
                    ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`
                    : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

                for (let i = 1; i <= lastPage; i++) {
                    if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        html += i === currentPage
                            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                            : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                html += data.next_page_url
                    ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`
                    : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

                html += '</ul></div>';
                paginationContainer.innerHTML = html;

                paginationContainer.querySelectorAll('.page-link[data-page]').forEach((link) => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        fetchTemplates(this.dataset.page);
                    });
                });
            }

            function fetchTemplates(page = 1) {
                const url = new URL("{{ route('marketing.templates.index') }}", window.location.origin);
                url.searchParams.set('page', page);

                if (searchInput.value.trim()) {
                    url.searchParams.set('search', searchInput.value.trim());
                }

                $.ajax({
                    url: url.toString(),
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
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
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-5">Error loading templates</td>
                            </tr>`;
                        paginationContainer.innerHTML = '';
                    }
                });
            }

            function deleteTemplate(url) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "DELETE",
                            url: url,
                            data: {
                                "_token": "{{ csrf_token() }}",
                            },
                            success: function (response) {
                                showToast(response.message || 'Template deleted successfully', 'success');
                                fetchTemplates();
                            },
                            error: function () {
                                showToast('Error deleting template', 'error');
                            }
                        });
                    }
                });
            }

            let searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchTemplates(1);
                }, 400);
            });

            fetchTemplates();

        });
    </script>
@endpush
