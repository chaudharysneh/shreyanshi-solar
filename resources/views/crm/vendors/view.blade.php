@extends('layouts.app')

@section('page_title', 'Vendor Details')

@push('styles')
    <style>
        .vendor-detail-image-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #f8fafc;
            overflow: hidden;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vendor-detail-image-card img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: cover;
        }

        .vendor-detail-image-empty {
            color: #64748b;
            text-align: center;
            padding: 1.5rem;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h1 class="h4 mb-1 fw-semibold">Vendor Details</h1>
                            <p class="text-muted small mb-0">Complete information about this vendor</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <a href="{{ route('vendors.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-1"></i>
                                <span>Back</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="detail-view-block px-md-4">
                        <div class="row g-4 align-items-start">
                            <div class="col-lg-3 col-md-4">
                                <div class="vendor-detail-image-card">
                                    @if($vendor->image)
                                        <img src="{{ route('vendors.image', $vendor) }}?v={{ optional($vendor->updated_at)?->timestamp ?? time() }}" alt="{{ $vendor->name }}">
                                    @else
                                        <div class="vendor-detail-image-empty">
                                            <i class="bi bi-image fs-1 d-block mb-2"></i>
                                            <span>No image uploaded</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-8">
                                <div class="row g-0 detail-view-grid">
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-building text-muted me-2"></i>Vendor Name:</span><span class="detail-view-value">{{ $vendor->name ?: '-' }}</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-envelope text-muted me-2"></i>Email:</span><span class="detail-view-value">@if($vendor->email)<a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a>@else -- @endif</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-telephone text-muted me-2"></i>Phone:</span><span class="detail-view-value">@if($vendor->phone)<a href="tel:{{ $vendor->phone }}">{{ $vendor->phone }}</a>@else -- @endif</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-toggle-on text-muted me-2"></i>Status:</span><span class="detail-view-value"><span class="badge bg-success rounded-pill px-3">{{ ucfirst($vendor->status ?: 'Active') }}</span></span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-geo-alt text-muted me-2"></i>Address:</span><span class="detail-view-value">{{ $vendor->address ?: '--' }}</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-calendar-plus text-muted me-2"></i>Created At:</span><span class="detail-view-value">{{ optional($vendor->created_at)?->format('d M, Y h:i A') ?? '-' }}</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-person-check text-muted me-2"></i>Created By:</span><span class="detail-view-value">{{ optional($vendor->creator)->name ?? '-' }}</span></div>
                                    <div class="col-md-6 detail-view-row"><span class="detail-view-label"><i class="bi bi-pencil-square text-muted me-2"></i>Updated By:</span><span class="detail-view-value">{{ optional($vendor->updater)->name ?? '-' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
