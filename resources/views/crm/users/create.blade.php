@extends('layouts.app')

@section('page_title', 'Add Staff')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/users-form.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-form-card">
            
            <!-- Card Header -->
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Staff</h1>
                        <p class="text-muted small mb-0">Create a new staff user and assign module access.</p>
                    </div>
                    <a href="{{ route('users.index') }}" class="btn btn-dark-blue staff-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-3 p-md-4">
                <form action="{{ url('/api/users') }}" method="POST" enctype="multipart/form-data" id="staffCreateForm" class="ajax-user-form">

                    @csrf

                    <!-- Step Tabs -->
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-2" id="staffFormSteps">
                            <button type="button" class="btn btn-outline-dark-blue active" data-step="1">Personal Details</button>
                            <button type="button" class="btn btn-outline-dark-blue" data-step="2">Permissions</button>
                        </div>
                    </div>

                    <!-- Step 1: Personal Details -->
                    <div class="staff-form-step" data-step="1">
                        <div class="border-bottom mb-4 pb-3">
                            <h5 class="mb-1">Personal Details</h5>
                            <p class="text-muted small mb-0">Enter staff name, email, phone and basic profile information.</p>
                        </div>

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="name">
                                    <i class="bi bi-person-fill"></i> Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter staff name">
                                @error('name')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="email">
                                    <i class="bi bi-envelope-fill"></i> Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Enter email address">
                                @error('email')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="password">
                                    <i class="bi bi-key-fill"></i> Password <span class="text-danger">*</span>
                                </label>
                                <div class="password-field-wrap position-relative">
                                    <input type="password" id="password" name="password" class="form-control pe-5 @error('password') is-invalid @enderror" placeholder="Enter password" autocomplete="new-password">
                                    <button type="button" class="password-toggle-btn position-absolute top-50 end-0 translate-middle-y me-3" id="toggleCreatePassword" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye-slash-fill"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="phone">
                                    <i class="bi bi-telephone-fill"></i> Phone no. <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="10" placeholder="Enter 10-digit phone number">
                                @error('phone')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- WhatsApp -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="whatsapp">
                                    <i class="bi bi-whatsapp"></i> WhatsApp no.
                                </label>
                                <div class="crm-phone-copy">
                                    <input type="text" id="whatsapp" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp') }}" maxlength="10" placeholder="Enter 10-digit WhatsApp number">
                                    <button type="button" class="btn btn-sm btn-outline-dark-blue crm-phone-copy-button">Copy Phone</button>
                                </div>
                                @error('whatsapp')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Image -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center gap-2" for="image">
                                    <i class="bi bi-person-circle"></i> Profile Image
                                </label>
                                <input type="file" id="image" name="image" class="form-control @error('image') is-invalid @enderror" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg">
                                @error('image')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                                
                                <div class="staff-avatar-preview mt-2" id="createImagePreviewWrap" style="display: none;">
                                    <img src="" alt="Staff preview" id="createImagePreview" class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label class="form-label d-flex align-items-center gap-2" for="address">
                                    <i class="bi bi-geo-alt-fill"></i> Address 
                                </label>
                                <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" placeholder="Enter address details">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="staff-validation">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Permissions -->
                    <div class="staff-form-step d-none" data-step="2">
                        <div class="border-bottom mb-4 pb-3">
                            <h5 class="mb-1">Permissions</h5>
                            <p class="text-muted small mb-0">Assign module access and permissions for this staff member.</p>
                        </div>

                        <!-- Permissions Section -->
                        @include('crm.users.partials.permission-matrix', [
                            'permissionMatrix' => $permissionMatrix ?? [],
                            'permissionActions' => $permissionActions ?? [],
                            'selectedPermissions' => old('permissions', []),
                        ])
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-4 pt-4 border-top d-flex gap-2 form-actions">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-dark-blue cancel-step flex-grow-1 flex-sm-grow-0">Cancel</a>
                        <div class="me-sm-auto d-none d-sm-block"></div>
                        <button type="button" class="btn btn-outline-dark-blue prev-step d-none flex-grow-1 flex-sm-grow-0">Previous</button>
                        <button type="button" class="btn btn-dark-blue next-step flex-grow-1 flex-sm-grow-0">Next</button>
                        <button type="submit" class="btn btn-dark-blue d-none flex-grow-1 flex-sm-grow-0">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ crm_asset('js/users.js') }}"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('staffCreateForm');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('toggleCreatePassword');
        const imageInput = document.getElementById('image');
        const imagePreviewWrap = document.getElementById('createImagePreviewWrap');
        const imagePreview = document.getElementById('createImagePreview');

        // Real-time field validation
        function validateField(field) {
            if (!field) return;

            const value = field.value.trim();
            let isValid = true;

            switch (field.name) {
                case 'name':
                case 'email':
                    isValid = value.length > 0;
                    break;
                case 'password':
                    isValid = value.length >= 8;
                    break;
                case 'phone':
                    isValid = /^\d{10}$/.test(value);
                    break;
                case 'whatsapp':
                    isValid = value === '' || /^\d{10}$/.test(value);
                    break;
            }

            field.classList.toggle('is-invalid', !isValid);

            // Hide/show custom error message
            const errorDiv = field.parentElement.querySelector('.staff-validation');
            if (errorDiv) {
                errorDiv.style.display = isValid ? 'none' : 'block';
            }
        }

        // Password Toggle
        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';

                this.innerHTML = isPassword
                    ? '<i class="bi bi-eye-fill"></i>'
                    : '<i class="bi bi-eye-slash-fill"></i>';
            });
        }

        // Image Preview
        if (imageInput && imagePreview && imagePreviewWrap) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                
                if (file) {
                    imagePreview.src = URL.createObjectURL(file);
                    imagePreviewWrap.style.display = 'block';
                } else {
                    imagePreview.src = '';
                    imagePreviewWrap.style.display = 'none';
                }
            });
        }

        // Attach validation to all inputs and textarea
        if (form) {
            const fields = form.querySelectorAll('input, textarea');
            
            fields.forEach(field => {
                field.addEventListener('input', () => validateField(field));
                field.addEventListener('blur', () => validateField(field));
            });
        }
    });
    </script>
@endpush