@extends('layouts.app')

@section('page_title', 'SMS Marketing')

@push('styles')
    <link rel="stylesheet"
        href="{{ crm_asset('css/users.css') }}">
    <style>
        @media (max-width: 767.98px) {
            .sms-marketing-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }

            .sms-marketing-actions {
                width: 100%;
                flex-direction: column;
            }

            .sms-marketing-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .sms-credentials-actions {
                display: grid;
            }

            .sms-credentials-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 sms-marketing-header">
                <div>
                    <h4 class="fw-bold mb-0">SMS Marketing</h4>
                    <p class="text-muted small mb-0">Manage SMS templates and gateway settings in one place.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 sms-marketing-actions">
                    <a href="{{ route('marketing.sms_marketing.templates.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Create Template
                    </a>
                    <a href="{{ route('marketing.sms_marketing.logs') }}" class="btn btn-dark-blue">
                        <i class="bi bi-send-fill me-1"></i>Send SMS
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <ul class="nav nav-tabs px-4 border-bottom-0" id="smsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if(!request('tab') || request('tab') == 'templates') active @endif fw-bold py-3" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                        Templates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if(request('tab') == 'credentials') active @endif fw-bold py-3" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#credentials" type="button" role="tab">
                        SMS Credentials
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="smsTabsContent">
                <div class="tab-pane fade @if(!request('tab') || request('tab') == 'templates') show active @endif" id="templates" role="tabpanel">
                    <div class="p-4 border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <h6 class="fw-bold mb-0">Template Directory</h6>
                            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                                <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control crm-search-input border-0" placeholder="Search templates..." id="smsTemplatesSearch">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 responsive-table" id="smsTemplatesTable">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                                        <th>Template Name</th>
                                        <th class="d-none d-md-table-cell">Status</th>
                                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Action</th>
                                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="smsTemplatesTableBody"></tbody>
                            </table>
                        </div>

                        <div id="smsTemplatesPagination" class="pt-3"></div>
                    </div>
                </div>

                <div class="tab-pane fade @if(request('tab') == 'credentials') show active @endif" id="credentials" role="tabpanel">
                    <div class="p-4 border-top bg-light">
                        <form action="{{ route('marketing.sms_marketing.save_credentials') }}" method="POST" id="saveCredentialsForm">
                            @csrf
                            <div class="mb-4 pb-3 border-bottom">
                                <h6 class="fw-bold mb-3">Default SMS Service</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Default:</label>
                                        <select name="sms_default_service" class="form-select border shadow-none bg-light">
                                            <option value="twilio" {{ $credentials['sms_default_service'] == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Twilio API Credentials</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio SID:</label>
                                        <input type="text" name="twilio_sid" value="{{ $credentials['twilio_sid'] }}" class="form-control border shadow-none bg-light" placeholder="Enter Twilio SID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio Auth Token:</label>
                                        <input type="password" name="twilio_auth_token" value="{{ $credentials['twilio_auth_token'] }}" class="form-control border shadow-none bg-light" placeholder="Enter Auth Token">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio Phone Number:</label>
                                        <input type="text" name="twilio_phone_number" value="{{ $credentials['twilio_phone_number'] }}" class="form-control border shadow-none bg-light" placeholder="+1234567890">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-2 sms-credentials-actions">
                                <button type="submit" class="btn btn-dark-blue border-0 px-4" id="btnSaveCredentials">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const tableBody = document.getElementById('smsTemplatesTableBody');
    const paginationContainer = document.getElementById('smsTemplatesPagination');
    const searchInput = document.getElementById('smsTemplatesSearch');

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

    function renderStatus(status) {
        return status === 'active'
            ? '<span class="badge crm-status-pill bg-success rounded-pill">Active</span>'
            : '<span class="badge crm-status-pill bg-secondary rounded-pill">Inactive</span>';
    }

    function renderRows(items, meta) {
        if (!items || !items.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="text-muted mb-3"><i class="bi bi-chat-square-text display-1 opacity-25"></i></div>
                        <p class="text-muted">No SMS templates found.</p>
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = items.map((template, index) => {
            const rowNumber = meta && meta.from ? meta.from + index : index + 1;
            const statusHtml = renderStatus(template.status);

            return `
                <tr>
                    <td class="ps-4">${rowNumber}</td>
                    <td>
                        <div class="fw-bold small">${escapeHtml(template.name || '-')}</div>
                    </td>
                    <td class="d-none d-md-table-cell">${statusHtml}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            <a href="{{ url('sms-marketing/templates') }}/${template.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="{{ url('sms-marketing/templates') }}/${template.id}" title="Delete"><i class="bi bi-trash"></i></button>
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
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ url('sms-marketing/templates') }}/${template.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="{{ url('sms-marketing/templates') }}/${template.id}"><i class="bi bi-trash"></i></button>
                                    </div>
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
        const url = new URL("{{ route('marketing.sms_marketing.index') }}", window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('tab', 'templates');

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
                    type: 'DELETE',
                    url: url,
                    data: {
                        '_token': "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        showToast(response.message || 'SMS template deleted successfully', 'success');
                        fetchTemplates();
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                        showToast(msg, 'error');
                    }
                });
            }
        });
    }

    $('#saveCredentialsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveCredentials');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                btn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: msg
                });
            }
        });
    });

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
