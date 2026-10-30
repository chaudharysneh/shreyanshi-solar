@extends('layouts.app')

@section('page_title', 'Ticket Details')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Ticket Details</h1>
                        <p class="text-muted small mb-0">Complete information about this support ticket</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tickets.edit')
                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('tickets.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                @php
                    $priorityBadge = match ($ticket->priority) {
                        'Low' => 'bg-info text-dark',
                        'Medium' => 'bg-primary',
                        'High' => 'bg-warning text-dark',
                        'Urgent' => 'bg-danger',
                        default => 'bg-secondary',
                    };

                    $statusBadge = match ($ticket->status) {
                        'Open' => 'bg-info text-dark',
                        'In Progress' => 'bg-primary',
                        'Resolved' => 'bg-success',
                        'Closed' => 'bg-secondary',
                        default => 'bg-secondary',
                    };
                @endphp

                <div class="detail-view-block">
                    <h2 class="detail-view-title mb-4">{{ $ticket->ticket_name ?? '-' }}</h2>

                    <div class="row g-0 detail-view-grid">
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-user-plus"></i>Created By:</span>
                            <span class="detail-view-value">{{ $ticket->creator?->name ?? (auth()->user()?->name ?? 'Admin') }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-user"></i>Customer Name:</span>
                            <span class="detail-view-value">{{ $ticket->customer?->name ?? '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-calendar-days"></i>Created At:</span>
                            <span class="detail-view-value">{{ $ticket->created_at?->format('d M Y h:i A') ?? '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-align-left"></i>Ticket Description:</span>
                            <span class="detail-view-value">{{ $ticket->description ?? '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-triangle-exclamation"></i>Priority:</span>
                            <span class="detail-view-value">
                                <span class="badge crm-status-pill rounded-pill {{ $priorityBadge }}">{{ $ticket->priority }}</span>
                            </span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                            <span class="detail-view-value">
                                <span class="badge crm-status-pill rounded-pill {{ $statusBadge }}">{{ $ticket->status }}</span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @include('crm.partials.status-history-table', ['histories' => $ticket->statusHistories])
    </div>
@endsection

@push('scripts')
@endpush
