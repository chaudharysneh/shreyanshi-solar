@extends('layouts.app')

@section('page_title', 'SMS Marketing Logs')

@push('styles')
    <link rel="stylesheet"
        href="{{ crm_asset('css/users.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        @media (max-width: 767.98px) {
            .sms-logs-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }

            .sms-logs-actions {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .sms-logs-actions .btn {
                width: 100%;
                justify-content: center;
            }

            #sendSmsModal .modal-dialog {
                margin: 0.75rem;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden text-sm">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 sms-logs-header">
                <div>
                    <h4 class="fw-bold mb-0">SMS Marketing</h4>
                    <p class="text-muted small mb-0">Review delivery history and send SMS to selected customers.</p>
                </div>
                <div class="sms-logs-actions">
                    <a href="{{ route('marketing.sms_marketing.templates.create') }}" class="btn btn-dark-blue">
                        Create Template
                    </a>
                    <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
                        Send SMS <i class="bi bi-send ms-1"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">SMS Logs</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search logs..." id="smsLogsSearch">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="smsLogsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Sr.No</th>
                            <th>Customer</th>
                            <th class="d-none d-md-table-cell">Send Date</th>
                            <th>Template Name</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Service</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 100px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="smsLogsTableBody"></tbody>
                </table>
            </div>
            <div id="smsLogsPagination" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 px-4">
                <h5 class="modal-title fw-bold">Send SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="sendSmsForm">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">Select Customers</label>
                        <select name="customer_ids[]" id="customer_ids" class="form-select" multiple placeholder="--Select-Customers--">
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">Select Template</label>
                        <select name="template_id" id="template_id" class="form-select">
                            <option value="">--Select Template--</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 pt-2 d-grid">
                        <button type="submit" class="btn btn-dark-blue" id="btnSendSms">
                            <i class="bi bi-send-fill me-1"></i> Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('smsLogsTableBody');
    const paginationContainer = document.getElementById('smsLogsPagination');
    const searchInput = document.getElementById('smsLogsSearch');

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

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function renderStatus(status) {
        const statusClass = status === 'sent' ? 'bg-success' : 'bg-danger';
        return `<span class="badge crm-status-pill ${statusClass} rounded-pill">${escapeHtml((status || '-').toUpperCase())}</span>`;
    }

    function renderRows(items, meta) {
        if (!items || !items.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted mb-3"><i class="bi bi-chat-dots display-1 opacity-25"></i></div>
                        <p class="text-muted">No SMS logs found.</p>
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = items.map((log, index) => {
            const rowNumber = meta && meta.from ? meta.from + index : index + 1;
            const customerName = log.customer ? log.customer.name : '--';
            const customerPhone = log.customer_phone || '-';
            const statusHtml = renderStatus(log.status);
            const service = log.service ? escapeHtml(log.service) : '--';
            const sendDate = formatDate(log.send_date);

            return `
                <tr>
                    <td class="ps-4">${rowNumber}</td>
                    <td>
                        <div class="fw-bold small">${escapeHtml(customerName)}</div>
                        <div class="text-muted small">${escapeHtml(customerPhone)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">${sendDate}</td>
                    <td>${escapeHtml(log.template_name || '--')}</td>
                    <td class="d-none d-md-table-cell">${statusHtml}</td>
                    <td class="d-none d-md-table-cell text-capitalize">${service}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <button type="button" class="btn crm-action-btn btn-sm text-danger delete-log" data-url="{{ url('sms-marketing/logs') }}/${log.id}" title="Delete"><i class="bi bi-trash"></i></button>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-log-id="${log.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${log.id}" style="display: none;">
                    <td colspan="8" class="p-0 border-0">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Send Date :</div>
                                    <div class="expand-value">${sendDate}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                    <div class="expand-value">${statusHtml}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-tower-broadcast"></i> Service :</div>
                                    <div class="expand-value text-capitalize">${service}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn crm-action-btn btn-sm text-danger delete-log" data-url="{{ url('sms-marketing/logs') }}/${log.id}"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tableBody.querySelectorAll('.delete-log').forEach((button) => {
            button.addEventListener('click', function () {
                deleteLog(this.dataset.url);
            });
        });

        tableBody.querySelectorAll('.btn-user-expand').forEach((button) => {
            button.addEventListener('click', function () {
                const id = this.dataset.logId;
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
                fetchLogs(this.dataset.page);
            });
        });
    }

    function fetchLogs(page = 1) {
        const url = new URL("{{ route('marketing.sms_marketing.logs') }}", window.location.origin);
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
                        <td colspan="8" class="text-center py-5">
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
                        <td colspan="8" class="text-center py-5">Error loading SMS logs</td>
                    </tr>`;
                paginationContainer.innerHTML = '';
            }
        });
    }

    function deleteLog(url) {
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
                    success: function (response) {
                        showToast(response.message || 'SMS log deleted successfully', 'success');
                        fetchLogs();
                    },
                    error: function () {
                        showToast('Error deleting SMS log', 'error');
                    }
                });
            }
        });
    }

    $('#customer_ids').select2({
        theme: 'bootstrap-5',
        placeholder: '--Select-Customers--',
        allowClear: true,
        dropdownParent: $('#sendSmsModal')
    });

    $('#sendSmsForm').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btnSendSms');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Sending...');

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        $.ajax({
            url: "{{ route('marketing.sms_marketing.send_sms') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                showToast(response.message || 'SMS sending completed.', 'success');
                $('#sendSmsForm')[0].reset();
                $('#customer_ids').val(null).trigger('change');
                $('#sendSmsModal').modal('hide');
                fetchLogs();
            },
            error: function (error) {
                if (error.status === 422) {
                    $.each(error.responseJSON.errors, function (key, value) {
                        let input = $('#' + key);
                        if (input.length === 0) input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.parent().append('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                } else {
                    const msg = error.responseJSON ? error.responseJSON.message : 'Something went wrong';
                    showToast(msg, 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#sendSmsModal').on('hidden.bs.modal', function() {
        $('#sendSmsForm')[0].reset();
        $('#customer_ids').val(null).trigger('change');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    });

    let searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            fetchLogs(1);
        }, 400);
    });

    fetchLogs();
});
</script>
@endpush
