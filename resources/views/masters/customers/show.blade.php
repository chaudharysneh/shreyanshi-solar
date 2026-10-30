@extends('layouts.app')

@section('page_title', 'Customer Details')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Customer Details</h1>
                    <p class="text-muted small mb-0">Complete information about this customer.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                    @php
                        $isCreator = (int) $customer->created_by === (int) auth()->id();
                        $isAdmin = auth()->user()?->isAdmin() ?? false;
                        $canUpdate = auth()->user()->can('update', $customer);
                    @endphp
                    @if($canUpdate)
                        <a href="{{ route('masters.customers.edit', $customer) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    @else
                        <button class="btn btn-dark-blue flex-grow-1 flex-md-grow-0" disabled title="Edit disabled - assigned through module">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                    @endif
                    <a href="{{ route('masters.customers.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            {{-- Profile Block --}}
            <div class="detail-view-block mb-2">
                <div class="row g-4 align-items-start">
                    <div class="col-md-4 col-lg-3 text-center text-md-start">
                        <div class="staff-profile-media mx-auto mx-md-0">
                            @if($customer->image)
                                <img src="{{ route('masters.customers.image', $customer) }}?v={{ $customer->updated_at->timestamp }}"
                                     alt="{{ $customer->name }}" class="staff-profile-img shadow-sm" loading="lazy" onerror="this.style.display='none'">
                            @else
                                <span class="staff-profile-placeholder bg-light">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                                <div class="detail-view-title mb-3">{{ $customer->name ?? '--' }}</div>
                                <div class="row g-0 detail-view-grid">
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-user-tie"></i>Created By:</span>
                                        <span class="detail-view-value">{{ $customer->creator->name ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-calendar-alt"></i>Created At:</span>
                                        <span class="detail-view-value">{{ $customer->created_at?->format('d M, Y h:i A') ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-envelope"></i>Email:</span>
                                        <span class="detail-view-value">
                                            @if($customer->email)
                                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none link-hover">{{ $customer->email }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-phone-alt"></i>Phone:</span>
                                        <span class="detail-view-value">
                                            @if($customer->phone)
                                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none link-hover">{{ $customer->phone }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fab fa-whatsapp"></i>WhatsApp:</span>
                                        <span class="detail-view-value">
                                            @if($customer->whatsapp)
                                                <a href="https://wa.me/{{ $customer->whatsapp }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->whatsapp }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-birthday-cake"></i>Date of Birth:</span>
                                        <span class="detail-view-value">{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M, Y') : '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-calendar-check"></i>Anniversary:</span>
                                        <span class="detail-view-value">{{ $customer->anniversary_date ? \Carbon\Carbon::parse($customer->anniversary_date)->format('d M, Y') : '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-globe"></i>Website:</span>
                                        <span class="detail-view-value">
                                            @if($customer->website)
                                                <a href="{{ $customer->website }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->website }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-file-invoice"></i>Tax Number:</span>
                                        <span class="detail-view-value">{{ $customer->tax_number ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-map-marker-alt"></i>Location:</span>
                                        <span class="detail-view-value">{{ $customer->city ? $customer->city->name . ', ' : '' }}{{ $customer->country ? $customer->country->name : '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-building"></i>Company:</span>
                                        <span class="detail-view-value">{{ $customer->company_name ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-tags"></i>Type:</span>
                                        <span class="detail-view-value">{{ $customer->type ?? '--' }}</span>
                                    </div>
                                    <div class="col-12 detail-view-row border-bottom-0">
                                        <span class="detail-view-label pe-2"><i class="fas fa-home"></i>Address:</span>
                                        <span class="detail-view-value">{{ $customer->address ?? '--' }}</span>
                                    </div>
                                </div>
                            </div>
                </div>
            </div>

            {{-- Custom Fields --}}
            @php
                $customFields = \App\Models\CustomField::where('module', 'Customer')->where('is_active', true)->get();
                $hasValues = false;
            @endphp
            @if($customFields->count() > 0)
                <div class="mb-4 pt-3 border-top">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.05em;">Additional Information</h6>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($customFields as $field)
                            @php $val = $customer->getCustomFieldValue($field->name); @endphp
                            @if($val)
                                @php $hasValues = true; @endphp
                                <div class="bg-light px-3 py-2 rounded-3 border">
                                    <strong class="text-muted small d-block" style="font-size: 0.65rem;">{{ $field->label }}</strong>
                                    <span class="text-dark fw-semibold small">{{ $val }}</span>
                                </div>
                            @endif
                        @endforeach
                        @if(!$hasValues)
                            <p class="text-muted small fst-italic mb-0 bg-light p-2 rounded w-100">No additional form data found.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="row g-3 mt-2" aria-label="Customer overview">
                @foreach(['Documents' => $overview['documents']->count(), 'Deals' => $customer->deals->count(), 'Estimates' => $overview['sections']['estimates']['records']->count(), 'Invoices' => $overview['sections']['invoices']['records']->count()] as $label => $count)
                    @php
                        $kpiStyle = match ($label) {
                            'Documents' => ['blue', 'bi-file-earmark-text'],
                            'Deals' => ['purple', 'bi-briefcase'],
                            'Estimates' => ['orange', 'bi-calculator'],
                            default => ['green', 'bi-receipt'],
                        };
                    @endphp
                    <div class="col-6 col-lg-3">
                        <div class="customer-overview-kpi customer-overview-kpi--{{ $kpiStyle[0] }}">
                            <div class="customer-overview-kpi__content">
                                <div class="customer-overview-kpi__label">{{ $label }}</div>
                                <div class="customer-overview-kpi__count">{{ $count }}</div>
                            </div>
                            <span class="customer-overview-kpi__icon" aria-hidden="true"><i class="bi {{ $kpiStyle[1] }}"></i></span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Tabbed Details Section --}}
            <div class="mt-4 border rounded">
                <div class="px-4 pt-4">
                    <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i> Detailed Insights
                    </h6>
                </div>

                <div class="overflow-x-auto overflow-y-hidden" style="scrollbar-width: thin;">
                    <ul class="nav nav-tabs px-3 px-md-4 border-bottom-0 flex-wrap" id="customerDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold text-nowrap" id="address-tab" data-bs-toggle="tab"
                                data-bs-target="#address" type="button" role="tab"><i class="bi bi-geo-alt" aria-hidden="true"></i>Address Info</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-nowrap" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                type="button" role="tab"><i class="bi bi-person-lines-fill" aria-hidden="true"></i>Contact Details</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-nowrap" id="meetings-tab" data-bs-toggle="tab" data-bs-target="#meetings"
                                type="button" role="tab"><i class="bi bi-calendar-event" aria-hidden="true"></i>Meetings</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-nowrap" id="deals-tab" data-bs-toggle="tab" data-bs-target="#deals"
                                type="button" role="tab"><i class="bi bi-briefcase" aria-hidden="true"></i>Deals</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-nowrap" id="followups-tab" data-bs-toggle="tab" data-bs-target="#followups"
                                type="button" role="tab"><i class="bi bi-clock-history" aria-hidden="true"></i>Followups</button>
                        </li>
                        @if(auth()->user()->hasMatrixPermission('view_invoices'))<li class="nav-item" role="presentation"><button class="nav-link fw-semibold text-nowrap" id="customer-finance-tab" data-bs-toggle="tab" data-bs-target="#customer-finance" type="button" role="tab"><i class="bi bi-bank"></i>Finance & Payments</button></li>@endif
                        @foreach(['pipeline' => 'Pipeline', 'documents' => 'Documents', 'workflow' => 'Workflow', 'additional' => 'Additional Details'] as $tab => $label)
                            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold text-nowrap" id="customer-{{ $tab }}-tab" data-bs-toggle="tab" data-bs-target="#customer-{{ $tab }}" type="button" role="tab"><i class="bi {{ ['pipeline' => 'bi-diagram-3', 'documents' => 'bi-folder2-open', 'workflow' => 'bi-signpost-split', 'additional' => 'bi-info-circle'][$tab] }}" aria-hidden="true"></i>{{ $label }}@if($tab === 'documents') <span class="badge bg-secondary ms-1">{{ $overview['documents']->count() }}</span>@endif</button></li>
                        @endforeach
                        @foreach(['solar'=>'Solar Documents','subsidy'=>'Subsidy Bond'] as $type=>$label)
                            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold text-nowrap" id="customer-{{ $type }}-documents-tab" data-bs-toggle="tab" data-bs-target="#customer-{{ $type }}-documents" type="button" role="tab" aria-controls="customer-{{ $type }}-documents"><i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ $label }} <span class="badge bg-secondary ms-1">{{ $formDocuments->where('type',$type)->count() }}</span></button></li>
                        @endforeach
                        @foreach($overview['sections'] as $key => $section)
    @continue(in_array($key, ['pipelines', 'bookings', 'messages', 'leads'], true))
                            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold text-nowrap" id="customer-{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#customer-{{ $key }}" type="button" role="tab"><i class="bi {{ ['estimates' => 'bi-calculator', 'invoices' => 'bi-receipt', 'projects' => 'bi-building', 'tasks' => 'bi-list-check', 'tickets' => 'bi-ticket-perforated', 'sales' => 'bi-box-arrow-up-right', 'purchases' => 'bi-box-arrow-in-down'][$key] ?? 'bi-folder' }}" aria-hidden="true"></i>{{ $section['label'] }} <span class="badge bg-secondary ms-1">{{ $section['records']->count() }}</span></button></li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-content border-top" id="customerDetailsTabContent">
                    @include('masters.customers.partials.overview')
                    @include('masters.customers.partials.form-documents')
                    {{-- Address Details --}}
                    <div class="tab-pane fade show active p-0" id="address" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4" style="width: 30%;">Website</td>
                                        <td class="pe-4">
                                            @if($customer->website)
                                                <a href="{{ $customer->website }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->website }}</a>
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Address</td>
                                        <td class="pe-4">{{ $customer->address ?? '--' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Country</td>
                                        <td class="pe-4">{{ $customer->country->name ?? '--' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">State/City</td>
                                        <td class="pe-4">{{ $customer->city->name ?? '--' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Tax Number</td>
                                        <td class="pe-4">{{ $customer->tax_number ?? '--' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Contact Details --}}
                    <div class="tab-pane fade p-0" id="contact" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4" style="width: 30%;">Phone Number</td>
                                        <td class="pe-4">
                                            @if($customer->phone)
                                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none link-hover">{{ $customer->phone }}</a>
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">WhatsApp Number</td>
                                        <td class="pe-4">
                                            @if($customer->whatsapp)
                                                <a href="https://wa.me/{{ $customer->whatsapp }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->whatsapp }}</a>
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Email</td>
                                        <td class="pe-4">
                                            @if($customer->email)
                                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none link-hover">{{ $customer->email }}</a>
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Date of Birth</td>
                                        <td class="pe-4">{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M Y') : '--' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold ps-4">Status</td>
                                        <td class="pe-4">
                                            @if($customer->is_active)
                                                <span class="badge bg-success opacity-75">Active</span>
                                            @else
                                                <span class="badge bg-danger opacity-75">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Meetings --}}
                    <div class="tab-pane fade p-0" id="meetings" role="tabpanel">
                        @if($customer->meetings->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Title</th>
                                            <th>Type</th>
                                            <th>Scheduled</th>
                                            <th>Status</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->meetings->sortByDesc('scheduled_at') as $meeting)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $meeting->title ?: '--' }}</td>
                                                <td><span class="text-muted small">{{ $meeting->meeting_type_label }}</span></td>
                                                <td><span class="text-muted small">{{ $meeting->scheduled_at?->format('d M Y h:i A') ?? '--' }}</span></td>
                                                <td>
                                                    @php
                                                        $meetingStatusClass = match ($meeting->status) {
                                                            'scheduled' => 'bg-primary',
                                                            'completed' => 'bg-success',
                                                            'cancelled' => 'bg-danger',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $meetingStatusClass }} opacity-75">{{ $meeting->status_label }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="{{ route('meetings.show', $meeting) }}"
                                                        class="btn btn-sm btn-outline-dark-blue">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5 px-4">
                                <i class="bi bi-calendar-event display-5 d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No meetings found for this customer.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Deals --}}
                    <div class="tab-pane fade p-0" id="deals" role="tabpanel">
                        @if($customer->deals->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Deal</th>
                                            <th>Stage</th>
                                            <th>Status</th>
                                            <th>Value</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->deals->sortByDesc('created_at') as $deal)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $deal->title ?: '--' }}</td>
                                                <td><span class="badge border border-info text-info small">{{ $deal->stage->name ?? '--' }}</span></td>
                                                <td>
                                                    @php
                                                        $dealStatusColor = $deal->status->color ?? null;
                                                    @endphp
                                                    @if($dealStatusColor)
                                                        <span class="badge text-white"
                                                            style="background-color: {{ $dealStatusColor }};">{{ $deal->status->name ?? '--' }}</span>
                                                    @else
                                                        <span class="badge bg-secondary opacity-75">{{ $deal->status->name ?? '--' }}</span>
                                                    @endif
                                                </td>
                                                <td class="fw-bold text-dark">{{ ($deal->currency->symbol ?? $deal->currency->code ?? '') . ($deal->amount ? number_format((float) $deal->amount, 2) : '0.00') }}</td>
                                                <td class="text-end pe-4">
                                                    <a href="{{ route('deals.show', $deal) }}"
                                                        class="btn btn-sm btn-outline-dark-blue">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5 px-4">
                                <i class="fa-solid fa-indian-rupee-sign display-5 d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No deals found for this customer.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Followups --}}
                    <div class="tab-pane fade p-0" id="followups" role="tabpanel">
                        @if(isset($customer->followUps) && $customer->followUps->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Purpose</th>
                                            <th>Status</th>
                                            <th>Follow-up At</th>
                                            <th>Assigned To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->followUps as $followup)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $followup->purpose ?: '--' }}</td>
                                                <td>
                                                    @php
                                                        $fuStatusClass = match ($followup->status) {
                                                            'pending' => 'bg-warning text-dark',
                                                            'completed' => 'bg-success',
                                                            'cancelled' => 'bg-danger',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $fuStatusClass }} opacity-75">{{ ucfirst($followup->status) }}</span>
                                                </td>
                                                <td><span class="text-muted small">{{ $followup->follow_up_at?->format('d M Y h:i A') ?? '--' }}</span></td>
                                                <td>{{ $followup->assignedUser->name ?? '--' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5 px-4">
                                <i class="fas fa-calendar-alt display-5 d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No followups found for this customer.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/customer-documents.css') }}">
<style>
    #customerDetailsTabs {
        gap: 8px;
        padding-top: 14px;
        padding-bottom: 18px;
        background: var(--crm-bg-page, #f6f8fc);
        border-top: 1px solid var(--crm-border, #e2e8f0);
    }
    #customerDetailsTabs .nav-item { margin: 0; }
    #customerDetailsTabs .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 38px;
        margin: 0;
        padding: 8px 12px;
        border: 1px solid var(--crm-border, #e2e8f0);
        border-radius: 9px;
        background: var(--bs-body-bg, #fff);
        color: var(--crm-text-body, #475569);
        font-size: .8rem;
        line-height: 1.3;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    #customerDetailsTabs .nav-link > i { font-size: .9rem; opacity: .8; }
    #customerDetailsTabs .nav-link:hover { background: #edf2f8; border-color: #b8c9da; color: #0a2540; }
    #customerDetailsTabs .nav-link.active {
        background: #0a2540;
        border-color: #0a2540;
        color: #fff;
        box-shadow: 0 3px 8px rgba(10,37,64,.15);
    }
    #customerDetailsTabs .nav-link:focus-visible { outline: 2px solid #6585a5; outline-offset: 3px; }
    #customerDetailsTabs .nav-link .badge {
        min-width: 21px;
        padding: 3px 6px;
        margin-left: 0 !important;
        border-radius: 6px;
        background: #edf1f7 !important;
        color: #53657b;
        font-size: .67rem;
        font-weight: 600;
    }
    #customerDetailsTabs .nav-link.active .badge { background: rgba(255,255,255,.18) !important; color: #fff; }
    [data-theme="dark"] #customerDetailsTabs .nav-link { background: #18263a; color: #cbd5e1; }
    [data-theme="dark"] #customerDetailsTabs .nav-link:hover { background: #263b55; color: #fff; }
    [data-theme="dark"] #customerDetailsTabs .nav-link.active { background: #0a2540; border-color: #6585a5; color: #fff; }
    .customer-overview-kpi {
        --kpi-accent: #3267ff;
        --kpi-tint: #eaf0ff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 104px;
        height: 100%;
        padding: 20px;
        border: 1px solid var(--crm-border, #dfe6f1);
        border-left: 3px solid var(--kpi-accent);
        border-radius: 13px;
        background: linear-gradient(115deg, var(--bs-body-bg, #fff), var(--kpi-tint));
    }
    .customer-overview-kpi--purple { --kpi-accent: #8145ff; --kpi-tint: #f1eaff; }
    .customer-overview-kpi--orange { --kpi-accent: #dc7900; --kpi-tint: #fff2e3; }
    .customer-overview-kpi--green { --kpi-accent: #009d78; --kpi-tint: #e2f5ef; }
    .customer-overview-kpi__content { min-width: 0; }
    .customer-overview-kpi__label { color: var(--crm-text-muted, #64748b); font-size: .82rem; font-weight: 500; margin-bottom: 5px; }
    .customer-overview-kpi__count { color: var(--crm-text-heading, #0a2540); font-size: 1.7rem; line-height: 1.1; font-weight: 700; overflow-wrap: anywhere; }
    .customer-overview-kpi__icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 44px; width: 44px; height: 44px; border-radius: 13px; color: var(--kpi-accent); background: var(--kpi-tint); font-size: 1.1rem; }
    [data-theme="dark"] .customer-overview-kpi { background: var(--crm-bg-card, #162033); }
    [data-theme="dark"] .customer-overview-kpi__count { color: #e2e8f0; }
    [data-theme="dark"] .customer-overview-kpi__icon { background: color-mix(in srgb, var(--kpi-accent) 16%, transparent); }
    @media (max-width: 575.98px) {
        .customer-overview-kpi { padding: 14px; gap: .5rem; min-height: 94px; }
        .customer-overview-kpi__icon { width: 36px; height: 36px; flex-basis: 36px; border-radius: 10px; }
    }
</style>
@endpush

@push('scripts')
@include('partials.customer-document-modal')
<script src="{{ crm_asset('js/customer-document-uploader.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let documentsChanged = false;
    const refreshDocuments = function () {
        window.location.hash = 'customer-documents';
        window.location.reload();
    };
    const uploader = new window.CustomerDocumentUploader({
        customer: true,
        url: () => @json(route('masters.customers.docs.index', $customer)),
        onChanged: () => { documentsChanged = true; },
    });
    document.getElementById('customerUploadDocuments')?.addEventListener('click', function () {
        uploader.open(@json($customer->id));
    });
    document.getElementById('estimateDocsModal').addEventListener('hidden.bs.modal', function () {
        if (documentsChanged) refreshDocuments();
    });
    document.querySelectorAll('.customer-delete-document').forEach(function (button) {
        button.addEventListener('click', async function () {
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                if (!response.ok) throw new Error('Unable to delete this document. Please refresh and try again.');
                refreshDocuments();
            } catch (error) {
                if (window.showAlert) window.showAlert('error', error.message);
                else window.alert(error.message);
                button.disabled = false;
            }
        });
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openLinkedCustomerTab = function () {
            const button = Array.from(document.querySelectorAll('#customerDetailsTabs [data-bs-toggle="tab"]'))
                .find(function (tab) { return tab.getAttribute('data-bs-target') === window.location.hash; });
            if (button && window.bootstrap) bootstrap.Tab.getOrCreateInstance(button).show();
        };
        openLinkedCustomerTab();
        window.addEventListener('hashchange', openLinkedCustomerTab);
    });
</script>
@endpush
