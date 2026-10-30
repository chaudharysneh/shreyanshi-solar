@extends('layouts.app')

@section('page_title', 'Add Meeting')

@section('content')
    <div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Add Meeting</h1>
                    <p class="text-muted small mb-0">Schedule a new meeting with a customer.</p>
                </div>
                <div class="d-flex w-100 w-md-auto gap-2">
                    <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue back-btn flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">

                <form method="POST" action="/api/meetings" id="meetingForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned For <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1 w-100">
                                    <select name="customer_id" id="customer_id" class="form-select" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                                data-phone="{{ $customer->phone }}">
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id" class="form-select">
                                    <option value="">Select Staff</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback" id="assigned_user_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="Enter meeting title" required>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="scheduled" selected>Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Scheduled On <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                            <div class="invalid-feedback" id="scheduled_at-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Type </label>
                            <select name="meeting_type" id="meeting_type" class="form-select">
                                <option value="">Select Meeting Type</option>
                                <option value="virtual">Virtual</option>
                                <option value="in-person">In-person</option>
                                <option value="telephonic">Telephonic</option>
                            </select>
                            <div class="invalid-feedback" id="meeting_type-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Agenda </label>
                            <textarea name="agenda" id="agenda" rows="4" class="form-control" placeholder="Enter meeting agenda"></textarea>
                            <div class="invalid-feedback" id="agenda-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" id="address" rows="4" class="form-control" placeholder="Enter meeting location/address"></textarea>
                            <div class="invalid-feedback" id="address-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('meetings.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>
            </div>
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
        .select2-results__options {
            max-height: 200px !important;
            overflow-y: auto !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/meeting.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#customer_id, #assigned_user_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
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
                
                if (!address) {
                    address = 'Address';
                }
                
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
                            let selectEl = document.getElementById('customer_id');
                            if (selectEl && selectEl.tomselect) {
                                const idStr = String(res.data.id);
                                selectEl.tomselect.addOption({
                                    id: idStr, 
                                    name: res.data.name, 
                                    email: res.data.email || '', 
                                    phone: res.data.phone || ''
                                });
                                selectEl.tomselect.setValue(idStr);
                            } else {
                                let newOption = new Option(res.data.name, res.data.id, true, true);
                                $(newOption).attr('data-email', res.data.email || '');
                                $(newOption).attr('data-phone', res.data.phone || '');
                                $('#customer_id').append(newOption).trigger('change');
                            }
                            
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
