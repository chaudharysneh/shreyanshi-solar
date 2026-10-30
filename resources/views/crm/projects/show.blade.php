@extends('layouts.app')

@section('page_title', 'Project Profile')

@section('content')
    <div class="container-fluid">

        <div class="row g-4">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0 detail-view-card">
                    <div class="card-body">

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h4 mb-1">Project Details</h1>
                                <p class="text-muted small mb-0">Complete information about this project</p>
                            </div>

                            <div class="d-flex gap-2">
                                @can('projects.edit')
                                <a href="{{ route('projects.edit', $project) }}" class="btn btn-dark-blue btn-sm">
                                    <i class="bi bi-pencil me-1"></i>Edit Project
                                </a>
                                @endcan

                                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </a>
                            </div>
                        </div>

                        <div class="detail-view-block px-md-5">
                            @php
                                $badge = match ($project->status) {
                                    'pending' => 'bg-warning',
                                    'ongoing' => 'bg-info',
                                    'completed' => 'bg-success',
                                    'canceled' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <div class="row g-0 detail-view-grid">
                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Customer:</span>
                                    <span class="detail-view-value">{{ $project->customer->name ?? '-' }}</span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Assigned To:</span>
                                    <span class="detail-view-value">{{ $project->assignedUser->name ?? 'Unassigned' }}</span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Status:</span>
                                    <span class="badge crm-status-pill {{ $badge }} rounded-pill ms-1">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Start Date:</span>
                                    <span class="detail-view-value">
                                        {{ $project->start_date ? \Illuminate\Support\Carbon::parse($project->start_date)->format('d M, Y') : 'Not set' }}
                                    </span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">End Date:</span>
                                    <span class="detail-view-value">
                                        {{ $project->end_date ? \Illuminate\Support\Carbon::parse($project->end_date)->format('d M, Y') : 'Not set' }}
                                    </span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Created:</span>
                                    <span class="detail-view-value">
                                        {{ $project->created_at?->format('d M, Y') ?? '-' }}
                                        @if($project->creator)
                                            by {{ $project->creator->name }}
                                        @endif
                                    </span>
                                </div>

                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">Last Updated:</span>
                                    <span class="detail-view-value">
                                        {{ $project->updated_at?->format('d M, Y') ?? '-' }}
                                        @if($project->updater)
                                            by {{ $project->updater->name }}
                                        @endif
                                    </span>
                                </div>

                                @if($project->description)
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label">Description:</span>
                                        <span class="detail-view-value">{{ $project->description }}</span>
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
