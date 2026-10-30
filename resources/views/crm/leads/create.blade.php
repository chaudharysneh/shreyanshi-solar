@extends('layouts.app')

@section('page_title', 'Leads - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Lead</h1>
                        <p class="text-muted small mb-0">Create a new lead entry.</p>
                    </div>
                    <a href="{{ route('leads.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/leads" enctype="multipart/form-data"
                    class="needs-validation ajax-lead-form" novalidate id="leadCreateForm">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="Lead Name" required>
                            <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="-- Search User --">
                                    <option value="">-- Search User --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback" id="assigned_user_id-error">@error('assigned_user_id')
                            {{ $message }} @enderror</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror" placeholder="Email Address">
                            <div class="invalid-feedback" id="email-error">@error('email') {{ $message }} @enderror</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror" placeholder="Phone Number"
                                required>
                            <div class="invalid-feedback" id="phone-error">@error('phone') {{ $message }} @enderror</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">WhatsApp</label>
                            <div class="crm-phone-copy">
                                <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp') }}"
                                class="form-control @error('whatsapp') is-invalid @enderror" placeholder="WhatsApp Number">
                                <button type="button" class="btn btn-sm btn-outline-dark-blue crm-phone-copy-button">Copy Phone</button>
                            </div>
                            <div class="invalid-feedback" id="whatsapp-error">@error('whatsapp') {{ $message }} @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Profile Image</label>
                            <input type="file" name="image" id="image"
                                accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml"
                                class="form-control @error('image') is-invalid @enderror"
                                onchange="previewImage(this, 'leadImagePreview')" style="height: calc(1.5em + 0.75rem + 2px); padding: 0.375rem 0.75rem; line-height: 1.5;">
                            <div class="invalid-feedback d-block" id="image-error">@error('image') {{ $message }} @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                class="form-control @error('company_name') is-invalid @enderror" placeholder="Company Name">
                            <div class="invalid-feedback" id="company_name-error">@error('company_name') {{ $message }}
                            @enderror</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SIC Code</label>
                            <input type="text" name="sic_code" id="sic_code" value="{{ old('sic_code') }}"
                                class="form-control @error('sic_code') is-invalid @enderror" placeholder="SIC Code">
                            <div class="invalid-feedback" id="sic_code-error">@error('sic_code') {{ $message }} @enderror
                            </div>
                        </div>
                        @include('crm.leads.partials.source-master', ['sourceSideBySide' => true, 'sourceColumnClass' => 'col-md-4', 'sourceLabel' => 'Lead Source'])
                        <div class="col-12 col-md-4" id="leadStatusColumn">
                            <label class="form-label fw-semibold">Lead Status</label>
                            @include('crm.leads.partials.status-master')
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address </label>
                            <textarea name="address" id="address"
                                class="form-control @error('address') is-invalid @enderror" rows="1"
                                placeholder="Lead Address">{{ old('address') }}</textarea>
                            <div class="invalid-feedback" id="address-error">@error('address') {{ $message }} @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                rows="1" placeholder="Comments">{{ old('notes') }}</textarea>
                            <div class="invalid-feedback" id="notes-error">@error('notes') {{ $message }} @enderror</div>
                        </div>
                    </div>

                    @include('partials.custom_fields', ['module' => 'Lead'])

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('leads.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('crm.leads.partials.status-master-modal')
    @include('crm.leads.partials.source-master-modal')
    @include('crm.leads.partials.channel-partner-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/lead-status-master.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        /* Clean file input styling */
        .form-control[type="file"] {
            height: calc(1.5em + 0.75rem + 2px) !important;
            padding: 0.375rem 0.75rem !important;
            line-height: 1.5 !important;
            border: 1px solid #dee2e6 !important;
            background-color: #fff !important;
        }
        
        .form-control[type="file"]::-webkit-file-upload-button {
            padding: 0.375rem 0.75rem;
            margin: -0.375rem -0.75rem -0.375rem -0.75rem;
            margin-inline-end: 0.75rem;
            color: #212529;
            background-color: #e9ecef;
            border: 0;
            border-inline-end: 1px solid #dee2e6;
            border-radius: 0.375rem 0 0 0.375rem;
        }
        
        .form-control[type="file"]:hover::-webkit-file-upload-button {
            background-color: #ddd;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/leads.js') }}"></script>
    <script src="{{ crm_asset('js/lead-status-master.js') }}"></script>
    <script src="{{ crm_asset('js/lead-source-master.js') }}"></script>
    <script src="{{ crm_asset('js/lead-source-details.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#assigned_user_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    </script>
@endpush
