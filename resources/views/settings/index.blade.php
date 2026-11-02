@extends('layouts.app')

@section('page_title', 'Settings')

@push('styles')
    <link rel="stylesheet"
        href="{{ crm_asset('css/users.css') }}">
    <link rel="stylesheet"
        href="{{ crm_asset('css/settings.css') }}">
@endpush

@section('content')
    @php
        $logoPath = $settings['company_logo_path']->value ?? null;
        $logoUrl =
            $logoPath && Storage::disk('public')->exists($logoPath)
                ? asset($logoPath)
                : 'https://crm-demo.fableadtech.com/public/assets/img/logos/fabcrmlogo.png';
    @endphp

    <div class="container-fluid px-0 settings-shell">
        <div class="d-flex justify-content-between align-items-center mb-3 settings-page-head">
            <div>
                <h1 class="h4 mb-1">Settings</h1>
                <p class="text-muted small mb-0">Manage SMTP, keys, WhatsApp and integration preferences.</p>
            </div>
        </div>

                <div class="settings-tabs-wrap">
            <ul class="nav settings-main-tabs flex-wrap" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#integrations-main" type="button" role="tab">Integrations</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#financial-information" type="button" role="tab">Financial Information</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table-truncate" type="button" role="tab">Table Clear</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#estimate-invoice-setting" type="button" role="tab">Estimate/Invoice setting</button>
                </li>
            </ul>
        </div>

                <div class="tab-content">
            <!-- 1. Integrations Main Tab -->
            <div class="tab-pane fade show active" id="integrations-main" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head">Integrations</div>
                    <div class="settings-panel-body">
                        <ul class="nav settings-subtabs settings-integration-subtabs mb-4" id="integrationSettingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="social-media-integration-tab" data-bs-toggle="pill" data-bs-target="#integrations" type="button" role="tab">Social Media Integration</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="whatsapp-integration-tab" data-bs-toggle="pill" data-bs-target="#whatsapp-configure" type="button" role="tab">WhatsApp Integration</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="smtp-tab" data-bs-toggle="pill" data-bs-target="#smtp" type="button" role="tab">Email SMTP</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="google-connection-tab" data-bs-toggle="pill" data-bs-target="#google-connection" type="button" role="tab">Google Connection</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="integrationSettingsTabContent">
                            <div class="tab-pane fade show active" id="integrations" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center">
                        <span>Integrations</span>
                        <div class="form-check form-switch form-check-reverse mb-0">
                            <input class="form-check-input integration-status-toggle" type="checkbox" role="switch" id="socialMediaIntegrationToggle" data-integration="social_media_integration" {{ $integrationSettings->social_media_integration ? 'checked' : '' }} style="cursor: pointer;">
                            <label class="form-check-label small fw-semibold text-muted me-2" for="socialMediaIntegrationToggle" id="socialMediaIntegrationToggleLabel" style="cursor: pointer;">
                                {{ $integrationSettings->social_media_integration ? 'Enable' : 'Disable' }}
                            </label>
                        </div>
                    </div>
                    <div class="settings-panel-body">
                        <div class="integrations-accordion" id="integrationsAccordion">
                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#facebookAdsPanel" aria-expanded="false"
                                    aria-controls="facebookAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon facebook"><i class="bi bi-facebook"></i></span>
                                        <span>Facebook Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="facebookAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy">This integration lets you connect your Facebook Ads
                                            account to manage campaigns and track conversions.</div>

                                        <div class="integration-inner-card">
                                            <button class="integration-inner-toggle" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#facebookConnectedPages"
                                                aria-expanded="false" aria-controls="facebookConnectedPages">
                                                <span>Connected Pages</span>
                                                <i class="bi bi-chevron-down integration-inner-caret"></i>
                                            </button>
                                            <div id="facebookConnectedPages" class="collapse integration-inner-collapse">
                                                <div class="table-responsive">
                                                    <table class="table integration-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 48px;">#</th>
                                                                <th style="width: 80px;">Image</th>
                                                                <th style="width: 240px;">Page ID</th>
                                                                <th>Page Name</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>1</td>
                                                                <td><span class="integration-mini-icon facebook"><i
                                                                            class="bi bi-facebook"></i></span></td>
                                                                <td>681579935046274</td>
                                                                <td>Hello test bhavvik</td>
                                                            </tr>
                                                            <tr>
                                                                <td>2</td>
                                                                <td><span class="integration-mini-icon whatsapp"><i
                                                                            class="bi bi-megaphone-fill"></i></span></td>
                                                                <td>727332373793917</td>
                                                                <td>Testing page</td>
                                                            </tr>
                                                            <tr>
                                                                <td>3</td>
                                                                <td><span class="integration-mini-icon analytics"><i
                                                                            class="bi bi-bar-chart-fill"></i></span></td>
                                                                <td>909114223345667</td>
                                                                <td>Demo Campaign Hub</td>
                                                            </tr>
                                                            <tr>
                                                                <td>4</td>
                                                                <td><span class="integration-mini-icon audience"><i
                                                                            class="bi bi-people-fill"></i></span></td>
                                                                <td>555666777888999</td>
                                                                <td>Demo Lead Gen Page</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="integration-action">
                                            <a href="https://www.facebook.com/login.php" target="_blank"
                                                rel="noopener noreferrer" class="btn btn-primary settings-submit-btn"><i
                                                    class="bi bi-facebook me-1"></i> Connect with Facebook Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#googleAdsPanel" aria-expanded="false"
                                    aria-controls="googleAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon google">G</span>
                                        <span>Google Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="googleAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect your Google Ads workspace to sync
                                            campaigns, account IDs and conversion reporting.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://myaccount.google.com/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-outline-primary rounded-pill px-4">Connect Google Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#instagramAdsPanel" aria-expanded="false"
                                    aria-controls="instagramAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon instagram"><i class="bi bi-instagram"></i></span>
                                        <span>Instagram Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="instagramAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect Instagram Ads to manage campaign sources
                                            and social lead capture from a single place.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://www.instagram.com/accounts/login/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-outline-primary rounded-pill px-4">Connect Instagram Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                            <div class="tab-pane fade" id="whatsapp-configure" role="tabpanel">
                
                        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="fw-bold mb-1">Configure your WhatsApp API settings <span
                                        class="settings-status-badge settings-inline-status"><span
                                            class="settings-status-dot"></span>Connected</span></h4>
                                <div class="text-muted">Enter your WhatsApp App details and WhatsApp Business credentials.
                                </div>
                            </div>
                            <div class="form-check form-switch form-check-reverse mb-0">
                                <input class="form-check-input integration-status-toggle" type="checkbox" role="switch" id="whatsappIntegrationToggle" data-integration="whatsapp_integration" {{ $integrationSettings->whatsapp_integration ? 'checked' : '' }} style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-muted me-2" for="whatsappIntegrationToggle" id="whatsappIntegrationToggleLabel" style="cursor: pointer;">
                                    {{ $integrationSettings->whatsapp_integration ? 'Enable' : 'Disable' }}
                                </label>
                            </div>
                        </div>

                        <div id="whatsappIntegrationFields">
                        <ul class="nav settings-subtabs mb-4" id="waSettingsTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="wa-config-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-config-pane" type="button"
                                    role="tab">Configuration</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="wa-templates-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-templates-pane" type="button"
                                    role="tab">Message Templates</button></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="wa-config-pane" role="tabpanel">
                                <div class="alert alert-primary d-flex align-items-start mb-4">
                                    <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                                    <div><strong>Credentials are stored locally.</strong>
                                        <div>Use backend storage for production and update credentials whenever you rotate
                                            tokens.</div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-app me-2"></i>WhatsApp App ID
                                        </label>
                                        <input type="text" class="form-control" id="wa_app_id">
                                        <div class="invalid-feedback" id="wa_app_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-shield-lock-fill me-2"></i>WhatsApp App
                                            Secret <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_app_secret">
                                        <div class="invalid-feedback" id="wa_app_secret_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-telephone-fill me-2"></i>Phone
                                            Number ID </label>
                                        <input type="text" class="form-control" id="wa_phone_number_id">
                                        <div class="invalid-feedback" id="wa_phone_number_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-building-fill me-2"></i>WhatsApp
                                            Business Account ID </label>
                                        <input type="text" class="form-control" id="wa_business_account_id">
                                        <div class="invalid-feedback" id="wa_business_account_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-key-fill me-2"></i>Access Token
                                        </label>
                                        <input type="text" class="form-control" id="wa_access_token">
                                        <div class="invalid-feedback" id="wa_access_token_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-link-45deg me-2"></i>Webhook
                                            URL</label>
                                        <input type="text" class="form-control" id="wa_webhook_url">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4">
                                    <span class="small" id="wa_status_msg"></span>
                                    <button type="button" class="btn btn-primary settings-submit-btn"
                                        id="wa_save_btn"><i class="bi bi-floppy-fill me-1"></i> Save
                                        Configuration</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="wa-templates-pane" role="tabpanel">
                                <div class="wa-templates-title">WhatsApp Message Templates</div>
                                <div class="wa-templates-subtitle">View and manage your WhatsApp message templates from
                                    WhatsApp. Stored in database; refresh to sync from API.</div>

                                <div class="wa-templates-toolbar">
                                    <div class="wa-templates-toolbar-left">
                                        <button type="button" class="btn btn-primary wa-templates-refresh-btn"
                                            id="wa_templates_refresh"><i class="bi bi-arrow-repeat me-1"></i> Refresh
                                            Templates</button>
                                        <div class="wa-templates-search-wrap"><input type="text" class="form-control"
                                                id="wa_templates_search" placeholder="Search templates..."></div>
                                    </div>
                                    <div class="wa-templates-show"><span>Show</span><select id="wa_templates_show"
                                            class="form-select">
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select><span>entries</span></div>
                                </div>

                                <div class="table-responsive wa-templates-table-wrap">
                                    <table class="table align-middle mb-0" id="wa_templates_table"
                                        data-module-options='@json($whatsappModuleOptions)'>
                                        <thead class="table-light">
                                            <tr>
                                                <th>Templates</th>
                                                <th class="d-none d-md-table-cell" style="width: 30%;">Use For Module</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Status</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Active/Inactive
                                                </th>
                                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($whatsappTemplates as $template)
                                                <tr data-template-row="main" data-template-id="{{ $template->id }}">
                                                    <td class="wa-templates-name">{{ $template->name }}</td>
                                                    <td class="d-none d-md-table-cell">
                                                        <select
                                                            class="form-select form-select-sm wa-template-module-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="">-- Select --</option>
                                                            @foreach ($whatsappModuleOptions as $key => $label)
                                                                <option value="{{ $key }}"
                                                                    {{ $template->use_for_module === $key ? 'selected' : '' }}>
                                                                    {{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><span
                                                            class="wa-templates-status-badge">{{ $template->status }}</span>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><select
                                                            class="form-select form-select-sm wa-template-status-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="1"
                                                                {{ $template->is_active ? 'selected' : '' }}>
                                                                Active</option>
                                                            <option value="0"
                                                                {{ !$template->is_active ? 'selected' : '' }}>
                                                                Inactive</option>
                                                        </select></td>
                                                    <td class="text-center d-md-none">
                                                        <button type="button" class="btn-user-expand"
                                                            data-template-id="{{ $template->id }}">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="details-row d-md-none border-0" data-template-row="details"
                                                    id="wa-template-details-{{ $template->id }}" style="display: none;">
                                                    <td colspan="5" class="p-0">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div
                                                                    class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i
                                                                            class="fa-solid fa-puzzle-piece"></i> Use For
                                                                        Module :</div>
                                                                    <div class="expand-value">
                                                                        <select
                                                                            class="form-select form-select-sm wa-template-module-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="">-- Select --</option>
                                                                            @foreach ($whatsappModuleOptions as $key => $label)
                                                                                <option value="{{ $key }}"
                                                                                    {{ $template->use_for_module === $key ? 'selected' : '' }}>
                                                                                    {{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i
                                                                            class="fa-solid fa-signal"></i> Status :</div>
                                                                    <div class="expand-value"><span
                                                                            class="wa-templates-status-badge">{{ $template->status }}</span>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i
                                                                            class="fa-solid fa-toggle-on"></i> Active /
                                                                        Inactive :</div>
                                                                    <div class="expand-value">
                                                                        <select
                                                                            class="form-select form-select-sm wa-template-status-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="1"
                                                                                {{ $template->is_active ? 'selected' : '' }}>
                                                                                Active</option>
                                                                            <option value="0"
                                                                                {{ !$template->is_active ? 'selected' : '' }}>
                                                                                Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No WhatsApp
                                                        message
                                                        templates found in database.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3"
                                    id="wa_templates_pagination"></div>
                            
                        
                    </div>
                </div>
            </div>
                        </div>
                             <div class="tab-pane fade" id="smtp" role="tabpanel">
                                <div class="settings-panel mb-4">
                                    <div class="settings-panel-head d-flex justify-content-between align-items-center">
                                        <span>Email SMTP Integration</span>
                                        <div class="form-check form-switch form-check-reverse mb-0">
                                            <input class="form-check-input integration-status-toggle" type="checkbox" role="switch" id="emailSmtpToggle" data-integration="email_smtp" {{ $integrationSettings->email_smtp ? 'checked' : '' }} style="cursor: pointer;">
                                            <label class="form-check-label small fw-semibold text-muted me-2" for="emailSmtpToggle" id="emailSmtpToggleLabel" style="cursor: pointer;">
                                                {{ $integrationSettings->email_smtp ? 'Enable' : 'Disable' }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data"
                                    id="smtpSettingsForm">
                    @csrf
                    @method('PUT')
                    
                            <div class="row g-3 settings-smtp-grid">
                                <div class="col-md-6"><label class="form-label">SMTP Host</label><input type="text"
                                        name="mail_host" class="form-control"
                                        value="{{ $settings['mail_host']->value ?? '' }}" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Port</label><input type="number"
                                        name="mail_port" class="form-control"
                                        value="{{ $settings['mail_port']->value ?? '587' }}" placeholder="587"></div>
                                <div class="col-md-6"><label class="form-label">SMTP Username</label><input type="email"
                                        name="mail_username" class="form-control"
                                        value="{{ $settings['mail_username']->value ?? '' }}" placeholder="you@gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Password</label><input
                                        type="password" name="mail_password" class="form-control"
                                        value="{{ $settings['mail_password']->value ?? '' }}"
                                        placeholder="Enter password">
                                </div>
                                <div class="col-md-6"><label class="form-label">Encryption</label><select
                                        name="mail_encryption" class="form-select">
                                        <option value="tls"
                                            {{ ($settings['mail_encryption']->value ?? 'tls') == 'tls' ? 'selected' : '' }}>
                                            TLS</option>
                                        <option value="ssl"
                                            {{ ($settings['mail_encryption']->value ?? '') == 'ssl' ? 'selected' : '' }}>
                                            SSL</option>
                                        <option value=""
                                            {{ ($settings['mail_encryption']->value ?? '') == '' ? 'selected' : '' }}>None
                                        </option>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label">From Name</label><input type="text"
                                        name="mail_from_name" class="form-control"
                                        value="{{ $settings['mail_from_name']->value ?? config('app.name', 'CRM') }}"
                                        placeholder="Company Name"></div>
                            
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                                <span id="smtpSettingsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-primary settings-submit-btn">Save SMTP
                                    Settings</button>
                            
                        </div>
                    </div>
                </form>
            </div>
                            <div class="tab-pane fade" id="google-connection" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" id="googleConnectionForm" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="settings-panel">
                        <div class="settings-panel-head d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <span>Google Connection</span>
                            <div class="form-check form-switch form-check-reverse mb-0">
                                <input class="form-check-input integration-status-toggle" type="checkbox" role="switch" id="googleConnectionToggle" data-integration="google_connection" {{ $integrationSettings->google_connection ? 'checked' : '' }} style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-muted me-2" for="googleConnectionToggle" id="googleConnectionToggleLabel" style="cursor: pointer;">
                                    {{ $integrationSettings->google_connection ? 'Enable' : 'Disable' }}
                                </label>
                            </div>
                        </div>
                        <div class="settings-panel-body">
                        <div id="googleIntegrationFields" @if(!$integrationSettings->google_connection) class="settings-integration-disabled" inert @endif>
                            <!-- Connect to Google Section -->
                            <div class="google-connection-card">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="google-connection-icon">
                                            <i class="bi bi-google" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <h6 class="google-connection-title">Google Calendar</h6>
                                            <p class="google-connection-description">Connect your account to sync meetings with Google Calendar.</p>
                                            <small class="google-connection-status">
                                                @if (!empty($settings['google_client_id']->value ?? ''))
                                                    <span class="google-status-connected"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Connected</span>
                                                @else
                                                    <span><i class="bi bi-circle me-1" aria-hidden="true"></i>Not connected</span>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        @if (!empty($settings['google_client_id']->value ?? ''))
                                            <button type="button" class="btn btn-danger" id="disconnectFromGoogleBtn">
                                                <i class="bi bi-box-arrow-right me-1"></i>Disconnect
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success" id="connectToGoogleBtn">
                                                <i class="bi bi-link-45deg me-1"></i>Connect to Google
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Google OAuth Credentials Section -->
                            <h6 class="fw-semibold mb-3 d-none">Google OAuth Credentials</h6>
                            <div class="row g-3 d-none">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Google Client ID</label>
                                    <input type="text" name="google_client_id" id="google_client_id"
                                        class="form-control"
                                        value="{{ old('google_client_id', $settings['google_client_id']->value ?? '') }}"
                                        placeholder="Enter Google Client ID">
                                    <div class="invalid-feedback" id="google_client_id-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Google Client Secret</label>
                                    <input type="password" name="google_client_secret" id="google_client_secret"
                                        class="form-control"
                                        value="{{ old('google_client_secret', $settings['google_client_secret']->value ?? '') }}"
                                        placeholder="Enter Google Client Secret">
                                    <div class="invalid-feedback" id="google_client_secret-error"></div>
                                </div>
                            

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                                <span id="googleConnectionStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-success settings-submit-btn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                        aria-hidden="true"></span>
                                    <span class="btn-text">Save Google Credentials</span>
                                </button>
                            
                        </div>
                    </div>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Financial Information Main Tab -->
            <div class="tab-pane fade" id="financial-information" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head">Financial Information</div>
                    <div class="settings-panel-body">
                        <ul class="nav settings-subtabs settings-integration-subtabs mb-4" id="financialInnerTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tax" type="button" role="tab">Tax</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#subsidy" type="button" role="tab">Subsidy</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#bank-details" type="button" role="tab">Bank Details</button></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tax" role="tabpanel">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header border-bottom-0 py-3 px-4">
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                            <div>
                                <h4 class="fw-bold mb-0">Tax Settings</h4>
                                <p class="text-muted small mb-0">Configure tax types and rates for your products and
                                    services.</p>
                            </div>
                            <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal"
                                data-bs-target="#addTaxModal">
                                <i class="bi bi-plus-lg me-1"></i>Add Tax
                            </button>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <h6 class="fw-bold mb-0">Manage Tax Types and Rates</h6>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="taxTable">
                                <thead>
                                    <tr>
                                        <th class="ps-4 text-center" style="width: 80px;">#</th>
                                        <th class="text-center">Tax Name</th>
                                        <th class="text-center">Rate (%)</th>
                                        <th class="text-center pe-4" style="width: 170px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($taxes as $index => $tax)
                                        <tr data-tax-id="{{ $tax->id }}">
                                            <td class="ps-4 text-center">{{ $index + 1 }}</td>
                                            <td class="text-center">{{ $tax->name }}</td>
                                            <td class="text-center">{{ $tax->rate }}%</td>
                                            <td class="text-center pe-4">
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                    onclick="editTax({{ $tax->id }}, '{{ $tax->name }}', {{ $tax->rate }})"
                                                    title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteTax({{ $tax->id }})" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="noTaxRow">
                                            <td colspan="4" class="text-center text-muted py-4">No tax configurations
                                                found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer border-top-0 py-4 px-4">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                These tax configurations will be available when creating products, estimates, and invoices.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
                            <div class="tab-pane fade" id="subsidy" role="tabpanel">
                <form id="subsidyForm" novalidate>
                            <div class="subsidy-section">
                                <div class="subsidy-section-heading">
                                    <span class="subsidy-section-icon"><i class="bi bi-house" aria-hidden="true"></i></span>
                                    <div><h6>Residential Subsidy</h6><p>Set the subsidy amount for each system capacity.</p></div>
                                </div>
                                <div class="row g-3">
                                    @foreach ($subsidies as $subsidy)
                                        @if (str_starts_with($subsidy->category, 'residential'))
                                            <div class="col-md-4">
                                                <div class="subsidy-field">
                                                <label class="form-label fw-semibold" for="subsidy_{{ $subsidy->id }}">
                                                    @if ($subsidy->category == 'residential_0_2')
                                                        0 - 2 kW
                                                    @elseif($subsidy->category == 'residential_2_3')
                                                        2 - 3 kW
                                                    @elseif($subsidy->category == 'residential_above_3')
                                                        Above 3 kW
                                                    @endif
                                                </label>
                                                <div class="subsidy-amount-wrap"><span aria-hidden="true">₹</span>
                                                <input type="number" class="form-control subsidy-input" aria-label="Residential subsidy amount in rupees"
                                                    id="subsidy_{{ $subsidy->id }}"
                                                    data-subsidy-id="{{ $subsidy->id }}" value="{{ $subsidy->amount }}"
                                                    step="0.01" min="0" placeholder="Enter amount"></div>
                                                <div class="invalid-feedback" id="subsidy_{{ $subsidy->id }}-error">
                                                </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="subsidy-section">
                                <div class="subsidy-section-heading">
                                    <span class="subsidy-section-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                                    <div><h6>Common Meter</h6><p>Configure the subsidy amount for a common meter.</p></div>
                                </div>
                                <div class="row g-3">
                                    @foreach ($subsidies as $subsidy)
                                        @if ($subsidy->category == 'common_meter')
                                            <div class="col-md-6 col-lg-4">
                                                <div class="subsidy-field">
                                                <label class="form-label" for="subsidy_{{ $subsidy->id }}">Subsidy amount</label>
                                                <div class="subsidy-amount-wrap"><span aria-hidden="true">₹</span>
                                                <input type="number" class="form-control subsidy-input"
                                                    id="subsidy_{{ $subsidy->id }}"
                                                    data-subsidy-id="{{ $subsidy->id }}" value="{{ $subsidy->amount }}"
                                                    step="0.01" min="0" placeholder="Enter amount"></div>
                                                <div class="invalid-feedback" id="subsidy_{{ $subsidy->id }}-error">
                                                </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            

                            </div>
                            <div class="subsidy-actions d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <span id="subsidyStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-primary settings-submit-btn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                        aria-hidden="true"></span>
                                    <span class="btn-text">Save Subsidy</span>
                                </button>
                            
                        </div>
                </form>
            </div>
                            <div class="tab-pane fade" id="bank-details" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" id="bankDetailsForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    
                            <div class="row g-3">
                                @php
                                    // Keep existing documents unchanged until an administrator explicitly turns a section off.
                                    $showBankDetails = old('show_bank_details', $settings['show_bank_details']->value ?? '1') === '1';
                                    $showCompanyQrCode = old('show_company_qr_code', $settings['show_company_qr_code']->value ?? '1') === '1';
                                @endphp
                                <div class="col-12">
                                    <div class="bank-display-options">
                                        <div class="bank-display-option-wrap">
                                            <input type="hidden" name="show_bank_details" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_bank_details" value="1" id="show_bank_details" @checked($showBankDetails)>
                                            <label class="bank-display-option" for="show_bank_details">
                                                <span class="bank-display-option-icon"><i class="bi bi-bank"></i></span>
                                                <span class="bank-display-option-copy"><strong>Bank details</strong><small>Show on estimates and PDFs</small></span>
                                                <span class="bank-display-option-switch" aria-hidden="true"></span>
                                            </label>
                                        </div>
                                        <div class="bank-display-option-wrap">
                                            <input type="hidden" name="show_company_qr_code" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_company_qr_code" value="1" id="show_company_qr_code" @checked($showCompanyQrCode)>
                                            <label class="bank-display-option" for="show_company_qr_code">
                                                <span class="bank-display-option-icon"><i class="bi bi-qr-code"></i></span>
                                                <span class="bank-display-option-copy"><strong>QR code</strong><small>Show on estimates and PDFs</small></span>
                                                <span class="bank-display-option-switch" aria-hidden="true"></span>
                                            </label>
                                        </div>
                                        <div class="bank-display-option-wrap">
                                            <input type="hidden" name="show_company_signature" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_company_signature" value="1" id="show_company_signature" @checked(old('show_company_signature', $settings['show_company_signature']->value ?? '1') === '1')>
                                            <label class="bank-display-option" for="show_company_signature">
                                                <span class="bank-display-option-icon"><i class="bi bi-pen"></i></span>
                                                <span class="bank-display-option-copy"><strong>Signature</strong><small>Show on deal PDFs</small></span>
                                                <span class="bank-display-option-switch" aria-hidden="true"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Bank Name</label>
                                    <input type="text" name="bank_name" id="bank_name" class="form-control"
                                        value="{{ old('bank_name', $settings['bank_name']->value ?? '') }}"
                                        placeholder="Enter bank name">
                                    <div class="invalid-feedback" id="bank_name-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Account Number</label>
                                    <input type="text" name="account_number" id="account_number" class="form-control"
                                        value="{{ old('account_number', $settings['account_number']->value ?? '') }}"
                                        placeholder="Enter account number">
                                    <div class="invalid-feedback" id="account_number-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Account Name</label>
                                    <input type="text" name="account_name" id="account_name" class="form-control"
                                        value="{{ old('account_name', $settings['account_name']->value ?? '') }}"
                                        placeholder="Enter account holder name">
                                    <div class="invalid-feedback" id="account_name-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">IFSC Code</label>
                                    <input type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                        value="{{ old('ifsc_code', $settings['ifsc_code']->value ?? '') }}"
                                        placeholder="Enter IFSC code">
                                    <div class="invalid-feedback" id="ifsc_code-error"></div>
                                </div>
                                @php
                                    $qrPath = $settings['company_qr_code_path']->value ?? null;
                                    $companyQrCodeUrl = $qrPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($qrPath)
                                        ? route('profile.company_qr_code.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($qrPath)
                                        : null;
                                    $signaturePath = $settings['company_signature_path']->value ?? null;
                                    $companySignatureUrl = $signaturePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($signaturePath)
                                        ? route('profile.company_signature.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($signaturePath)
                                        : null;
                                @endphp
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Branch Name</label>
                                    <input type="text" name="branch_name" id="branch_name" class="form-control"
                                        value="{{ old('branch_name', $settings['branch_name']->value ?? '') }}"
                                        placeholder="Enter branch name">
                                    <div class="invalid-feedback" id="branch_name-error"></div>
                                    <small class="text-muted d-block mt-1" style="visibility: hidden;">Spacer</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">QR Code (Upload)</label>
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <input type="file" name="company_qr_code_path" id="bank-qr-input" accept="image/*" class="form-control">
                                            <small class="text-muted d-block mt-1">Any image file - max 50 MB.</small>
                                        </div>
                                        <div class="profile-qr-preview-container flex-shrink-0">
                                            <img src="{{ $companyQrCodeUrl ?: '' }}" alt="QR Code" class="profile-qr-mini {{ $companyQrCodeUrl ? '' : 'd-none' }}" id="bank-qr-preview">
                                            @if(!$companyQrCodeUrl)
                                                <div class="profile-qr-mini-placeholder d-flex align-items-center justify-content-center" id="bank-qr-placeholder">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Default Signature (Upload)</label>
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <input type="file" name="company_signature_path" id="company-signature-input" accept="image/*" class="form-control">
                                            <small class="text-muted d-block mt-1">Used in deal PDFs unless a deal-specific signature is uploaded. Any image file - max 50 MB.</small>
                                        </div>
                                        <div class="profile-signature-preview-container flex-shrink-0">
                                            <img src="{{ $companySignatureUrl ?: '' }}" alt="Signature preview" class="profile-signature-mini {{ $companySignatureUrl ? '' : 'd-none' }}" id="company-signature-preview">
                                            <div class="profile-signature-mini-placeholder d-flex align-items-center justify-content-center {{ $companySignatureUrl ? 'd-none' : '' }}" id="company-signature-placeholder">
                                                <i class="bi bi-pen"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                                <span id="bankDetailsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-primary settings-submit-btn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                        aria-hidden="true"></span>
                                    <span class="btn-text">Save Bank Details</span>
                                </button>
                            
                        </div>
                    </div>
                </form>
            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="estimate-invoice-setting" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head">Estimate/Invoice setting</div>
                    <div class="settings-panel-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_settings_tab" value="estimate-invoice-setting">
                            <div class="mb-4">
                                <h3 class="h6 fw-semibold">Document numbering</h3>
                                <p class="text-muted small">Set the starting number for new estimates and invoices. For example, 200 starts at 200, then 201, 202. Existing numbers, including deleted records, are skipped. Existing documents are not renumbered.</p>
                                <div class="row g-3">
                                    @foreach (['estimate' => 'Estimate', 'invoice' => 'Invoice'] as $kind => $label)
                                        <div class="col-md-6">
                                            <label for="{{ $kind }}_number_start" class="form-label fw-semibold">{{ $label }} starting number</label>
                                            <input type="number" name="{{ $kind }}_number_start" id="{{ $kind }}_number_start" min="1" max="999999999" step="1" class="form-control"
                                                value="{{ old($kind . '_number_start', $settings[$kind . '_number_start']->value ?? '') }}" placeholder="Automatic">
                                            <div class="form-text">Next available: {{ $kind === 'estimate' ? '#EST-' : '#' }}{{ \App\Support\DocumentNumber::next($kind) }}. Leave blank to continue after the highest number.</div>
                                            @error($kind . '_number_start')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="estimate_price_mode" class="form-label fw-semibold">Pricing Method</label>
                                <div class="text-muted small mb-2">Choose how the estimate total should be calculated.</div>
                                <select name="estimate_price_mode" id="estimate_price_mode" class="form-select" style="max-width: 420px;">
                                    <option value="base" @selected(($settings['estimate_price_mode']->value ?? 'bom') === 'base')>Show Base Price only</option>
                                    <option value="bom" @selected(($settings['estimate_price_mode']->value ?? 'bom') === 'bom')>Show BOM Price only</option>
                                </select>
                            </div>
                            <div class="pricing-guide mb-4">
                                <div class="pricing-guide-title"><i class="bi bi-info-circle"></i><span>How pricing works</span></div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="pricing-mode-card pricing-mode-card-base h-100">
                                            <div class="pricing-mode-heading"><span class="pricing-mode-icon"><i class="bi bi-cash-stack"></i></span><div><span class="pricing-mode-badge">Base Price</span><div class="pricing-mode-name">Show Base Price only</div></div></div>
                                            <ul class="pricing-mode-list">
                                                <li class="mb-1">The estimate total uses the Base Price.</li>
                                                <li class="mb-1">You can select one Global Tax Rate.</li>
                                                <li>BOM prices and BOM taxes are hidden.</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="pricing-mode-card pricing-mode-card-bom h-100">
                                            <div class="pricing-mode-heading"><span class="pricing-mode-icon"><i class="bi bi-box-seam"></i></span><div><span class="pricing-mode-badge">BOM Price</span><div class="pricing-mode-name">Show BOM Price only</div></div></div>
                                            <ul class="pricing-mode-list">
                                                <li class="mb-1">The estimate total uses BOM item prices.</li>
                                                <li class="mb-1">You can select a tax for each BOM item.</li>
                                                <li>Base Price and Global Tax Rate are hidden.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-dark-blue settings-submit-btn">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<div class="tab-pane fade" id="table-truncate" role="tabpanel">
            <div class="settings-panel">
                <div
                    class="settings-panel-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                    <span>Table Clear Utility</span>
                    <button type="button" class="btn btn-danger rounded-pill shadow-sm fw-semibold"
                        style="padding: 8px 20px;" id="truncateAllBtn">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Clear All Allowed
                    </button>
                </div>
                <div class="settings-panel-body p-0">
                    <div class="p-4">
                        <div class="alert alert-warning m-0" role="alert"
                            style="background-color: #fdf6e3; border: 1px solid #ffe8a1; color: #856404; border-radius: 6px; padding: 12px 16px; font-size: 0.9rem;">
                            <i class="bi bi-exclamation-triangle-fill" style="color: #f5b041; margin-right: 6px;"></i>
                            <strong>Warning:</strong> Clearing a table permanently deletes all its records (except Admin
                            users in the users table). This action cannot be undone. System configuration tables are marked
                            as 'Not Allowed'.
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="truncateTable" class="table table-bordered table-hover align-middle">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th class="text-uppercase" style="color: #6c757d; font-size: 0.85rem;">Table Name</th>
                                    <th class="text-uppercase" style="color: #6c757d; font-size: 0.85rem;">Total Records
                                    </th>
                                    <th class="text-uppercase text-center"
                                        style="color: #6c757d; font-size: 0.85rem; width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($truncateTables)
                                    @forelse($truncateTables as $tableInfo)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $tableInfo['name'] == 'users' ? 'users (Excludes Admins)' : $tableInfo['name'] }}
                                            </td>
                                            <td>{{ $tableInfo['count'] }}</td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn crm-action-btn btn-sm text-danger truncate-btn shadow-sm"
                                                    style="border-radius: 8px; background-color: #fff2f2; border: 1px solid #ffcccc;"
                                                    data-table="{{ $tableInfo['name'] }}" title="Clear Table">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No allowed tables found.</td>
                                        </tr>
                                    @endforelse
                                @endisset
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top-0 p-4 pt-0 bg-white" id="truncatePagination"></div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Tax Modal -->
    <div class="modal fade" id="addTaxModal" tabindex="-1" aria-labelledby="addTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaxModalLabel">Add Tax</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTaxForm" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="taxType" class="form-label fw-semibold">Tax Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="taxType" name="name">
                                <option value="">Select Tax</option>
                                <option value="GST (CGST + SGST)">GST (CGST + SGST)</option>
                                <option value="GST (IGST)">GST (IGST)</option>
                            </select>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="taxRate" class="form-label fw-semibold">Tax Rate (%) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="taxRate" name="rate"
                                placeholder="Enter rate" step="0.01" min="0" max="100">
                            <div class="invalid-feedback" id="rate-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Tax Modal -->
    <div class="modal fade" id="editTaxModal" tabindex="-1" aria-labelledby="editTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaxModalLabel">Edit Tax</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTaxForm" novalidate>
                    <input type="hidden" id="editTaxId" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editTaxType" class="form-label fw-semibold">Tax Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="editTaxType" name="name">
                                <option value="">Select Tax</option>
                                <option value="GST (CGST + SGST)">GST (CGST + SGST)</option>
                                <option value="GST (IGST)">GST (IGST)</option>
                            </select>
                            <div class="invalid-feedback" id="edit-name-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editTaxRate" class="form-label fw-semibold">Tax Rate (%) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editTaxRate" name="rate"
                                placeholder="Enter rate" step="0.01" min="0" max="100">
                            <div class="invalid-feedback" id="edit-rate-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.settingsPageConfig = {
                apiSettingsIndex: @json(route('api.settings.index')),
                apiSettingsUpdate: @json(route('api.settings.update')),
                taxStoreUrl: @json(route('settings.taxes.store')),
                taxUpdateUrl: @json(route('settings.taxes.update', ':id')),
                taxDestroyUrl: @json(route('settings.taxes.destroy', ':id')),
                subsidyUpdateUrl: @json(route('settings.subsidies.update', ':id')),
                toggleIntegrationUrl: @json(route('settings.toggle_integration')),
            };

            // Tax Management Functions
            function editTax(id, name, rate) {
                document.getElementById('editTaxId').value = id;
                document.getElementById('editTaxType').value = name;
                document.getElementById('editTaxRate').value = rate;

                const modal = new bootstrap.Modal(document.getElementById('editTaxModal'));
                modal.show();
            }

            function deleteTax(id) {
                showDeleteConfirm('You won\'t be able to revert this!', {
                    title: 'Are you sure?',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const url = window.settingsPageConfig.taxDestroyUrl.replace(':id', id);

                        fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content'),
                                    'Accept': 'application/json',
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove row from table
                                    const row = document.querySelector(`tr[data-tax-id="${id}"]`);
                                    if (row) {
                                        row.remove();

                                        // Show "no tax" message if table is empty
                                        const tbody = document.querySelector('#taxTable tbody');
                                        if (tbody.children.length === 0) {
                                            tbody.innerHTML =
                                                '<tr id="noTaxRow"><td colspan="4" class="text-center text-muted py-4">No tax configurations found.</td></tr>';
                                        } else {
                                            // Update row numbers
                                            updateRowNumbers();
                                        }
                                    }

                                    // Show success message
                                    showAlert('success', data.message || 'Tax deleted successfully.');
                                } else {
                                    showAlert('error', data.message || 'Failed to delete tax.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showAlert('error', 'An error occurred while deleting the tax.');
                            });
                    }
                });
            }

            function updateRowNumbers() {
                const rows = document.querySelectorAll('#taxTable tbody tr[data-tax-id]');
                rows.forEach((row, index) => {
                    row.querySelector('td:first-child').textContent = index + 1;
                });
            }

            function clearFormErrors(form) {
                const fields = form === document.getElementById('addTaxForm') ?
                    ['name', 'rate'] :
                    ['edit-name', 'edit-rate'];

                fields.forEach(field => {
                    const input = form.querySelector(`[name="${field.replace('edit-', '')}"]`);
                    const errorDiv = document.getElementById(`${field}-error`);

                    if (input) input.classList.remove('is-invalid');
                    if (errorDiv) errorDiv.textContent = '';
                });
            }

            function showFormErrors(form, errors) {
                const isEditForm = form === document.getElementById('editTaxForm');

                Object.keys(errors).forEach(field => {
                    const errorId = isEditForm ? `edit-${field}` : field;
                    const input = form.querySelector(`[name="${field}"]`);
                    const errorDiv = document.getElementById(`${errorId}-error`);

                    if (input) {
                        input.classList.add('is-invalid');
                    }

                    if (errorDiv) {
                        errorDiv.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                    }
                });
            }

            // Clear errors on input change for Add Tax form
            ['taxType', 'taxRate'].forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`${this.name}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });
                    input.addEventListener('change', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`${this.name}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });
                }
            });

            // Clear errors on input change for Edit Tax form
            ['editTaxType', 'editTaxRate'].forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`edit-${this.name}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });
                    input.addEventListener('change', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`edit-${this.name}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });
                }
            });

            // Add Tax Form Handler
            document.getElementById('addTaxForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const submitBtn = form.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const btnText = submitBtn.querySelector('.btn-text');

                // Clear previous errors
                clearFormErrors(form);

                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Saving...';

                const formData = new FormData(form);

                fetch(window.settingsPageConfig.taxStoreUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addTaxModal'));
                            modal.hide();

                            // Reset form
                            form.reset();

                            // Add new row to table
                            const tbody = document.querySelector('#taxTable tbody');
                            const noTaxRow = document.getElementById('noTaxRow');

                            if (noTaxRow) {
                                noTaxRow.remove();
                            }

                            const newRow = document.createElement('tr');
                            newRow.setAttribute('data-tax-id', data.tax.id);
                            newRow.innerHTML = `
                            <td class="ps-4 text-center">${tbody.children.length + 1}</td>
                            <td class="text-center">${data.tax.name}</td>
                            <td class="text-center">${data.tax.rate}%</td>
                            <td class="text-center pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editTax(${data.tax.id}, '${data.tax.name}', ${data.tax.rate})"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteTax(${data.tax.id})"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        `;

                            tbody.appendChild(newRow);
                            updateRowNumbers();

                            // Show success message
                            showAlert('success', data.message || 'Tax added successfully.');
                        } else {
                            if (data.errors) {
                                showFormErrors(form, data.errors);
                            } else {
                                showAlert('error', data.message || 'Failed to add tax.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'An error occurred while adding the tax.');
                    })
                    .finally(() => {
                        // Hide loading state
                        submitBtn.disabled = false;
                        spinner.classList.add('d-none');
                        btnText.textContent = 'Save';
                    });
            });

            // Edit Tax Form Handler
            document.getElementById('editTaxForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const submitBtn = form.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const btnText = submitBtn.querySelector('.btn-text');
                const taxId = document.getElementById('editTaxId').value;

                // Clear previous errors
                clearFormErrors(form);

                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Updating...';

                const formData = new FormData(form);
                const url = window.settingsPageConfig.taxUpdateUrl.replace(':id', taxId);

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json',
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editTaxModal'));
                            modal.hide();

                            // Update row in table
                            const row = document.querySelector(`tr[data-tax-id="${taxId}"]`);
                            if (row) {
                                const cells = row.querySelectorAll('td');
                                cells[1].textContent = data.tax.name;
                                cells[2].textContent = data.tax.rate + '%';

                                // Update onclick handlers
                                const editBtn = row.querySelector('.btn-outline-primary');
                                const deleteBtn = row.querySelector('.btn-outline-danger');

                                editBtn.setAttribute('onclick',
                                    `editTax(${data.tax.id}, '${data.tax.name}', ${data.tax.rate})`);
                                deleteBtn.setAttribute('onclick', `deleteTax(${data.tax.id})`);
                            }

                            // Show success message
                            showAlert('success', data.message || 'Tax updated successfully.');
                        } else {
                            if (data.errors) {
                                showFormErrors(form, data.errors);
                            } else {
                                showAlert('error', data.message || 'Failed to update tax.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'An error occurred while updating the tax.');
                    })
                    .finally(() => {
                        // Hide loading state
                        submitBtn.disabled = false;
                        spinner.classList.add('d-none');
                        btnText.textContent = 'Update';
                    });
            });

            // Bank Details Form Handler
            const bankDetailsForm = document.getElementById('bankDetailsForm');
            if (bankDetailsForm) {
                bankDetailsForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const form = this;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const statusSpan = document.getElementById('bankDetailsStatus');

                    // Clear previous errors
                    ['bank_name', 'account_name', 'account_number', 'ifsc_code', 'branch_name'].forEach(field => {
                        const input = document.getElementById(field);
                        const errorDiv = document.getElementById(`${field}-error`);
                        if (input) input.classList.remove('is-invalid');
                        if (errorDiv) errorDiv.textContent = '';
                    });

                    // Show loading state
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    btnText.textContent = 'Saving...';
                    statusSpan.textContent = '';

                    const formData = new FormData(form);

                    fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        throw new Error('Server returned an error');
                                    }
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data && data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    const input = document.getElementById(field);
                                    const errorDiv = document.getElementById(`${field}-error`);

                                    if (input) input.classList.add('is-invalid');
                                    if (errorDiv) {
                                        errorDiv.textContent = Array.isArray(data.errors[field]) ?
                                            data.errors[field][0] :
                                            data.errors[field];
                                    }
                                });
                                showAlert('error', data.message || 'Please fix the errors and try again.');
                            } else {
                                showAlert('success', 'Bank details saved successfully.');
                                statusSpan.textContent = 'Saved successfully!';
                                statusSpan.className = 'settings-form-status text-success';

                                setTimeout(() => {
                                    statusSpan.textContent = '';
                                }, 3000);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('error', 'An error occurred while saving bank details.');
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            spinner.classList.add('d-none');
                            btnText.textContent = 'Save Bank Details';
                        });
                });

                // Clear errors on input change for bank details
                ['bank_name', 'account_name', 'account_number', 'ifsc_code', 'branch_name'].forEach(fieldId => {
                    const input = document.getElementById(fieldId);
                    if (input) {
                        input.addEventListener('input', function() {
                            this.classList.remove('is-invalid');
                            const errorDiv = document.getElementById(`${fieldId}-error`);
                            if (errorDiv) errorDiv.textContent = '';
                        });
                    }
                });
            }

            // Subsidy Form Handler
            const subsidyForm = document.getElementById('subsidyForm');
            if (subsidyForm) {
                subsidyForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const form = this;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const statusSpan = document.getElementById('subsidyStatus');

                    // Clear previous errors
                    document.querySelectorAll('.subsidy-input').forEach(input => {
                        input.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`${input.id}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });

                    // Show loading state
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    btnText.textContent = 'Saving...';
                    statusSpan.textContent = '';

                    // Get all subsidy inputs
                    const subsidyInputs = document.querySelectorAll('.subsidy-input');
                    const updatePromises = [];

                    subsidyInputs.forEach(input => {
                        const subsidyId = input.dataset.subsidyId;
                        const amount = input.value;

                        const formData = new FormData();
                        formData.append('amount', amount);
                        formData.append('_method', 'PUT');

                        const url = window.settingsPageConfig.subsidyUpdateUrl.replace(':id', subsidyId);

                        const promise = fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content'),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success && data.errors) {
                                    // Handle validation errors for this specific input
                                    Object.keys(data.errors).forEach(field => {
                                        input.classList.add('is-invalid');
                                        const errorDiv = document.getElementById(
                                            `${input.id}-error`);
                                        if (errorDiv) {
                                            errorDiv.textContent = Array.isArray(data.errors[
                                                field]) ?
                                                data.errors[field][0] :
                                                data.errors[field];
                                        }
                                    });
                                    return {
                                        success: false
                                    };
                                }
                                return data;
                            });

                        updatePromises.push(promise);
                    });

                    // Wait for all updates to complete
                    Promise.all(updatePromises)
                        .then(results => {
                            const allSuccess = results.every(result => result.success !== false);

                            if (allSuccess) {
                                showAlert('success', 'Subsidy details saved successfully.');
                                statusSpan.textContent = 'Saved successfully!';
                                statusSpan.className = 'settings-form-status text-success';

                                setTimeout(() => {
                                    statusSpan.textContent = '';
                                }, 3000);
                            } else {
                                showAlert('error',
                                    'Some subsidy values could not be saved. Please check the errors.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('error', 'An error occurred while saving subsidy details.');
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            spinner.classList.add('d-none');
                            btnText.textContent = 'Save Subsidy';
                        });
                });

                // Clear errors on input change for subsidy
                document.querySelectorAll('.subsidy-input').forEach(input => {
                    input.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = document.getElementById(`${this.id}-error`);
                        if (errorDiv) errorDiv.textContent = '';
                    });
                });
            }

            // Google Connection Form Handler
            const googleConnectionForm = document.getElementById('googleConnectionForm');
            if (googleConnectionForm) {
                googleConnectionForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const form = this;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const statusSpan = document.getElementById('googleConnectionStatus');

                    // Clear previous errors
                    ['google_client_id', 'google_client_secret'].forEach(field => {
                        const input = document.getElementById(field);
                        const errorDiv = document.getElementById(`${field}-error`);
                        if (input) input.classList.remove('is-invalid');
                        if (errorDiv) errorDiv.textContent = '';
                    });

                    // Show loading state
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    btnText.textContent = 'Saving...';
                    statusSpan.textContent = '';

                    const formData = new FormData(form);

                    fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        throw new Error('Server returned an error');
                                    }
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data && data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    const input = document.getElementById(field);
                                    const errorDiv = document.getElementById(`${field}-error`);

                                    if (input) input.classList.add('is-invalid');
                                    if (errorDiv) {
                                        errorDiv.textContent = Array.isArray(data.errors[field]) ?
                                            data.errors[field][0] :
                                            data.errors[field];
                                    }
                                });
                                showAlert('error', data.message || 'Please fix the errors and try again.');
                            } else {
                                showAlert('success', 'Google credentials saved successfully.');
                                statusSpan.textContent = 'Saved successfully!';
                                statusSpan.className = 'settings-form-status text-success';

                                setTimeout(() => {
                                    statusSpan.textContent = '';
                                }, 3000);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('error', 'An error occurred while saving Google credentials.');
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            spinner.classList.add('d-none');
                            btnText.textContent = 'Save Google Credentials';
                        });
                });

                // Clear errors on input change for Google Connection
                ['google_client_id', 'google_client_secret'].forEach(fieldId => {
                    const input = document.getElementById(fieldId);
                    if (input) {
                        input.addEventListener('input', function() {
                            this.classList.remove('is-invalid');
                            const errorDiv = document.getElementById(`${fieldId}-error`);
                            if (errorDiv) errorDiv.textContent = '';
                        });
                    }
                });

                // Connect to Google button handler
                const connectBtn = document.getElementById('connectToGoogleBtn');
                if (connectBtn) {
                    connectBtn.addEventListener('click', function() {
                        // Check if credentials are saved
                        const clientId = document.getElementById('google_client_id').value;
                        const clientSecret = document.getElementById('google_client_secret').value;

                        if (!clientId || !clientSecret) {
                            showAlert('warning', 'Please save Google credentials first before connecting.');
                            return;
                        }

                        // Redirect to Google OAuth
                        window.location.href = '/auth/google';
                    });
                }

                // Disconnect from Google button handler
                const disconnectBtn = document.getElementById('disconnectFromGoogleBtn');
                if (disconnectBtn) {
                    disconnectBtn.addEventListener('click', function() {
                        if (confirm('Are you sure you want to disconnect from Google? This will remove your credentials.')) {
                            document.getElementById('google_client_id').value = '';
                            document.getElementById('google_client_secret').value = '';
                            
                            // submit the form programmatically to clear credentials in backend
                            const submitEvent = new Event('submit', {
                                'bubbles': true,
                                'cancelable': true
                            });
                            document.getElementById('googleConnectionForm').dispatchEvent(submitEvent);
                            
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    });
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Client-side pagination logic
                const tableBody = document.querySelector('#truncateTable tbody');
                const paginationContainer = document.getElementById('truncatePagination');
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                let currentPage = 1;
                const rowsPerPage = 10;

                function renderTable(page) {
                    currentPage = page;
                    const start = (page - 1) * rowsPerPage;
                    const end = start + rowsPerPage;
                    const paginatedRows = rows.slice(start, end);

                    tableBody.innerHTML = '';
                    paginatedRows.forEach(row => tableBody.appendChild(row));

                    renderPagination(rows.length, page);
                }

                function renderPagination(totalRows, page) {
                    if (totalRows <= rowsPerPage) {
                        paginationContainer.innerHTML = '';
                        return;
                    }

                    const totalPages = Math.ceil(totalRows / rowsPerPage);
                    const startCount = (page - 1) * rowsPerPage + 1;
                    const endCount = Math.min(page * rowsPerPage, totalRows);

                    let html =
                        `<div class="crm-pagination-container d-flex justify-content-between align-items-center"><div class="text-muted small">Showing ${startCount} to ${endCount} of ${totalRows} results</div><ul class="pagination crm-pagination mb-0">`;

                    html += page > 1 ?
                        `<li class="page-item"><a class="page-link" href="#" data-page="${page - 1}">Previous</a></li>` :
                        `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;

                    for (let i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                            html += i === page ?
                                `<li class="page-item active"><span class="page-link">${i}</span></li>` :
                                `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                        } else if (i === page - 3 || i === page + 3) {
                            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                    }

                    html += page < totalPages ?
                        `<li class="page-item"><a class="page-link" href="#" data-page="${page + 1}">Next</a></li>` :
                        `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
                    html += `</ul></div>`;

                    paginationContainer.innerHTML = html;

                    paginationContainer.querySelectorAll('.page-link[data-page]').forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            renderTable(parseInt(e.target.getAttribute('data-page')));
                        });
                    });
                }

                if (rows.length > 0 && !rows[0].querySelector('td[colspan]')) {
                    renderTable(1);
                }

                // Individual Table Truncate
                // Use event delegation for dynamically paginated rows
                $(document).on('click', '.truncate-btn', function() {
                    const tableName = $(this).attr('data-table');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Are you absolutely sure you want to truncate the table "${tableName}"? This cannot be undone.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // AJAX call
                            fetch(`/settings/table-truncate/${tableName}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Deleted!', data.message, 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Error!', data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error!', 'Something went wrong.', 'error');
                                });
                        }
                    });
                });

                // Truncate All
                const truncateAllBtn = document.getElementById('truncateAllBtn');
                if (truncateAllBtn) {
                    truncateAllBtn.addEventListener('click', function() {
                        Swal.fire({
                            title: 'Are you completely sure?',
                            text: "You are about to permanently delete all data from all allowed tables. This cannot be undone!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, proceed to next step'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Second confirmation
                                Swal.fire({
                                    title: 'Final Warning!',
                                    text: "This is a highly sensitive action. All system records will be wiped out. Do you still want to truncate ALL allowed tables?",
                                    icon: 'error',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, delete EVERYTHING!'
                                }).then((secondResult) => {
                                    if (secondResult.isConfirmed) {
                                        fetch(`/settings/table-truncate-all`, {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector(
                                                            'meta[name="csrf-token"]')
                                                        .getAttribute('content'),
                                                    'Accept': 'application/json',
                                                }
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire('Deleted!', data.message,
                                                            'success')
                                                        .then(() => location.reload());
                                                } else {
                                                    Swal.fire('Error!', data.message,
                                                        'error');
                                                }
                                            })
                                            .catch(error => {
                                                Swal.fire('Error!', 'Something went wrong.',
                                                    'error');
                                            });
                                    }
                                });
                            }
                        });
                    });
                }
            });
            // Bank QR Code Preview
            const bankQrInput = document.getElementById('bank-qr-input');
            const bankQrPreview = document.getElementById('bank-qr-preview');
            const bankQrPlaceholder = document.getElementById('bank-qr-placeholder');

            if (bankQrInput) {
                bankQrInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (bankQrPreview) {
                                bankQrPreview.src = e.target.result;
                                bankQrPreview.classList.remove('d-none');
                            }
                            if (bankQrPlaceholder) {
                                bankQrPlaceholder.classList.add('d-none');
                            }
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            // Default Signature Preview
            const companySignatureInput = document.getElementById('company-signature-input');
            const companySignaturePreview = document.getElementById('company-signature-preview');
            const companySignaturePlaceholder = document.getElementById('company-signature-placeholder');

            if (companySignatureInput) {
                companySignatureInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            companySignaturePreview.src = e.target.result;
                            companySignaturePreview.classList.remove('d-none');
                            companySignaturePlaceholder.classList.add('d-none');
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        </script>
        <script
            src="{{ crm_asset('assets/js/setting.js') }}">
        </script>
        <script
            src="{{ crm_asset('js/settings-page.js') }}">
        </script>
    @endpush
@endsection
