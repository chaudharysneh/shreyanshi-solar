@extends('layouts.app')

@section('page_title', 'Follow Up Details')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card mb-4">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Follow Up Details</h1>
                        <p class="text-muted small mb-0">Complete information about this scheduled follow up.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('followups.edit')
                            <a href="{{ route('followups.edit', $followUp) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('followups.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-bullseye"></i>Purpose:</span>
                        <span class="detail-view-value">{{ $followUp->purpose ?? '-' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user"></i>Lead/Customer:</span>
                        <span class="detail-view-value">
                            {{ $followUp->lead->name ?? ($followUp->customer->name ?? '--') }}
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user-tie"></i>Assigned Staff:</span>
                        <span class="detail-view-value">{{ $followUp->assignedUser?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-triangle-exclamation"></i>Priority:</span>
                        <span class="detail-view-value">
                            @php
                                $priorityClass = [
                                    'low' => 'bg-info',
                                    'medium' => 'bg-primary',
                                    'high' => 'bg-danger'
                                ][$followUp->priority] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $priorityClass }} opacity-75 rounded-pill px-3 py-2 text-capitalize">
                                {{ $followUp->priority }}
                            </span>
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-calendar-days"></i>Follow Up At:</span>
                        <span class="detail-view-value">
                            {{ $followUp->follow_up_at ? \Illuminate\Support\Carbon::parse($followUp->follow_up_at)->format('d M, Y h:i A') : 'Not set' }}
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                        <span class="detail-view-value">
                            @php
                                $statusClass = [
                                    'pending' => 'bg-warning text-dark',
                                    'resheduled' => 'bg-info',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger'
                                ][$followUp->status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $statusClass }} opacity-75 rounded-pill px-3 py-2 text-capitalize">
                                {{ $followUp->status }}
                            </span>
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user-plus"></i>Created By:</span>
                        <span class="detail-view-value">
                            {{ $followUp->creator?->name ?? '--' }} | {{ $followUp->created_at?->format('d M, Y') ?? '-' }}
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-clock-rotate-left"></i>Last Update:</span>
                        <span class="detail-view-value">{{ $followUp->updated_at?->format('d M, Y h:i A') ?? '-' }}</span>
                    </div>
                </div>

                @if($followUp->comment)
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-2"></i>Internal Comment</h6>
                        <div class="p-3 bg-light rounded-3 text-muted small text-break">
                            {{ $followUp->comment }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet"
            href="{{ crm_asset('css/main.css') }}">
    @endpush

@endsection