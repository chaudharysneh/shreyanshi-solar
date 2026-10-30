@php
    $planName = $currentSubscriptionPlan?->name ?? 'No Plan Assigned';
    $planStaffLimit = (int) ($currentSubscriptionPlan?->staff_limit ?? 0);
    $planStartDate = !empty($currentSubscriptionAssignment->start_date)
        ? \Carbon\Carbon::parse($currentSubscriptionAssignment->start_date)->format('d M Y') : '-';
    $planEndDateRaw = !empty($currentSubscriptionAssignment->end_date)
        ? \Carbon\Carbon::parse($currentSubscriptionAssignment->end_date)->startOfDay() : null;
    $planEndDate = $planEndDateRaw ? $planEndDateRaw->format('d M Y') : '-';
    $daysRemainingRaw = $planEndDateRaw ? (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($planEndDateRaw, false) : 0;
    $daysRemaining = max(0, $daysRemainingRaw);
    $statusText = $planEndDateRaw && $daysRemainingRaw < 0 ? 'Expired' : ($planEndDateRaw ? 'Active' : 'Inactive');
@endphp
        <div class="modal fade dashboard-plan-modal" id="dashboardPlanModal" tabindex="-1"
            aria-labelledby="dashboardPlanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 pb-3 pt-3 px-4" style="background-color: #122137;">
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0 text-white" id="dashboardPlanModalLabel" style="font-size: 1.15rem;">
                            <i class="fa-solid fa-crown text-white"></i>
                            <span id="dashboardPlanModalTitle">Your Subscription Plan</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-start mb-4 p-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <i class="fa-solid fa-circle-info mt-1 me-2" style="color: #64748b;"></i>
                            <span style="color: #475569; font-size: 0.9rem;">
                                @if ($currentSubscriptionPlan)
                                    Staff accounts are counted under your admin ID. When the plan limit is reached, new staff creation will be blocked automatically.
                                @else
                                    No subscription plan is assigned to this admin account yet.
                                @endif
                            </span>
                        </div>

                        <div class="p-4 mb-4 position-relative" style="background-color: #f8fafc; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <!-- Left Border accent -->
                            <div class="position-absolute top-0 bottom-0 start-0" style="width: 6px; background-color: #34d399; border-top-left-radius: 12px; border-bottom-left-radius: 12px;"></div>
                            
                            <div class="row gy-3 ms-2">
                                <div class="col-md-6 pe-md-4">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Plan:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $planName }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Staff Limit:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $currentStaffCount ?? 0 }} / {{ $planStaffLimit }} users</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Status:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $statusText }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 ps-md-4">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Start Date:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $planStartDate }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">End Date:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $planEndDate }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary fw-bold" style="font-size: 0.95rem;">Days Left:</span>
                                        <span class="fw-bold" style="color: #ef4444; font-size: 0.95rem;">{{ $daysRemaining }} days</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
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

