@extends('layouts.app')

@section('page_title', 'Meeting Details')

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card mb-4">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Meeting Details</h1>
                        <p class="text-muted small mb-0">Complete information about this scheduled meeting.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('meetings.edit')
                            <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user"></i>Customer:</span>
                        <span class="detail-view-value">{{ $meeting->customer?->name ?? '--' }}</span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user-tie"></i>Assigned Staff:</span>
                        <span class="detail-view-value">{{ $meeting->assignedUser?->name ?? 'Unassigned' }}</span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-handshake"></i>Meeting Type:</span>
                        <span class="detail-view-value text-capitalize">{{ $meeting->meeting_type ?? '--' }}</span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                        <span class="detail-view-value">
                            @php
                                $badge = match ($meeting->status) {
                                    'scheduled' => 'bg-warning text-dark',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badge }} opacity-75 rounded-pill px-3 py-2 text-capitalize">
                                {{ $meeting->status ?? '--' }}
                            </span>
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-calendar-days"></i>Scheduled At:</span>
                        <span class="detail-view-value">
                            {{ $meeting->scheduled_at ? \Carbon\Carbon::parse($meeting->scheduled_at)->format('d M, Y h:i A') : 'Not set' }}
                        </span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-location-dot"></i>Location:</span>
                        <span class="detail-view-value">{{ $meeting->address ?? '--' }}</span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user-plus"></i>Created By:</span>
                        <span class="detail-view-value">
                            {{ $meeting->creator?->name ?? '--' }} | {{ $meeting->created_at?->format('d M, Y') ?? '--' }}
                        </span>
                    </div>
                    
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-clock-rotate-left"></i>Last Updated:</span>
                        <span class="detail-view-value">
                            {{ $meeting->updater?->name ?? '--' }} | {{ $meeting->updated_at?->format('d M, Y') ?? '--' }}
                        </span>
                    </div>
                </div>

                @if($meeting->agenda)
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2"></i>Agenda</h6>
                        <div class="p-3 bg-light rounded-3 text-muted small text-break">
                            {{ $meeting->agenda }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $meeting->statusHistories])
            </div>
        </div>
    </div>
@endsection
