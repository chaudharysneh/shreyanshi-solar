@extends('layouts.app')

@section('page_title', 'Profile')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/profile.css') }}">
@endpush

@section('content')
@php
    $roleName = auth()->user()->isAdmin()
        ? 'Administrator'
        : (auth()->user()->roles->first()?->name
            ? \Illuminate\Support\Str::headline(auth()->user()->roles->first()->name)
            : (auth()->user()->job_title ?: 'Staff'));

    $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=ffffff&size=128';
    $defaultLogo = 'https://ui-avatars.com/api/?name=' . urlencode($settings['company_name'] ?? 'Company') . '&background=3b82f6&color=ffffff&size=128';
    $defaultQr = 'https://ui-avatars.com/api/?name=QR&background=3b82f6&color=ffffff&size=128'; // Placeholder for default QR
    
    $avatarUrl = !empty($user->avatar_path)
        ? route('users.image', $user) . '?v=' . optional($user->updated_at)->timestamp
        : $defaultAvatar;

    $companyLogoPath = $settings['company_logo_path'] ?? null;
    $companyLogoUrl = $companyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
        ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyLogoPath)
        : $defaultLogo;

    $sidebarIconPath = $settings['sidebar_icon_path'] ?? null;
    $sidebarIconUrl = $sidebarIconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($sidebarIconPath)
        ? route('profile.sidebar_icon.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($sidebarIconPath)
        : null;

    $companyQrCodePath = $settings['company_qr_code_path'] ?? null;
    $companyQrCodeUrl = $companyQrCodePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyQrCodePath)
        ? route('profile.company_qr_code.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyQrCodePath)
        : null; // Or default QR if you have one
@endphp

<div class="profile-shell">
    <div class="profile-hero">
        <div class="profile-hero-card d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $avatarUrl }}" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';" alt="{{ $user->name }}" class="profile-avatar-mini">
                <div>
                    <h4 class="profile-hero-name">{{ $user->name }}</h4>
                    <div class="text-muted">({{ ucfirst($roleName) }})</div>
                </div>
            </div>
            <button type="button" class="profile-dark-btn profile-password-btn" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="bi bi-key-fill"></i>
                <span>Change Password</span>
            </button>
        </div>
    </div>

    <form id="profileForm" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="card profile-card">
            <div class="profile-card-head">UPDATE PROFILE</div>
            <div class="card-body p-3 p-md-4">
                <div class="profile-section">
                    <div class="profile-section-label">USER INFORMATION</div>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-3 profile-image-col">
                            <h5 class="profile-image-title">Profile Image</h5>
                            <img src="{{ $avatarUrl }}" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';" alt="Profile" class="profile-circle-image" id="avatar-preview">
                        </div>
                        <div class="col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                                    @error('name')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email address</label>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                                    @error('email')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Contact No.</label>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                    @error('phone')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Profile Image (Upload)</label>
                                    <input type="file" name="avatar" id="avatar-input" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                                    @error('avatar')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">Any image file - max 50 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()?->isAdmin())
                <div class="profile-section">
                    <div class="profile-section-label">COMPANY INFORMATION</div>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-3 profile-image-col">
                            <h5 class="profile-image-title">Company Logo</h5>
                            <img src="{{ $companyLogoUrl }}" onerror="this.onerror=null;this.src='{{ $defaultLogo }}';" alt="Company Logo" class="profile-circle-image" id="company-logo-preview">
                        </div>
                        <div class="col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Name</label>
                                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-control @error('company_name') is-invalid @enderror">
                                    @error('company_name')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Tagline</label>
                                    <input type="text" name="company_tagline" value="{{ old('company_tagline', $settings['company_tagline'] ?? '') }}" class="form-control @error('company_tagline') is-invalid @enderror">
                                    @error('company_tagline')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Address</label>
                                    <input type="text" name="company_address" value="{{ old('company_address', $settings['company_address'] ?? '') }}" class="form-control @error('company_address') is-invalid @enderror">
                                    @error('company_address')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Tax No.</label>
                                    <input type="text" name="company_tax_id" value="{{ old('company_tax_id', $settings['company_tax_id'] ?? '') }}" class="form-control @error('company_tax_id') is-invalid @enderror">
                                    @error('company_tax_id')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Logo (Upload)</label>
                                    <input type="file" name="company_logo_path" id="company-logo-input" accept="image/jpeg,image/png,image/jpg" class="form-control @error('company_logo_path') is-invalid @enderror">
                                    @error('company_logo_path')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">JPG or PNG image - max 50 MB.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="sidebar-icon-input" class="form-label fw-semibold">Company Logo (Small)</label>
                                    <div class="d-flex align-items-center gap-2">
                                    <input type="file" name="sidebar_icon_path" id="sidebar-icon-input" accept="image/jpeg,image/png,image/jpg" class="form-control @error('sidebar_icon_path') is-invalid @enderror">
                                        <div class="border rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-white" style="width:45px;height:45px;padding:4px;" aria-label="Small company logo preview">
                                            <img src="{{ $sidebarIconUrl ?: '' }}" alt="Company Logo (Small)" id="sidebar-icon-preview" class="{{ $sidebarIconUrl ? '' : 'd-none' }}" style="width:100%;height:100%;object-fit:contain;">
                                            <i id="sidebar-icon-placeholder" class="bi bi-image text-muted {{ $sidebarIconUrl ? 'd-none' : '' }}" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                    @error('sidebar_icon_path')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">Any image file - max 50 MB. (Used for small sidebar icon)</small>
                                </div>
                                <!-- <div class="col-md-6">
                                    <label class="form-label fw-semibold">QR Code (Upload)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="file" name="company_qr_code_path" id="company-qr-input" accept="image/*" class="form-control @error('company_qr_code_path') is-invalid @enderror">
                                        <div class="profile-qr-preview-container">
                                            <img src="{{ $companyQrCodeUrl ?: '' }}" alt="QR Code" class="profile-qr-mini {{ $companyQrCodeUrl ? '' : 'd-none' }}" id="qr-preview">
                                            @if(!$companyQrCodeUrl)
                                                <div class="profile-qr-mini-placeholder d-flex align-items-center justify-content-center" id="qr-placeholder">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @error('company_qr_code_path')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">Any image file - max 50 MB.</small>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="profile-section-label">SOCIAL LINKS</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-instagram me-1"></i> Instagram</label>
                            <input type="text" name="social_instagram" value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}" class="form-control" placeholder="https://www.instagram.com/">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-facebook me-1"></i> Facebook</label>
                            <input type="text" name="social_facebook" value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}" class="form-control" placeholder="https://www.facebook.com/">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-linkedin me-1"></i> LinkedIn</label>
                            <input type="text" name="social_linkedin" value="{{ old('social_linkedin', $settings['social_linkedin'] ?? '') }}" class="form-control" placeholder="https://www.linkedin.com/">
                        </div>
                    </div>
                </div>
                @endif

                <div class="profile-submit-wrap">
                    <button type="submit" class="btn btn-dark-blue profile-submit-btn">Update</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade profile-password-modal" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('profile.password.update') }}" id="changePasswordForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('current_password')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" class="form-control" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue px-4 rounded-3 profile-submit-btn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.profilePageConfig = {
    updateUrl: @json(route('api.profile.update')),
    passwordUrl: @json(route('api.profile.password.update')),
    openPasswordModal: @json($errors->has('current_password') || $errors->has('password')),
};
</script>
<script src="{{ crm_asset('js/profile.js') }}"></script>
@endpush
