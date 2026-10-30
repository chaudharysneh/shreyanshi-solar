@extends('layouts.app')

@section('page_title', 'Pipeline Details')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 detail-view-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Pipeline Details</h1>
                    <p class="text-muted small mb-0">Complete information about this pipeline</p>
                </div>
                <div class="d-flex gap-2">
                    @can('pipeline.edit')
                    <a href="{{ route('pipeline.edit', $pipeline) }}" class="btn btn-dark-blue btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit Pipeline
                    </a>
                    @endcan
                    <a href="{{ route('pipeline.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            @php
                $statusValue = $pipeline->status;
                $statusLabel = match ($statusValue) {
                    'in_progress' => 'In-Process',
                    'paused' => 'Paused',
                    'completed' => 'Completed',
                    default => ucfirst(str_replace('_', ' ', (string) $statusValue)),
                };
                $statusClass = match ($statusValue) {
                    'in_progress' => 'bg-info',
                    'paused' => 'bg-warning',
                    'completed' => 'bg-success',
                    default => 'bg-secondary',
                };
            @endphp
            <div class="detail-view-block px-md-5">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer:</span>
                        <span class="detail-view-value">{{ $pipeline->customer?->name ?? '-' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Pipeline Stage:</span>
                        <span class="detail-view-value">{{ $pipeline->stage?->name ?? '-' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Status:</span>
                        <span class="badge crm-status-pill rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created By:</span>
                        <span class="detail-view-value">{{ $pipeline->creator?->name ?? 'Admin' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created At:</span>
                        <span class="detail-view-value">{{ $pipeline->created_at ? $pipeline->created_at->format('d M, Y') : '-' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Description:</span>
                        <span class="detail-view-value">{{ $pipeline->description ?? '-' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
