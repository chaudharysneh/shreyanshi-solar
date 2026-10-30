@extends('layouts.app')

@section('page_title', 'BOM - View')

@push('styles')
    <style>
        .bom-detail-image-card {
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
            min-height: 220px;
            width: 220px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bom-detail-image-card img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: contain;
            padding: 1rem;
        }

        .bom-detail-image-empty {
            color: #64748b;
            text-align: center;
            padding: 1.5rem;
        }

        .bom-detail-side .detail-view-grid {
            border-top: 0;
        }

        /* Fix for description field to wrap text properly */
        .detail-view-row .detail-view-value {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }
    </style>
@endpush

@section('content')
    @php
        $dummyBomImageUrl = url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'images/template/faveicone.png');
        $bomImageUrl = $bomProduct->image
            ? route('bom-products.image', $bomProduct) . '?v=' . (optional($bomProduct->updated_at)?->timestamp ?? time())
            : $dummyBomImageUrl;
    @endphp
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h1 class="h4 mb-1 fw-semibold">BOM Details</h1>
                                <p class="text-muted small mb-0">Complete information about this BOM</p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                <a href="{{ route('bom-products.edit', $bomProduct) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                <a href="{{ route('bom-products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="fa-solid fa-angle-left pe-1"></i>
                                    <span>Back</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4">

                        <div class="detail-view-block px-md-4">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-3 col-md-4 d-flex justify-content-center">
                                    <div class="bom-detail-image-card">
                                        <img src="{{ $bomImageUrl }}" alt="{{ $bomProduct->product_name ?: 'BOM image' }}" onerror="this.onerror=null;this.src='{{ $dummyBomImageUrl }}';">
                                    </div>
                                </div>

                                <div class="col-lg-9 col-md-8 bom-detail-side">
                                    <div class="row g-0 detail-view-grid">
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-briefcase text-muted me-2"></i>BOM Name:</span>
                                            <span class="detail-view-value">{{ $bomProduct->product_name ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-calendar-check text-muted me-2"></i>Created At:</span>
                                            <span class="detail-view-value">{{ optional($bomProduct->created_at)?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-person text-muted me-2"></i>Created By:</span>
                                            <span class="detail-view-value">{{ $bomProduct->creator?->name ?? 'Admin' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-shield-check text-muted me-2"></i>Warranty:</span>
                                            <span class="detail-view-value">{{ $bomProduct->warranty?->title ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-cpu text-muted me-2"></i>Technology:</span>
                                            <span class="detail-view-value">{{ $bomProduct->technology?->title ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-rulers text-muted me-2"></i>Size of Pipe:</span>
                                            <span class="detail-view-value">{{ $bomProduct->size_of_pipe ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-speedometer2 text-muted me-2"></i>Capacity:</span>
                                            <span class="detail-view-value">{{ $bomProduct->capacity ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-arrows-expand text-muted me-2"></i>Height:</span>
                                            <span class="detail-view-value">{{ $bomProduct->height ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-border-width text-muted me-2"></i>Thickness:</span>
                                            <span class="detail-view-value">{{ $bomProduct->thickness ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-box-seam text-muted me-2"></i>Fitting Material:</span>
                                            <span class="detail-view-value">{{ $bomProduct->fitting_material ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-gear-wide text-muted me-2"></i>Fitting Type:</span>
                                            <span class="detail-view-value">{{ $bomProduct->fitting_type ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-tags text-muted me-2"></i>Make:</span>
                                            <span class="detail-view-value">
                                                @if($bomProduct->categories && $bomProduct->categories->count() > 0)
                                                    @foreach($bomProduct->categories as $category)
                                                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $category->name }}</span>
                                                    @endforeach
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>

                                        <div class="col-12 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-text-paragraph text-muted me-2"></i>Description:</span>
                                            <span class="detail-view-value">{{ $bomProduct->description ?: '--' }}</span>
                                        </div>

                                        
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
