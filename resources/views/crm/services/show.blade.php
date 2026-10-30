@extends('layouts.app')

@section('page_title', 'Service Profile')

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm border-0 detail-view-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h4 mb-1">Service Details</h1>
                        <p class="text-muted small mb-0">Complete information about this service</p>
                    </div>

                    <div class="d-flex gap-2">
                        @can('services.edit')
                        <a href="{{ route('services.edit', $service->id) }}" class="btn btn-dark-blue">
                            <i class="bi bi-pencil me-1"></i>Edit Service
                        </a>
                        @endcan
                        <a href="{{ route('services.index') }}" class="btn btn-dark-blue">
                            <i class="fa-solid fa-angle-left pe-2"></i>Back
                        </a>
                    </div>
                </div>

                @php
                    $badge = match ($service->status) {
                        'active' => 'bg-success',
                        'inactive' => 'bg-secondary',
                        default => 'bg-secondary',
                    };
                @endphp

                <div class="detail-view-block px-md-5">
                    <h2 class="detail-view-title">{{ $service->service_name ?? '-' }}</h2>

                    <div class="row g-0 detail-view-grid">
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Created By:</span>
                            <span class="detail-view-value">{{ $service->creator?->name ?? 'Admin' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Created At:</span>
                            <span class="detail-view-value">{{ $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d M Y h:i A') : '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Service Description:</span>
                            <span class="detail-view-value">{{ $service->description ?? '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Price:</span>
                            <span class="detail-view-value">INR {{ number_format((float) $service->service_price, 2) }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Status:</span>
                            <span class="badge crm-status-pill rounded-pill {{ $badge }}">
                                {{ strtoupper($service->status ?? '-') }}
                            </span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Product:</span>
                            <span class="detail-view-value">{{ $service->product?->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
