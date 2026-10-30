@extends('layouts.app')

@section('page_title', 'Deal Details')

@section('content')
    @if($deal->invoice)
        @include('crm.invoices.partials.finance-summary', ['invoice' => $deal->invoice])
    @endif
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Deal Details</h1>
                        <p class="text-muted small mb-0">Complete information about this deal</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('deals.edit')
                            <a href="{{ route('deals.edit', $deal) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('deals.pdf', $deal->id) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-file-pdf me-1"></i>PDF
                        </a>
                        <a href="{{ route('deals.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                @php
                    $statusName = strtolower((string) $deal->status?->name);
                    $statusColor = $deal->status?->color;
                    $statusBadge = match ($statusName) {
                        'pending' => 'bg-secondary text-white',
                        'in-process', 'in process' => 'bg-info text-white',
                        'paused' => 'bg-warning text-dark',
                        'won/confirm', 'won' => 'bg-success text-white',
                        'lost' => 'bg-danger text-white',
                        default => 'bg-secondary text-white',
                    };
                    $timelineValue = $deal->timeline_value ? (int) $deal->timeline_value : null;
                    $timelineUnit = strtolower((string) ($deal->timeline_unit ?? 'days'));
                    $timelineLabel = $timelineValue
                        ? $timelineValue . ' ' . ($timelineValue === 1
                            ? rtrim($timelineUnit, 's')
                            : $timelineUnit)
                        : '--';
                @endphp
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user"></i>Created By:</span>
                        <span class="detail-view-value">{{ $deal->creator?->name ?? '-' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-calendar-days"></i>Created At:</span>
                        <span class="detail-view-value">{{ $deal->created_at?->format('j M Y h:i A') ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-envelope"></i>Email:</span>
                        <span class="detail-view-value">
                            @if ($deal->customer?->email)
                                <a href="mailto:{{ $deal->customer->email }}" class="text-decoration-none link-hover">{{ $deal->customer->email }}</a>
                            @else
                                --
                            @endif
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-phone"></i>Phone:</span>
                        <span class="detail-view-value">
                            @if($deal->customer?->phone)
                                <a href="tel:{{ $deal->customer->phone }}" class="text-decoration-none link-hover">{{ $deal->customer->phone }}</a>
                            @else
                                --
                            @endif
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-brands fa-whatsapp"></i>WhatsApp:</span>
                        <span class="detail-view-value">{{ $deal->customer?->whatsapp ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-cake-candles"></i>Date of Birth:</span>
                        <span class="detail-view-value">{{ $deal->customer?->dob ? \Carbon\Carbon::parse($deal->customer->dob)->format('j M Y') : '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-calendar-day"></i>Anniversary:</span>
                        <span class="detail-view-value">{{ $deal->customer?->anniversary_date ? \Carbon\Carbon::parse($deal->customer->anniversary_date)->format('j M Y') : '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-globe"></i>Website:</span>
                        <span class="detail-view-value">
                            @if ($deal->customer?->website)
                                <a href="{{ $deal->customer->website }}" target="_blank" class="text-decoration-none link-hover">{{ $deal->customer->website }}</a>
                            @else
                                --
                            @endif
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-file-invoice"></i>Tax Number:</span>
                        <span class="detail-view-value">{{ $deal->customer?->tax_number ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-location-dot"></i>Location:</span>
                        <span class="detail-view-value">{{ $deal->customer?->address ?? '--' }}{{ $deal->customer?->city ? ', ' . $deal->customer->city->name : '' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-building"></i>Company:</span>
                        <span class="detail-view-value">{{ $deal->customer?->company_name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-tag"></i>Type:</span>
                        <span class="detail-view-value">{{ ucfirst($deal->customer?->type ?? 'Individual') }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-file-signature"></i>Invoice Name:</span>
                        <span class="detail-view-value">{{ $deal->invoice?->invoice_name ?? 'No invoice linked' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-indian-rupee-sign"></i>Invoice Value:</span>
                        <span class="detail-view-value">{{ $deal->invoice ? number_format((float) $deal->invoice->amount, 2, '.', '').' '.($deal->invoice->currency?->code ?? '') : '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-clock"></i>Time Line:</span>
                        <span class="detail-view-value">{{ $timelineLabel }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                        <span class="badge rounded-pill px-3 {{ $statusColor ? '' : $statusBadge }}"
                            @if ($statusColor) style="background-color: {{ $statusColor }}; color: #fff;" @endif>
                            {{ $deal->status?->name ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
