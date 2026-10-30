@extends('layouts.app')

@section('page_title', 'Task Details')

@push('styles')
    <style>
        .task-upload-card {
            border: 1px solid #eef2f7;
            border-radius: 1.2rem;
            background: #fff;
            box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.22);
        }

        [data-theme="dark"] .task-upload-card {
            border-color: #334155;
            background: #0f172a;
            box-shadow: 0 8px 24px -18px rgba(0, 0, 0, 0.5);
        }

        .task-upload-panel {
            height: 100%;
            padding: 0.5rem 0.85rem 0.25rem;
            border: 0;
            border-radius: 0;
            background: #fff !important;
            position: relative;
            color: #334155 !important;
        }

        [data-theme="dark"] .task-upload-panel {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }

        .task-upload-panel .task-history-meta,
        .task-upload-panel .task-history-meta strong,
        .task-upload-panel .task-history-location,
        .task-upload-panel .task-history-location strong,
        .task-upload-panel .task-history-date,
        .task-upload-panel .task-history-section-title span,
        .task-upload-panel div,
        .task-upload-panel p {
            color: #1e3a5f !important;
        }

        [data-theme="dark"] .task-upload-panel .task-history-meta,
        [data-theme="dark"] .task-upload-panel .task-history-meta strong,
        [data-theme="dark"] .task-upload-panel .task-history-location,
        [data-theme="dark"] .task-upload-panel .task-history-location strong,
        [data-theme="dark"] .task-upload-panel .task-history-date,
        [data-theme="dark"] .task-upload-panel .task-history-section-title span,
        [data-theme="dark"] .task-upload-panel div,
        [data-theme="dark"] .task-upload-panel p {
            color: #e2e8f0 !important;
        }

        .task-upload-panel.is-start {
            border-top: 0;
        }

        .task-upload-panel.is-end {
            border-top: 0;
        }

        .task-upload-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .task-upload-panel.is-start .task-upload-badge {
            background: rgba(34, 197, 94, 0.12);
            color: #15803d;
        }

        .task-upload-panel.is-end .task-upload-badge {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .task-upload-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.4rem;
        }

        .task-upload-value {
            color: #1e3a5f;
            font-weight: 500;
        }

        .task-upload-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
            gap: 0.85rem;
        }

        .task-upload-thumb {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .task-upload-thumb-frame {
            border-radius: 0.4rem;
            overflow: hidden;
            width: 80px;
            height: 80px;
            background: #edf4ff;
            border: 1px solid #dbe6f5;
        }

        [data-theme="dark"] .task-upload-thumb-frame {
            background: #1e293b;
            border-color: #334155;
        }

        .task-upload-thumb-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .task-upload-thumb-name {
            display: none;
        }

        .task-upload-file-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .task-upload-file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.7rem 0.9rem;
            border-radius: 0.9rem;
            border: 1px solid #dbe6f5;
            background: #fff;
            text-decoration: none;
            color: #1d4ed8;
            font-weight: 600;
        }

        [data-theme="dark"] .task-upload-file-link {
            border-color: #334155;
            background: #1e293b;
            color: #60a5fa;
        }

        .task-upload-empty {
            padding: 1rem;
            border-radius: 0.9rem;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #64748b;
        }

        [data-theme="dark"] .task-upload-empty {
            background: #1e293b;
            border-color: #475569;
            color: #cbd5e1;
        }

        .task-history-serial {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 74px;
            padding: 0.35rem 0.7rem;
            border-radius: 0.7rem;
            background: #6475db;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .task-history-date {
            position: absolute;
            top: 0.55rem;
            right: 0.25rem;
            font-size: 0.95rem;
            color: #64748b;
        }

        .task-history-section-title {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 1.05rem;
            font-weight: 700;
            margin-top: 0.85rem;
            margin-bottom: 0.9rem;
        }

        .task-history-section-title.is-start {
            color: #06b6d4;
        }

        .task-history-section-title.is-end {
            color: #22c55e;
        }

        .task-history-meta {
            margin-bottom: 1rem;
            color: #4b6385;
            font-size: 1rem;
        }

        .task-history-meta strong {
            color: #516b93;
        }

        .task-history-tabs {
            margin-bottom: 0.55rem;
        }

        .task-history-tabs .nav-link {
            color: #5b6b88;
            font-weight: 600;
            border-radius: 0;
            padding: 0.55rem 1rem;
        }

        .task-history-tabs .nav-link.active {
            color: #1d4ed8;
            background: #fff;
            border-color: #dee6f2 #dee6f2 #fff;
        }

        [data-theme="dark"] .task-history-tabs .nav-link {
            color: #94a3b8;
        }

        [data-theme="dark"] .task-history-tabs .nav-link.active {
            color: #60a5fa;
            background: #1e293b;
            border-color: #334155 #334155 #1e293b;
        }

        .task-history-tab-pane {
            min-height: 120px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 0.35rem;
        }

        .task-upload-panel .tab-content {
            background-color: #fff !important;
            border: 1px solid #dee6f2;
            border-top: 0;
            padding: 1rem;
            border-radius: 0 0 0.8rem 0.8rem;
        }

        [data-theme="dark"] .task-upload-panel .tab-content {
            background-color: #1e293b !important;
            color: #e2e8f0;
            border-color: #334155;
        }

        .task-history-location {
            margin-top: 1.8rem;
            color: #5d7396;
            font-size: 0.95rem;
            line-height: 1.45;
        }

        .task-history-location strong {
            color: #516b93;
        }
    </style>
    <link rel="stylesheet" href="{{ crm_asset('css/task-history.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        @include('crm.tasks.partials.invoice-workflow')
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Task Details</h1>
                        <p class="text-muted small mb-0">Complete information about this task</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tasks.edit')
                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                @php
                    $statusClass = match ($task->status) {
                        'completed' => 'task-detail-badge--success',
                        'in_progress' => 'task-detail-badge--info',
                        'pending' => 'task-detail-badge--warning',
                        default => 'task-detail-badge--neutral',
                    };
                    $priorityClass = match (strtolower($task->priority ?? '')) {
                        'high' => 'task-detail-badge--danger',
                        'medium' => 'task-detail-badge--warning',
                        'low' => 'task-detail-badge--info',
                        default => 'task-detail-badge--neutral',
                    };
                    $createdBy = $task->owner?->name ?? $task->project?->creator?->name ?? '-';
                    $invoiceName = $invoice?->invoice_name ?: ($invoice?->invoice_no ?: '-');
                    $staffName = $task->assignedUser?->name ?? $task->owner?->name ?? '-';
                    $documents = $task->documents ?? collect();
                    $completedHistories = $task->statusHistories
                        ->where('status', 'completed')
                        ->sortByDesc('created_at')
                        ->values();
                    $latestCompletedHistory = $completedHistories->first();
                    $latestCompletedAt = $latestCompletedHistory?->created_at;
                    $previousCompletedAt = $completedHistories->skip(1)->first()?->created_at;
                    $cycleStartedAt = $task->status === 'completed' ? $previousCompletedAt : $latestCompletedAt;
                    $cycleEndedAt = $task->status === 'completed' ? $latestCompletedAt : null;
                    $isWithinCycle = function ($timestamp) use ($cycleStartedAt, $cycleEndedAt) {
                        if (!$timestamp) {
                            return false;
                        }

                        if ($cycleStartedAt && !$timestamp->gt($cycleStartedAt)) {
                            return false;
                        }

                        if ($cycleEndedAt && $timestamp->gt($cycleEndedAt)) {
                            return false;
                        }

                        return true;
                    };
                    $startDocuments = $documents
                        ->filter(fn ($document) => \Illuminate\Support\Str::startsWith((string) $document->title, 'Task Start Image'))
                        ->filter(fn ($document) => $isWithinCycle($document->created_at))
                        ->sortByDesc('created_at')
                        ->take(1)
                        ->values();
                    $endDocuments = [
                        'light_bill' => $documents->where('title', 'Task End Light Bill')->filter(fn ($document) => $isWithinCycle($document->created_at))->sortByDesc('created_at')->first(),
                        'measurements' => $documents->where('title', 'Task End Measurements')->filter(fn ($document) => $isWithinCycle($document->created_at))->sortByDesc('created_at')->first(),
                        'site_photo' => $documents->where('title', 'Task End Site Photo')->filter(fn ($document) => $isWithinCycle($document->created_at))->sortByDesc('created_at')->first(),
                    ];
                    $startHistory = $task->statusHistories
                        ->where('status', 'in_progress')
                        ->filter(fn ($history) => $isWithinCycle($history->created_at))
                        ->sortByDesc('created_at')
                        ->first();
                    $endHistory = $task->status === 'completed'
                        ? $latestCompletedHistory
                        : $task->statusHistories->where('status', 'completed')->filter(fn ($history) => $isWithinCycle($history->created_at))->sortByDesc('created_at')->first();
                    $historyDate = $endHistory?->created_at ?: $startHistory?->created_at;
                    $formatHistoryLocation = function ($history) {
                        if (!$history) {
                            return 'Not updated';
                        }

                        $lat = $history->location_latitude;
                        $lng = $history->location_longitude;
                        $address = $history->location_address;

                        if ($lat && $lng) {
                            $text = filled($address) ? htmlspecialchars($address) : ('Lat: ' . htmlspecialchars($lat) . ', Lng: ' . htmlspecialchars($lng));
                            $url = 'https://maps.google.com/?q=' . urlencode($lat) . ',' . urlencode($lng);
                            return '<a href="' . $url . '" target="_blank" class="text-decoration-none"><i class="fa-solid fa-map-location-dot me-1"></i>' . $text . '</a>';
                        }

                        if (filled($address)) {
                            return htmlspecialchars($address);
                        }

                        return 'Not updated';
                    };
                    $isImageDocument = function ($document) {
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'jfif', 'avif'];
                        $fileType = strtolower((string) $document?->file_type);
                        $pathExtension = strtolower(pathinfo((string) $document?->file_path, PATHINFO_EXTENSION));

                        return in_array($fileType, $allowedExtensions, true)
                            || in_array($pathExtension, $allowedExtensions, true);
                    };
                    $documentUrl = function ($document) {
                        return $document?->id ? route('documents.preview', $document) : null;
                    };
                @endphp

                <div class="detail-view-block">
                    <h2 class="detail-view-title mb-4">{{ $task->title ?? '-' }}</h2>

                    <div class="row g-0 detail-view-grid">
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-user-plus"></i>Created By:</span>
                            <span class="detail-view-value">{{ $createdBy }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-file-signature"></i>Invoice:</span>
                            <span class="detail-view-value">{{ $invoiceName }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-user"></i>Customer Name:</span>
                            <span class="detail-view-value">{{ $customer?->name ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-user-tie"></i>Staff Name:</span>
                            <span class="detail-view-value">{{ $staffName }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-triangle-exclamation"></i>Priority:</span>
                            <span class="detail-view-value"><span class="badge crm-status-pill rounded-pill task-detail-badge {{ $priorityClass }}">{{ ucfirst(strtolower($task->priority ?? '-')) }}</span></span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-align-left"></i>Description:</span>
                            <span class="detail-view-value">{{ $task->description ?: '-' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-calendar-day"></i>Due Date:</span>
                            <span class="detail-view-value">{{ $task->due_date?->format('d M Y') ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                            <span class="detail-view-value">
                                <span class="badge crm-status-pill rounded-pill task-detail-badge {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $task->status ?? '-')) }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
