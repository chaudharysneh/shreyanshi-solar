@extends('layouts.app')

@section('page_title', 'Products - View')

@push('styles')
    <style>
        .product-detail-image-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #f8fafc;
            overflow: hidden;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-detail-image-card img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: cover;
        }

        .product-detail-image-empty {
            color: #64748b;
            text-align: center;
            padding: 1.5rem;
        }

        .product-detail-side .detail-view-grid {
            border-top: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 detail-view-card">
                    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h1 class="h4 mb-1 fw-semibold">Product Details</h1>
                                <p class="text-muted small mb-0">Complete information about this product.</p>
                            </div>
                            <div
                                class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                @can('products.edit')
                                    <a href="{{ route('products.edit', $product) }}"
                                        class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                @endcan
                                <a href="{{ route('products.index') }}"
                                    class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="fa-solid fa-angle-left pe-1"></i>
                                    <span>Back</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="detail-view-block px-md-4">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-12">
                                    <div class="row g-0 detail-view-grid">
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-box"></i> Product Name:</span>
                                            <span class="detail-view-value">{{ $product->name ?: '-' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-tag"></i> Category:</span>
                                            <span
                                                class="detail-view-value">{{ optional($product->category)->name ?? '-' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-bar-chart"></i> Quantity:</span>
                                            <span class="detail-view-value">{{ $product->quantity ?: '0' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-box-seam"></i> Stock:</span>
                                            <span class="detail-view-value">
                                                @if ($product->availability == 'in_stock')
                                                    <span class="badge bg-success">In Stock</span>
                                                @elseif($product->availability == 'out_of_stock')
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-hash"></i> Serial No:</span>
                                            <span class="detail-view-value">{{ $product->serial_no ?: '-' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-bullseye"></i> Status:</span>
                                            <span class="detail-view-value">
                                                @if ($product->status == 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($product->status == 'inactive')
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>

                                        <div class="col-12 detail-view-row">
                                            <span class="detail-view-label"><i class="bi bi-pencil-square"></i>
                                                Description:</span>
                                            <span class="detail-view-value">{{ $product->description ?: '-' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Created At:</span>
                                            <span
                                                class="detail-view-value">{{ optional($product->created_at)?->format('d M, Y h:i A') ?? '-' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Created By:</span>
                                            <span
                                                class="detail-view-value">{{ optional($product->creator)->name ?? '-' }}</span>
                                        </div>
                                    </div>

                                    @php
                                        $customFields = $product->customFieldValues ?? collect();
                                    @endphp
                                    @if ($customFields->count())
                                        <div class="mt-4 pt-3 border-top">
                                            <div class="row g-0 detail-view-grid">
                                                @foreach ($customFields as $fieldValue)
                                                    <div class="col-md-6 detail-view-row">
                                                        <span
                                                            class="detail-view-label">{{ $fieldValue->customField->label ?? 'Custom Field' }}:</span>
                                                        <span
                                                            class="detail-view-value">{{ $fieldValue->value ?: '-' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
