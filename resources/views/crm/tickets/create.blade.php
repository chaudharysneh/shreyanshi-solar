@extends('layouts.app')

@section('page_title', 'Raise Ticket')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden ticket-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Raise Ticket</h1>
                        <p class="text-muted small mb-0">Create a new support request.</p>
                    </div>
                    <a href="{{ route('tickets.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form id="ticketForm" action="/api/tickets" method="POST" class="needs-validation ajax-ticket-form"
                    data-redirect="{{ route('tickets.index') }}" novalidate>
                    @csrf
                    <div id="formErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="customer_id" class="form-label d-flex align-items-center gap-2 fw-semibold">
                                <i class="fa-solid fa-user"></i> Customer <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1 w-100">
                                    <select name="customer_id" id="customer_id" class="form-select" required>
                                        <option value="">-- Search Customer --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                                data-phone="{{ $customer->phone }}" data-name="{{ $customer->name }}"
                                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}{{ $customer->phone ? ' (' . $customer->phone . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="customer_id-error"></div>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        @include('crm.tickets.assigned-field')
                        <div class="col-md-6">
                            <label for="ticket_name" class="form-label d-flex align-items-center gap-2 fw-semibold">
                                <i class="fa-solid fa-ticket"></i> Ticket Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="ticket_name" id="ticket_name" class="form-control"
                                value="{{ old('ticket_name') }}" placeholder="Enter ticket title" required>
                            <div class="invalid-feedback" id="ticket_name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="description" class="form-label d-flex align-items-center gap-2 fw-semibold">
                                <i class="fa-solid fa-align-left"></i> Ticket Description
                            </label>
                            <textarea name="description" id="description" rows="1" class="form-control" placeholder="Enter ticket description">{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="priority" class="form-label d-flex align-items-center gap-2 fw-semibold">
                                <i class="fa-solid fa-circle-exclamation"></i> Priority <span class="text-danger">*</span>
                            </label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="">Select Priority</option>
                                <option value="Low" {{ old('priority', 'Medium') == 'Low' ? 'selected' : '' }}>Low</option>
                                <option value="Medium" {{ old('priority', 'Medium') == 'Medium' ? 'selected' : '' }}>Medium
                                </option>
                                <option value="High" {{ old('priority', 'Medium') == 'High' ? 'selected' : '' }}>High</option>
                            </select>
                            <div class="invalid-feedback" id="priority-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label d-flex align-items-center gap-2 fw-semibold">
                                <i class="fa-solid fa-circle-info"></i> Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                @foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status)
                                    <option value="{{ $status }}" {{ old('status', 'Open') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Create Ticket</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addCustomerQuickForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_name" required>
                            <div class="invalid-feedback">Please enter customer name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_number" required>
                            <div class="invalid-feedback">Please enter mobile number</div>
                        </div>
                        <div class="mb-3">
                            <a href="#" class="small text-decoration-none" onclick="$('#quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="quick_address_container">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="quick_customer_address" rows="2" placeholder="Address"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="saveQuickCustomerBtn">Save Customer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-results__options { max-height: 200px !important; overflow-y: auto !important; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/tickets-api.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('ticketForm');
            if (!form) return;

            const validationMessages = {
                'customer_id': 'Customer name is required',
                'ticket_name': 'Ticket name is required',
                
                'priority': 'Priority is required',
                'status': 'Status is required'
            };

            const validateField = (field) => {
                const errorDiv = document.getElementById(`${field.name}-error`);
                if (!errorDiv) return;

                if (field.required && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    if (field.tomselect) {
                        field.nextElementSibling.classList.add('is-invalid');
                    }
                    if ($(field).data('select2')) {
                        $(field).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                    }
                    errorDiv.textContent = validationMessages[field.name] || `${field.name.replace('_', ' ')} is required`;
                    return false;
                } else {
                    field.classList.remove('is-invalid');
                    if (field.tomselect) {
                        field.nextElementSibling.classList.remove('is-invalid');
                    }
                    if ($(field).data('select2')) {
                        $(field).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                    }
                    errorDiv.textContent = '';
                    return true;
                }
            };

            form.addEventListener('submit', function(e) {
                let isValid = true;
                ['customer_id', 'ticket_name', 'priority', 'status'].forEach(fieldName => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field && !validateField(field)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Real-time validation
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('change', () => validateField(field));
                field.addEventListener('input', () => validateField(field));
            });
            
            // Select2 init
            $('#customer_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            const ticketNameInput = document.getElementById('ticket_name');
            let generatedTicketName = '';
            function updateTicketName() {
                const option = document.getElementById('customer_id').selectedOptions[0];
                const customerName = option?.dataset.name || option?.textContent.trim().replace(/\s*\([^)]*\)$/, '') || '';
                const nextName = option?.value ? `Ticket - ${customerName}` : '';
                if (!ticketNameInput.value.trim() || ticketNameInput.value === generatedTicketName) {
                    ticketNameInput.value = nextName;
                    if (nextName) validateField(ticketNameInput);
                }
                generatedTicketName = nextName;
            }
            $('#customer_id').on('change', function() { validateField(this); updateTicketName(); });
            updateTicketName();

            // Quick Add Customer logic
            $('#saveQuickCustomerBtn').click(function() {
                let name = $('#quick_customer_name').val().trim();
                let number = $('#quick_customer_number').val().trim();
                let address = $('#quick_customer_address').val().trim();
                
                $('#quick_customer_name').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter customer name');
                $('#quick_customer_number').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter mobile number');
                
                if (!name || !number) {
                    if(!name) $('#quick_customer_name').addClass('is-invalid');
                    if(!number) $('#quick_customer_number').addClass('is-invalid');
                    return;
                }
                
                if (!address) { address = 'Address'; }
                
                let btn = $(this);
                let originalText = btn.data('original-text') || btn.html();
                btn.data('original-text', originalText);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.ajax({
                    url: '/api/customers',
                    type: 'POST',
                    data: {
                        name: name,
                        phone: number,
                        address: address,
                        status: 'active',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success && res.data) {
                            let newOption = new Option(res.data.name + (res.data.phone ? ' (' + res.data.phone + ')' : ''), res.data.id, true, true);
                            $(newOption).attr('data-name', res.data.name);
                            $(newOption).attr('data-email', res.data.email || '');
                            $(newOption).attr('data-phone', res.data.phone || '');
                            $('#customer_id').append(newOption).trigger('change');
                            
                            $('#addCustomerModal').modal('hide');
                            $('#addCustomerQuickForm')[0].reset();
                            if (!window.customerSuccessToastShown) {
                                window.customerSuccessToastShown = true;
                                if (window.showAlert) window.showAlert('success', 'Customer added successfully');
                                setTimeout(() => window.customerSuccessToastShown = false, 1000);
                            }
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = xhr.responseJSON?.message || 'Failed to add customer';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            if (errors.phone) {
                                $('#quick_customer_number').addClass('is-invalid');
                                $('#quick_customer_number').siblings('.invalid-feedback').text(errors.phone[0]);
                                errorMessage = errors.phone[0];
                            }
                            if (errors.name) {
                                $('#quick_customer_name').addClass('is-invalid');
                                $('#quick_customer_name').siblings('.invalid-feedback').text(errors.name[0]);
                                errorMessage = errors.name[0];
                            }
                        }
                        if (window.showAlert) window.showAlert('error', errorMessage);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endpush
