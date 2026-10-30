@extends('layouts.app')

@section('page_title', 'User Logs')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/user-logs.css') }}">
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">User Logs</h4>
                        <p class="text-muted small mb-0">Track user activity across CRM modules.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-dark-blue" id="userLogsRefreshBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-dark-blue" id="userLogsDeleteAllBtn">
                            <i class="bi bi-trash3 me-1"></i>Delete All
                        </button>
                    </div>
                </div>

                <div id="userLogsFilterForm" class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="per_page" class="mb-0">Show</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>entries</span>
                    </div>

                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text bg-light border-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="userLogsSearch" name="q" value="{{ $search }}"
                            class="form-control bg-light border-0" placeholder="Search logs..." autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="userLogsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Actioned By</th>
                                <th>Module</th>
                                <th class="d-none d-md-table-cell">Taken Action</th>
                                <th class="d-none d-md-table-cell">Message</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="text-center d-none d-md-table-cell">Action</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">Loading user logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="userLogsPagination" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>

    <div class="modal fade user-log-modal" id="userLogDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header user-log-modal-header border-0">
                    <div class="user-log-modal-heading">
                        <div class="user-log-modal-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="user-log-module-pill" id="userLogDetailModule">Activity</span>
                                <span class="badge rounded-pill user-log-action-pill" id="userLogDetailAction">UPDATE</span>
                            </div>
                            <h5 class="modal-title fw-bold mb-1" id="userLogDetailTitle">Activity details</h5>
                            <p class="user-log-modal-meta mb-0" id="userLogDetailMeta">--</p>
                        </div>
                    </div>
                    <button type="button" class="user-log-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body user-log-modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-lg-7">
                            <div class="user-log-detail-card user-log-detail-card--message h-100">
                                <div class="user-log-detail-label">
                                    <i class="bi bi-chat-left-text"></i>
                                    <span>Message</span>
                                </div>
                                <div class="user-log-detail-message" id="userLogDetailMessage">--</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="user-log-detail-card user-log-detail-card--summary h-100">
                                <div class="user-log-detail-label">
                                    <i class="bi bi-card-checklist"></i>
                                    <span>Summary</span>
                                </div>
                                <div class="user-log-detail-summary" id="userLogDetailSummary">--</div>
                            </div>
                        </div>
                    </div>

                    <div class="user-log-detail-section-head">
                        <span>Tracked Changes</span>
                    </div>
                    <div id="userLogDetailGroups" class="row g-3"></div>
                    <div id="userLogDetailEmpty" class="user-log-empty-state d-none">
                        <i class="bi bi-info-circle"></i>
                        <span>Detailed field tracking is not available for this older log entry.</span>
                    </div>
                </div>
                <div class="modal-footer user-log-modal-footer border-0">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.userLogsConfig = {
            indexUrl: @json(route('api.user-logs.index')),
            showBaseUrl: @json(url('/api/user-logs')),
            destroyBaseUrl: @json(url('/api/user-logs')),
            destroyAllUrl: @json(route('api.user-logs.destroy_all')),
        };
    </script>
    <script src="{{ crm_asset('js/user-logs.js') }}"></script>
@endpush