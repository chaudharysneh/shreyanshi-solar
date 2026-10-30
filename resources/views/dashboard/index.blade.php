@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div class="container-fluid p-0 dashboard-page">
        @php
            $formatDashboardAmount = static function ($value) {
                [$whole, $decimal] = explode('.', number_format((float) $value, 2, '.', ''));
                $whole = preg_replace('/\B(?=(\d{2})*\d{3}(?!\d))/', ',', $whole);
                return $whole . '.' . $decimal;
            };
            $dashboardUser = auth()->user();
            $planName = $currentSubscriptionPlan?->name ?? 'No Plan Assigned';
            $isPremiumPlan = str_contains(strtolower($planName), 'premium');
            $planStaffLimit = (int) ($currentSubscriptionPlan?->staff_limit ?? 0);
            $planRenewalDate =
                optional(
                    $currentSubscriptionAssignment?->updated_at ?? $currentSubscriptionAssignment?->created_at,
                )->format('d M Y') ?? '-';
                
            $hasStartDate = isset($currentSubscriptionAssignment->start_date);
            $hasEndDate = isset($currentSubscriptionAssignment->end_date);
            $hasAutoRenew = isset($currentSubscriptionAssignment->auto_renew);
            
            $planStartDate = ($hasStartDate && $currentSubscriptionAssignment->start_date) ? \Carbon\Carbon::parse($currentSubscriptionAssignment->start_date)->format('d M Y') : '-';
            $planEndDateRaw = ($hasEndDate && $currentSubscriptionAssignment->end_date) ? \Carbon\Carbon::parse($currentSubscriptionAssignment->end_date)->startOfDay() : null;
            $planEndDate = $planEndDateRaw ? $planEndDateRaw->format('d M Y') : '-';
            
            $autoRenew = ($hasAutoRenew && $currentSubscriptionAssignment->auto_renew) ? 'On' : 'Off';
            $planRenewalDate = $planEndDateRaw ? $planEndDateRaw->format('d M Y') : '-';
            
            $daysRemainingRaw = $planEndDateRaw ? (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($planEndDateRaw, false) : 0;
            $daysRemaining = max(0, $daysRemainingRaw);
            $isExpired = $planEndDateRaw && $daysRemainingRaw < 0;
            
            $hasPrice = isset($currentSubscriptionPlan->price);
            $hasBillingCycle = isset($currentSubscriptionPlan->billing_cycle);
            
            $planPrice = ($hasPrice && $currentSubscriptionPlan->price) ? '₹' . number_format($currentSubscriptionPlan->price, 0) : 'Free';
            $planBillingCycle = ($hasBillingCycle && $currentSubscriptionPlan->billing_cycle) ? ' / ' . $currentSubscriptionPlan->billing_cycle : '';
            $priceDisplay = $planPrice . $planBillingCycle;
            
            $statusText = $isExpired ? 'Expired' : ($planEndDateRaw ? 'Active' : 'Inactive');
            $statusColor = $isExpired ? 'text-danger' : ($planEndDateRaw ? 'text-success' : 'text-muted');
            $canViewCustomers =
                $canViewCustomers ?? \App\Models\Customer::query()->visibleToUser($dashboardUser)->exists();
            $canViewFollowUps = $dashboardUser?->hasMatrixPermission('view_followups') ?? false;
            $canViewLeads = $dashboardUser?->hasMatrixPermission('view_leads') ?? false;
            $canViewDeals = $dashboardUser?->hasMatrixPermission('view_deals') ?? false;
            $canViewTasks = $dashboardUser?->hasMatrixPermission('view_tasks') ?? false;
            $canViewBookings = $dashboardUser?->hasMatrixPermission('view_bookings') ?? false;
            $canViewEstimates = $dashboardUser?->hasMatrixPermission('view_estimates') ?? false;
            $hasDashboardAccess =
                $dashboardUser?->isAdmin() ||
                $canViewCustomers ||
                $canViewFollowUps ||
                $canViewLeads ||
                $canViewDeals ||
                $canViewTasks ||
                $canViewBookings ||
                $canViewEstimates;
        @endphp

        @include('dashboard.partials.document-forms')
        @include('dashboard.partials.solar-overview')
        @include('dashboard.partials.daily-overview')

        {{-- <div class="lead-board-wrapper p-0" id="leadBoardWrapper">
            <button type="button" class="lead-board-arrow lead-board-arrow--left" id="leadBoardLeft" title="Scroll Left">
                <i class="fa-solid fa-angle-left fs-5"></i>
            </button>
            <div class="status-board px-0 pb-0" id="leadBoardContainer">
                <div class="card border-0 shadow-sm w-100">
                    <div class="card-body text-muted small">Loading lead board...</div>
                </div>
            </div>
            <button type="button" class="lead-board-arrow lead-board-arrow--right" id="leadBoardRight"
                title="Scroll Right">
                <i class="fa-solid fa-angle-right fs-5"></i>
            </button>
        </div> --}}


        <div class="row g-3 mt-0">

            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold">Estimate Overview</h5>
                    @if(($estimateStats['can_view'] ?? false))
                        <a href="{{ route('estimates.index') }}" class="badge bg-light text-dark px-3 py-2 fw-semibold small">View All</a>
                    @endif
                </div>
                    <div class="card-body estimate-overview-card">
                        @if (!($estimateStats['can_view'] ?? false))
                            <div class="text-center text-muted py-5">Estimate data is not available for your account.</div>
                        @else
                            <div class="estimate-overview-hero mb-3">
                                <div>
                                    <p class="estimate-overview-label mb-1">Total Estimate Value</p>
                                    <h3 class="estimate-overview-value mb-0">
                                        ₹{{ $formatDashboardAmount($estimateStats['total_value'] ?? 0) }}</h3>
                                </div>
                                <div class="estimate-overview-icon">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="estimate-mini-stat">
                                        <span>Total</span>
                                        <strong>{{ number_format($estimateStats['total'] ?? 0) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="estimate-mini-stat">
                                        <span>This Month</span>
                                        <strong>{{ number_format($estimateStats['this_month'] ?? 0) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="estimate-mini-stat estimate-mini-stat--pending">
                                        <span>Pending</span>
                                        <strong>{{ number_format($estimateStats['pending'] ?? 0) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="estimate-mini-stat estimate-mini-stat--approved">
                                        <span>Approved</span>
                                        <strong>{{ number_format($estimateStats['approved'] ?? 0) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="estimate-status-strip mb-3">
                                <div>
                                    <span>Rejected</span><strong>{{ number_format($estimateStats['rejected'] ?? 0) }}</strong>
                                </div>
                                <div>
                                    <span>Completed</span><strong>{{ number_format($estimateStats['completed'] ?? 0) }}</strong>
                                </div>
                            </div>

                            @if (($estimateStats['latest'] ?? collect())->isNotEmpty())
                                @php($latestEstimate = ($estimateStats['latest'] ?? collect())->first())
                                <a href="{{ route('estimates.show', $latestEstimate) }}"
                                    class="estimate-latest-item estimate-latest-item--compact text-decoration-none mb-3">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-dark text-truncate">
                                            {{ $latestEstimate->estimate_name ?: ($latestEstimate->estimate_no ?: 'Estimate') }}
                                        </div>
                                        <div class="text-muted small text-truncate">
                                            {{ $latestEstimate->customer?->name ?? 'No customer' }} ·
                                            {{ optional($latestEstimate->estimate_date)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="fw-bold text-dark">
                                            ₹{{ $formatDashboardAmount($latestEstimate->amount) }}</div>
                                        <span
                                            class="badge-status {{ strtolower((string) $latestEstimate->status) }}">{{ strtoupper((string) ($latestEstimate->status ?: 'pending')) }}</span>
                                    </div>
                                </a>
                            @endif

                            <div class="estimate-footnote">
                                <i class="bi bi-info-circle me-1"></i>
                                Overview is based on all visible estimates and their current statuses.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Lead Conversion Snapshot</h5>
                        <a href="{{ route('leads.index') }}" class="badge bg-light text-dark px-3 py-2 fw-semibold small">View All</a>
                    </div>
                    <div class="card-body conversion-snapshot-card">
                        @if (!($leadConversionSnapshot['can_view'] ?? false))
                            <div class="text-center text-muted py-5">Lead conversion data is not available for your
                                account.</div>
                        @else
                            <div class="conversion-rate-circle mx-auto mb-3"
                                style="--conversion-rate: {{ min(100, max(0, (int) ($leadConversionSnapshot['conversion_rate'] ?? 0))) }}%;">
                                <span>{{ $leadConversionSnapshot['conversion_rate'] ?? 0 }}%</span>
                                <small>Conversion</small>
                            </div>

                            <div class="conversion-grid">
                                <div class="conversion-stat">
                                    <span>New Leads</span>
                                    <strong>{{ number_format($leadConversionSnapshot['new_leads'] ?? 0) }}</strong>
                                </div>
                                <div class="conversion-stat">
                                    <span>Qualified</span>
                                    <strong>{{ number_format($leadConversionSnapshot['qualified_leads'] ?? 0) }}</strong>
                                </div>
                                <div class="conversion-stat">
                                    <span>Estimates Created</span>
                                    <strong>{{ number_format($leadConversionSnapshot['estimates_created'] ?? 0) }}</strong>
                                </div>
                                <div class="conversion-stat conversion-stat--approved">
                                    <span>Approved Estimates</span>
                                    <strong>{{ number_format($leadConversionSnapshot['approved_estimates'] ?? 0) }}</strong>
                                </div>
                            </div>

                            <div class="conversion-footnote mt-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Conversion is calculated from approved estimates against total leads.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-7">
                @include('dashboard.partials.customer-pipeline')
            </div>

            <div class="col-12 col-xl-5">
                @include('dashboard.partials.todays-actions')
            </div>
        </div>

        <div class="row g-3 mt-1">

            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-white">Daily Activity</h5>
                        <select id="dailyActivityDays" aria-label="Daily activity range" class="form-select form-select-sm dashboard-year-select" style="color:#f1f5f9 !important;">
                            <option value="7">Last 7 days</option><option value="14">Last 14 days</option><option value="30" selected>Last 30 days</option>
                        </select>
                    </div>
                    <div class="card-body pt-2">
                        <p class="small text-muted mb-2">Leads by creation date; follow-ups and meetings by scheduled date.</p>
                        @if(empty($dailyActivity['datasets']))<p class="small text-muted">No activity modules available with your permissions.</p>@endif
                        <div class="chart-wrap">
                            <canvas id="dailyActivityChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-white">All Deals</h5>
                        <a href="{{ route('deals.index') }}" class="badge bg-light text-dark px-3 py-2 fw-semibold small">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="dashboardDealsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Deals Name</th>
                                        <th style="width: 25%;" class="d-none d-md-table-cell">Deal Value</th>
                                        <th style="width: 25%;" class="text-center d-none d-md-table-cell">Status</th>
                                        <th style="width: 10%;" class="text-center d-md-none">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Loading deals...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        @include('layouts.subscription-plan-modal')

        {{-- Expiry Warning / Expired Modal (Shows if 0 <= daysRemaining <= 30 OR already expired) --}}
        @if($currentSubscriptionPlan && isset($daysRemaining) && ($daysRemaining <= 30 || $isExpired))
            <div class="modal fade" id="expiryWarningModal" tabindex="-1" aria-labelledby="expiryWarningModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-bottom-0 pb-3 pt-3 px-4" style="background-color: #122137;">
                            <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0 text-white" id="expiryWarningModalLabel" style="font-size: 1.15rem;">
                                <i class="fa-solid {{ $isExpired ? 'fa-circle-xmark' : 'fa-triangle-exclamation' }} text-white"></i>
                                <span>{{ $isExpired ? 'Subscription Expired' : 'Subscription Expiring Soon' }}</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body p-4">
                            <p class="text-center text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                                @if($isExpired)
                                    Your subscription plan expired on <strong class="text-dark">{{ $planEndDate }}</strong>. Please renew it immediately to restore<br> full access to your service.
                                @else
                                    Your subscription plan will expire in <strong class="text-dark">{{ $daysRemaining }} days</strong>. Please renew it to avoid<br> interruption in service.
                                @endif
                            </p>

                            <div class="p-4 mx-auto mb-4 position-relative" style="background-color: #f8fafc; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); max-width: 90%;">
                                <!-- Left Border accent -->
                                <div class="position-absolute top-0 bottom-0 start-0" style="width: 6px; background-color: #34d399; border-top-left-radius: 12px; border-bottom-left-radius: 12px;"></div>
                                
                                <div class="d-flex justify-content-between mb-3 ms-2">
                                    <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Plan:</span>
                                    <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $planName ?? 'Basic Plan' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 ms-2">
                                    <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Expires On:</span>
                                    <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $planEndDate ?? '29 Jul 2026' }}</span>
                                </div>
                                <div class="d-flex justify-content-between ms-2">
                                    <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Days Remaining:</span>
                                    <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $isExpired ? '0 days' : ($daysRemaining . ' days') }}</span>
                                </div>
                            </div>

                            <div class="mx-auto" style="max-width: 90%;">
                                <h6 class="fw-bold mb-3" style="color: #f58220; font-size: 1.05rem;">Contact us to Renew:</h6>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                <a href="tel:+919824734531" class="d-inline-flex align-items-center gap-2 rounded px-3 py-2 text-decoration-none fw-medium" style="background-color:#f1f1f1;color:#475569;font-size:0.95rem;">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                    <span>+91 98247 34531</span>
                                </a>
                                <a href="mailto:info@fableadtechnolabs.com" class="d-inline-flex align-items-center gap-2 rounded px-3 py-2 text-decoration-none fw-medium" style="background-color:#f1f1f1;color:#475569;font-size:0.95rem;overflow-wrap:anywhere;">
                                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                    <span>info@fableadtechnolabs.com</span>
                                </a>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var expiryWarningModal = new bootstrap.Modal(document.getElementById('expiryWarningModal'));
                    expiryWarningModal.show();
                });
            </script>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ crm_asset('css/dashboard.css') }}">
@endpush

@push('scripts')
    <script>window.dashboardDailyActivity = @json($dailyActivity);</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script
        src="{{ crm_asset('js/dashboard.js') }}">
    </script>
@endpush
