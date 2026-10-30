@extends('layouts.app')

@section('page_title', 'Follow Ups - Create')

@section('content')
    <div class="container-fluid p-0">

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Schedule Follow Up</h1>
                        <p class="text-muted small mb-0">Create a new follow up record for a lead or customer.</p>
                    </div>
                    <a href="{{ route('followups.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/follow-ups" class="needs-validation ajax-followup-form" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead Name <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1 w-100">
                                    <select name="lead_id" id="lead_id" class="form-select select2" required>
                                        <option value="">Select Lead</option>
                                        @foreach ($leads as $lead)
                                            <option value="{{ $lead->id }}" @selected(old('lead_id', request('lead_id')) == $lead->id)>{{ $lead->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addLeadModal" title="Add New Lead">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback d-block" id="lead_id-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned To <span class="text-danger">*</span></label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror" required>
                                    <option value="">-- Search User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback d-block" id="assigned_user_id-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose <span class="text-danger">*</span></label>
                            <input name="purpose" id="purpose" value="{{ old('purpose') }}" class="form-control" placeholder="Enter purpose of follow up" required>
                            <div class="invalid-feedback d-block" id="purpose-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow Up Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="follow_up_at" id="follow_up_at"
                                value="{{ old('follow_up_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required>
                            <div class="invalid-feedback d-block" id="follow_up_at-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="" @selected(old('priority') === null)>Select Priority</option>
                                <option value="low" @selected(old('priority') == 'low')>Low</option>
                                <option value="medium" @selected(old('priority', 'medium') == 'medium')>Medium</option>
                                <option value="high" @selected(old('priority') == 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                {{-- <option value="" disabled>Select Status</option> --}}
                                <option value="pending" @selected(old('status', 'pending') == 'pending')>Pending</option>
                                <option value="resheduled" @selected(old('status') == 'resheduled')>Rescheduled</option>
                                <option value="completed" @selected(old('status') == 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status') == 'cancelled')>Cancelled</option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control" placeholder="Enter any comments or notes">{{ old('comment') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('followups.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Lead Modal -->
    <div class="modal fade" id="addLeadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addLeadQuickForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lead Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_lead_name" required>
                            <div class="invalid-feedback">Please enter lead name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_lead_phone" required>
                            <div class="invalid-feedback">Please enter phone number</div>
                        </div>
                        <div class="mb-3">
                            <a href="#" class="small text-decoration-none" onclick="$('#quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="quick_address_container">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="quick_lead_address" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="saveQuickLeadBtn">Save Lead</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.5.2/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        .select2-results__options {
            max-height: 200px !important;
            overflow-y: auto !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/followup.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#lead_id, #assigned_user_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            // Keep every permitted assignee available when the lead changes.
            // Blade selects the logged-in user by default and preserves old input.
        });

        // Quick Add Lead Modal handling
        $(document).ready(function() {
            $('#saveQuickLeadBtn').click(function() {
                const $btn = $(this);
                const $form = $('#addLeadQuickForm');
                const name = $('#quick_lead_name').val().trim();
                const phone = $('#quick_lead_phone').val().trim();
                const address = $('#quick_lead_address').val().trim();
                
                let isValid = true;
                    
                $('#quick_lead_name').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter lead name');
                $('#quick_lead_phone').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter phone number');
                
                if (!name) {
                    $('#quick_lead_name').addClass('is-invalid');
                    isValid = false;
                }
                
                if (!phone) {
                    $('#quick_lead_phone').addClass('is-invalid');
                    isValid = false;
                }              
                
                if (!isValid) return;
                
                const originalText = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...').prop('disabled', true);
                
                $.ajax({
                    url: '/api/leads',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        name: name,
                        phone: phone,
                        address: address || '--'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const lead = response.data;
                            
                            let selectEl = document.getElementById('lead_id');
                            if (selectEl && selectEl.tomselect) {
                                const idStr = String(lead.id);
                                selectEl.tomselect.addOption({
                                    id: idStr,
                                    name: lead.name
                                });
                                selectEl.tomselect.setValue(idStr);
                            } else {
                                const newOption = new Option(lead.name, lead.id, true, true);
                                $('#lead_id').append(newOption).val(lead.id).trigger('change');
                            }
                            
                            $('#addLeadModal').modal('hide');
                            $form[0].reset();
                            $('#quick_address_container').addClass('d-none');
                            
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', 'Lead added successfully', 'Success');
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        let msg = 'Failed to create lead';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            if (errors.phone) {
                                $('#quick_lead_phone').addClass('is-invalid');
                                $('#quick_lead_phone').siblings('.invalid-feedback').text(errors.phone[0]);
                                msg = errors.phone[0];
                            }
                            if (errors.name) {
                                $('#quick_lead_name').addClass('is-invalid');
                                $('#quick_lead_name').siblings('.invalid-feedback').text(errors.name[0]);
                                msg = errors.name[0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', msg, 'Error');
                        }
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            $('#addLeadModal').on('hidden.bs.modal', function () {
                $('#addLeadQuickForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('#quick_address_container').addClass('d-none');
            });
        });

        // Add browser timezone offset to form submission
        $(document).on('submit', '.ajax-followup-form', function(e) {
            // Get browser timezone offset in minutes
            const offset = new Date().getTimezoneOffset();
            // Add hidden field with timezone offset
            if (!$('input[name="browser_timezone_offset"]').length) {
                $(this).append('<input type="hidden" name="browser_timezone_offset" value="' + offset + '">');
            } else {
                $('input[name="browser_timezone_offset"]').val(offset);
            }
        });
    </script>
@endpush
