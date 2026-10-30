<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Stage;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (request()->hasAny(['solar_period', 'solar_from', 'solar_to'])) {
            $filters = app(\App\Services\SolarDashboardService::class)->validateFilters(request());
            request()->session()->put('dashboard.solar_filters', $filters);
            return redirect(url('/dashboard'));
        }
        request()->merge(request()->session()->get('dashboard.solar_filters', []));
        $stages = $this->buildLeadBoard();
        $user = auth()->user();
        $canViewCustomers = Customer::query()->visibleToUser($user)->exists();
        $planOwner = $this->resolvePlanOwner($user);
        $subscriptionAssignment = $planOwner
            ? DB::table('subscription_user_plan')
                ->where('user_id', $planOwner->id)
                ->orderByDesc('id')
                ->first()
            : null;
        $currentSubscriptionPlan = $subscriptionAssignment
            ? SubscriptionPlan::find($subscriptionAssignment->subscription_id)
            : null;
        $currentStaffCount = $planOwner
            ? User::query()->nonAdmin()->where('parent_id', $planOwner->id)->count()
            : 0;

        return view('dashboard.index', [
            'bankReceivables' => app(\App\Services\DailyOverviewService::class)->bankReceivables($user),
            'showDailyOverview' => request()->session()->pull('dashboard.show_daily_overview', false),
            'solar' => app(\App\Services\SolarDashboardService::class)->build(request()),
            'dailyActivity' => app(\App\Services\SolarDashboardService::class)->dailyActivity(request()),
            'customerPipeline' => $this->buildCustomerPipeline(),
            'stats' => $this->buildStats(),
            'estimateStats' => $this->buildEstimateStats(),
            'leadConversionSnapshot' => $this->buildLeadConversionSnapshot(),
            'stages' => $stages,
            'canViewCustomers' => $canViewCustomers,
            'currentSubscriptionPlan' => $currentSubscriptionPlan,
            'currentSubscriptionAssignment' => $subscriptionAssignment,
            'currentStaffCount' => $currentStaffCount,
        ]);
    }

    private function buildCustomerPipeline(): array
    {
        if (!auth()->user()->hasMatrixPermission('view_customers')) return ['stages' => collect(), 'unassigned' => 0, 'allowed' => false];
        $counts = Customer::visibleToUser(auth()->user())->selectRaw('pipeline_stage_id, COUNT(*) as total')->groupBy('pipeline_stage_id')->pluck('total', 'pipeline_stage_id');
        $stages = Stage::orderBy('sort_order')->orderBy('id')->get()->map(fn ($stage) => [
            'id' => $stage->id, 'name' => $stage->name, 'count' => (int) ($counts[$stage->id] ?? 0),
        ]);
        return ['stages' => $stages, 'unassigned' => (int) ($counts[''] ?? 0), 'allowed' => true];
    }

    public function saveFilters(Request $request): \Illuminate\Http\RedirectResponse
    {
        $filters = app(\App\Services\SolarDashboardService::class)->validateFilters($request);
        $request->session()->put('dashboard.solar_filters', $filters);
        return redirect(url('/dashboard'));
    }

    public function apiStats(): JsonResponse
    {
        return response()->json($this->buildStats());
    }

    public function apiLeadBoard(): JsonResponse
    {
        if (!auth()->user()?->hasMatrixPermission('view_leads')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildLeadBoard()->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'count' => $stage->leads_count,
                    'leads' => collect($stage->leads)->map(function (Lead $lead) {
                        return [
                            'id' => $lead->id,
                            'name' => $lead->name,
                            'email' => $lead->email,
                            'phone' => $lead->phone,
                            'assigned_to' => $lead->assignedUser?->name,
                            'created_at' => optional($lead->created_at)->toIso8601String(),
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function apiTasksWidget(): JsonResponse
    {
        if (!auth()->user()?->hasMatrixPermission('view_tasks')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $tasks = Task::query()
            ->with([
                'assignedUser:id,name',
                'customer:id,name',
                'project:id,customer_id',
                'project.customer:id,name',
            ])
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'priority', 'status', 'assigned_user_id', 'related_type', 'related_id', 'project_id', 'due_date']);

        return response()->json([
            'success' => true,
            'data' => $tasks->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'assigned_to' => $task->assignedUser?->name,
                    'customer' => $task->customer?->name ?? $task->project?->customer?->name,
                    'due_date' => optional($task->due_date)->format('d M Y'),
                ];
            })->values(),
        ]);
    }

    public function apiTrend(): JsonResponse
    {
        $months = collect(range(5, 0))->map(function (int $offset) {
            $dt = Carbon::now()->subMonths($offset)->startOfMonth();

            return [
                'label' => $dt->format('M Y'),
                'start' => $dt->copy()->startOfMonth(),
                'end' => $dt->copy()->endOfMonth(),
            ];
        })->push([
                    'label' => Carbon::now()->format('M Y'),
                    'start' => Carbon::now()->copy()->startOfMonth(),
                    'end' => Carbon::now()->copy()->endOfMonth(),
                ])->values();

        $user = auth()->user();
        $canLeads = $user?->hasMatrixPermission('view_leads') ?? false;
        $canFollowups = $user?->hasMatrixPermission('view_followups') ?? false;
        $customersQuery = Customer::query()->visibleToUser($user);
        $canCustomers = $customersQuery->exists();
        $canDeals = $user?->hasMatrixPermission('view_deals') ?? false;

        $emptySeries = $months->map(fn() => 0)->values();

        $countByRange = function (string $modelClass) use ($months) {
            return $months->map(function (array $month) use ($modelClass) {
                return $modelClass::query()
                    ->whereBetween('created_at', [$month['start'], $month['end']])
                    ->count();
            })->values();
        };
        $countCustomersByRange = function () use ($months, $user) {
            return $months->map(function (array $month) use ($user) {
                return Customer::query()
                    ->visibleToUser($user)
                    ->whereBetween('created_at', [$month['start'], $month['end']])
                    ->count();
            })->values();
        };

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $months->pluck('label')->values(),
                'datasets' => [
                    'leads' => $canLeads ? $countByRange(Lead::class) : $emptySeries,
                    'followups' => $canFollowups ? $countByRange(FollowUp::class) : $emptySeries,
                    'customers' => $canCustomers ? $countCustomersByRange() : $emptySeries,
                    'deals' => $canDeals ? $countByRange(Deal::class) : $emptySeries,
                ],
            ],
        ]);
    }

    public function apiCustomerReport(Request $request): JsonResponse
    {
        if (!Customer::query()->visibleToUser(auth()->user())->exists()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'year' => (int) ($request->get('year') ?: now()->year),
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'series' => collect(range(1, 12))->map(fn() => 0)->values(),
                ],
            ]);
        }

        $year = (int) ($request->get('year') ?: now()->year);
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $series = collect(range(1, 12))->map(function (int $month) use ($year) {
            return Customer::query()
                ->visibleToUser(auth()->user())
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'labels' => $labels,
                'series' => $series,
            ],
        ]);
    }

    public function apiDealsWidget(): JsonResponse
    {
        if (!auth()->user()?->hasMatrixPermission('view_deals')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $deals = Deal::query()
            ->with([
                'status:id,name,color',
                'customer:id,name',
                'assignedUser:id,name',
            ])
            ->latest()
            ->take(4)
            ->get(['id', 'title', 'amount', 'probability', 'status_id', 'customer_id', 'assigned_user_id', 'expected_close_date']);

        return response()->json([
            'success' => true,
            'data' => $deals->map(function (Deal $deal) {
                return [
                    'id' => $deal->id,
                    'name' => $deal->title,
                    'amount' => (float) $deal->amount,
                    'probability' => $deal->probability,
                    'status' => $deal->status?->name,
                    'status_color' => $deal->status?->color,
                    'customer' => $deal->customer?->name,
                    'assigned_to' => $deal->assignedUser?->name,
                    'expected_close_date' => optional($deal->expected_close_date)->format('d M Y'),
                ];
            })->values(),
        ]);
    }

    private function buildStats(): array
    {
        $user = auth()->user();
        $customersQuery = Customer::query()->visibleToUser($user);
        $canFollowups = $user?->hasMatrixPermission('view_followups') ?? false;
        $canLeads = $user?->hasMatrixPermission('view_leads') ?? false;
        $canDeals = $user?->hasMatrixPermission('view_deals') ?? false;
        $canBookings = $user?->hasMatrixPermission('view_bookings') ?? false;

        $customers = (clone $customersQuery)->count();
        $followUps = $canFollowups ? FollowUp::query()->count() : 0;
        $pendingFollowUps = $canFollowups ? FollowUp::query()->where('status', 'pending')->count() : 0;
        $completedFollowUpsToday = $canFollowups
            ? FollowUp::query()->where('status', 'completed')->whereDate('updated_at', today())->count()
            : 0;
        $leads = $canLeads ? Lead::query()->count() : 0;
        $activeLeads = $canLeads ? Lead::query()->whereNotIn('status', ['won', 'lost'])->count() : 0;
        $deals = $canDeals ? Deal::count() : 0;
        $confirmedBookings = $canBookings ? Booking::where('status', 'confirmed')->count() : 0;
        $totalLeads = $leads;

        return [
            'customers' => $customers,
            'follow_ups' => $followUps,
            'leads' => $leads,
            'deals' => $deals,
            'new_customers_today' => (clone $customersQuery)->whereDate('created_at', today())->count(),
            'new_leads_today' => $canLeads ? Lead::query()->whereDate('created_at', today())->count() : 0,
            'pending_followups' => $pendingFollowUps,
            'completed_followups_today' => $completedFollowUpsToday,
            'active_leads' => $activeLeads,
            'confirmed_bookings' => $confirmedBookings,
            'conversion_rate' => $totalLeads > 0 ? round(($confirmedBookings / $totalLeads) * 100) : 0,
        ];
    }

    private function buildEstimateStats(): array
    {
        $user = auth()->user();
        $canEstimates = $user?->hasMatrixPermission('view_estimates') ?? false;

        if (!$canEstimates) {
            return [
                'can_view' => false,
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'completed' => 0,
                'this_month' => 0,
                'total_value' => 0,
                'latest' => collect(),
            ];
        }

        $baseQuery = Estimate::query();
        if (!$user?->isAdmin()) {
            $baseQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $statusCount = function (string $status) use ($baseQuery): int {
            return (clone $baseQuery)->where('status', $status)->count();
        };

        return [
            'can_view' => true,
            'total' => (clone $baseQuery)->count(),
            'pending' => $statusCount('pending'),
            'approved' => $statusCount('approved'),
            'rejected' => $statusCount('rejected'),
            'completed' => $statusCount('completed'),
            'this_month' => (clone $baseQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'total_value' => (float) (clone $baseQuery)->sum('amount'),
            'latest' => (clone $baseQuery)
                ->with('customer:id,name')
                ->latest('estimate_id')
                ->take(4)
                ->get(['estimate_id', 'estimate_no', 'estimate_name', 'customer_id', 'status', 'amount', 'estimate_date']),
        ];
    }

    private function buildUpcomingFollowUps()
    {
        if (!auth()->user()?->hasMatrixPermission('view_followups')) {
            return collect();
        }

        return FollowUp::query()
            ->with([
                'lead:id,name,phone',
                'assignedUser:id,name',
            ])
            ->whereDate('follow_up_at', '>=', today())
            ->whereNotIn('status', ['completed', 'cancelled', 'canceled'])
            ->orderBy('follow_up_at')
            ->take(5)
            ->get(['id', 'lead_id', 'assigned_user_id', 'purpose', 'status', 'follow_up_at']);
    }

    private function buildLeadConversionSnapshot(): array
    {
        $user = auth()->user();
        $canLeads = $user?->hasMatrixPermission('view_leads') ?? false;
        $canEstimates = $user?->hasMatrixPermission('view_estimates') ?? false;

        $leadQuery = Lead::query();
        $estimateQuery = Estimate::query();

        if (!$user?->isAdmin()) {
            $estimateQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $totalLeads = $canLeads ? (clone $leadQuery)->count() : 0;
        $newLeads = $canLeads ? (clone $leadQuery)->where('status', 'new')->count() : 0;
        $qualifiedLeads = $canLeads
            ? (clone $leadQuery)
                ->where(function ($query) {
                    $query->where('status', 'qualified')
                        ->orWhereHas('stage', function ($stageQuery) {
                            $stageQuery->whereRaw('LOWER(name) = ?', ['qualified']);
                        });
                })
                ->count()
            : 0;
        $estimatesCreated = $canEstimates ? (clone $estimateQuery)->count() : 0;
        $approvedEstimates = $canEstimates ? (clone $estimateQuery)->where('status', 'approved')->count() : 0;

        return [
            'can_view' => $canLeads || $canEstimates,
            'total_leads' => $totalLeads,
            'new_leads' => $newLeads,
            'qualified_leads' => $qualifiedLeads,
            'estimates_created' => $estimatesCreated,
            'approved_estimates' => $approvedEstimates,
            'conversion_rate' => $totalLeads > 0 ? round(($approvedEstimates / $totalLeads) * 100) : 0,
        ];
    }

    private function buildLeadBoard()
    {
        if (!auth()->user()?->hasMatrixPermission('view_leads')) {
            return collect();
        }

        $statusLabels = [
            'new' => 'New',
            'qualified' => 'Qualified',
            'working' => 'Working',
            'ready_to_close' => 'Ready to Close',
        ];

        return collect($statusLabels)->map(function ($label, $key) {
            $query = Lead::query()->with('assignedUser:id,name')->where('status', $key);

            $leads = (clone $query)
                ->latest()
                ->limit(1)
                ->get(['id', 'name', 'email', 'phone', 'assigned_user_id', 'created_at', 'status']);

            return (object) [
                'id' => $key,
                'name' => $label,
                'color' => null,
                'leads_count' => $query->count(),
                'leads' => $leads,
            ];
        })->values();
    }

    private function resolvePlanOwner(?User $user): ?User
    {
        if (!$user) {
            return null;
        }

        if ($user->isAdmin()) {
            return $user;
        }

        if (DB::getSchemaBuilder()->hasColumn('users', 'parent_id') && !empty($user->parent_id)) {
            return User::find($user->parent_id) ?: $user;
        }

        return $user;
    }
}
