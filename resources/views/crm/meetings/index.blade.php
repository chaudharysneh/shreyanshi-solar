@extends('layouts.app')

@section('page_title', 'Meetings')

@section('content')
@php($googleIntegrationEnabled = \App\Helpers\IntegrationHelper::isGoogleEnabled())
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="googleCalendarSection">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="fw-bold mb-0">Manage Meetings</h4>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @can('meetings.edit')
                    @if($googleIntegrationEnabled)
                    <button type="button" id="connectGoogleBtn" class="btn btn-outline-primary" onclick="GoogleCalendar.connect()">
                        <i class="bi bi-google me-1"></i>Connect Google Calendar
                    </button>
                    <button type="button" id="disconnectGoogleBtn" class="btn btn-outline-danger" onclick="GoogleCalendar.disconnect()" style="display: none;">
                        <i class="bi bi-x-circle me-1"></i>Disconnect
                    </button>
                    @endif
                    @endcan
                    @can('meetings.view')
                    <a href="{{ route('meetings.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('meetings.create')
                    <a href="{{ route('meetings.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Meeting
                    </a>
                    @endcan
                </div>
            </div>
            
            @include('crm.partials.module-filter-config', ['module' => 'meetings', 'searchId' => 'meetingsSearch', 'placeholder' => 'Search meetings...'])
        </div>

        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="meetingFilterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                            Created By Me
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                            Assigned To Me
                        </button>
                    </li>
                </ul>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center">Sr.No</th>
                            <th>Customer</th>
                            <th class="text-center d-none d-md-table-cell">Staff</th>
                            <th class="text-center d-none d-md-table-cell">Scheduled On</th>
                            <th class="text-center d-none d-md-table-cell">Meeting Type</th>
                            @if($googleIntegrationEnabled)
                            <th class="text-center d-none d-md-table-cell">Calendar</th>
                            @endif
                            <th class="text-center d-none d-md-table-cell">Status</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 220px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div id="meetingPaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <style>
        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
    </style>
@endpush

@push('scripts')
    <script>
    window.googleIntegrationEnabled = @json(\App\Helpers\IntegrationHelper::isGoogleEnabled());
    window.crmUserPermissions = {
        ...(window.crmUserPermissions || {}),
        meetings: {
            view: @json(auth()->user()?->hasMatrixPermission('view_meetings')),
            create: @json(auth()->user()?->hasMatrixPermission('create_meetings')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_meetings')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_meetings')),
        }
    };
    </script>
    <script src="{{ crm_asset('js/meeting.js') }}"></script>
@endpush
