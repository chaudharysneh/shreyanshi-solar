<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\SupplierPayable;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Payment;
use App\Models\SupplierPayment;
use App\Models\Task;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function applyReportFilters($query, Request $request, string $module): void
    {
        [$exact, $dates] = match ($module) {
            'customers' => [['is_active', 'type'], ['created_at']],
            'leads' => [['status', 'lead_source_id', 'lead_stage_id', 'created_by', 'assigned_user_id'], ['created_at']],
            'deals' => [['customer_id', 'created_by', 'status_id'], []],
            'tasks' => [['assigned_user_id', 'task_type', 'status'], ['due_date']],
            'followups' => [['assigned_user_id', 'status'], ['created_at', 'follow_up_at']],
        };
        \App\Support\ListFilters::apply($query, $request, $exact, $dates);

        if ($module === 'deals') {
            $request->validate(['amount_min' => ['nullable', 'numeric', 'min:0'], 'amount_max' => ['nullable', 'numeric', 'min:0']]);
            if ($request->filled('amount_min')) $query->where('amount', '>=', $request->input('amount_min'));
            if ($request->filled('amount_max')) $query->where('amount', '<=', $request->input('amount_max'));
        }
        if ($module === 'tasks') {
            $request->validate(['customer_id' => ['nullable', 'integer', 'min:1']]);
            if ($request->filled('customer_id')) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('customer', fn ($customer) => $customer->whereKey($request->integer('customer_id')))
                        ->orWhere(function ($fallback) use ($request) {
                            $fallback->whereDoesntHave('customer')->whereHas('project', fn ($project) => $project->where('customer_id', $request->integer('customer_id')));
                        });
                });
            }
        }
    }

    public function index()
    {
        $stats = [
            'total_revenue' => Booking::sum('total_amount'),
            'total_costs' => SupplierPayable::sum('amount'),
            'total_leads' => Lead::count(),
            'converted_leads' => Lead::where('is_converted', true)->count(),
            'total_customers' => Customer::count(),
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::where('status', 'pending')->count(),
            'pending_receivables' => Booking::sum('total_amount') - Payment::sum('amount'),
            'pending_payables' => SupplierPayable::sum('amount') - SupplierPayment::sum('amount'),
        ];

        $stats['gross_profit'] = $stats['total_revenue'] - $stats['total_costs'];
        $stats['conversion_rate'] = $stats['total_leads'] > 0
            ? round(($stats['converted_leads'] / $stats['total_leads']) * 100, 1)
            : 0;

        // Lead Status Distribution
        $leadsByStatus = Lead::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Task Status Distribution
        $tasksByStatus = Task::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Customer Growth Data
        $customerGrowth = Customer::select(
            DB::raw('count(*) as total'),
            DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("MONTH(created_at) as month_num")
        )
            ->groupBy('month', 'year', 'month_num')
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();

        // Revenue Data for Charts
        $monthlyRevenue = Booking::select(
            DB::raw('SUM(total_amount) as revenue'),
            DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("MONTH(created_at) as month_num")
        )
            ->groupBy('month', 'year', 'month_num')
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();

        return view('crm.reports.index', compact('stats', 'leadsByStatus', 'tasksByStatus', 'customerGrowth', 'monthlyRevenue'));
    }

    public function profitAndLoss()
    {
        $bookings = Booking::with(['customer', 'payables'])
            ->latest()
            ->paginate(20);

        return view('crm.reports.profit_loss', compact('bookings'));
    }

    public function salesPerformance()
    {
        $agentPerformance = Agent::select('agents.*')
            ->addSelect([
                'bookings_count' => Booking::whereColumn('agent_id', 'agents.id')->selectRaw('count(*)'),
                'total_sales' => Booking::whereColumn('agent_id', 'agents.id')->selectRaw('sum(total_amount)')
            ])
            ->orderByDesc('total_sales')
            ->get();

        return view('crm.reports.sales', compact('agentPerformance'));
    }

    public function pendingAccounts()
    {
        $receivables = Booking::with('customer')
            ->get()
            ->filter(function ($booking) {
                return $booking->total_amount > $booking->invoices->flatMap->payments->sum('amount');
            });

        $payables = SupplierPayable::with(['supplier', 'booking'])
            ->where('status', '!=', 'paid')
            ->get();

        return view('crm.reports.pending', compact('receivables', 'payables'));
    }

    public function customersReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = Customer::with(['country', 'city'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $this->applyReportFilters($query, $request, 'customers');
            $customers = $query->paginate($request->integer('per_page', 10))->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Customers retrieved successfully.',
                'data' => $customers,
            ]);
        }

        $years = Customer::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        // Fetch chart data
        $chartQuery = Customer::select(
            DB::raw('count(*) as total'),
            DB::raw("MONTH(created_at) as month_num")
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery->groupBy('month_num')
            ->orderBy('month_num')
            ->get()
            ->pluck('total', 'month_num')
            ->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $chartDataRaw[$i] ?? 0;
        }
        
        return view('crm.reports.customers_report', compact('years', 'year', 'from_date', 'to_date', 'chartData'));
    }

    public function leadsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        // ── DataTable AJAX response ──────────────────────────────
        if ($request->ajax()) {
            $query = Lead::with(['leadSource', 'assignedUser', 'creator'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('leadSource', function ($leadSourceQuery) use ($search) {
                            $leadSourceQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($assignedUserQuery) use ($search) {
                            $assignedUserQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $this->applyReportFilters($query, $request, 'leads');
            $leads = $query->paginate($request->integer('per_page', 10))->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Leads retrieved successfully.',
                'data' => $leads,
            ]);
        }

        // ── Year dropdown ────────────────────────────────────────
        $years = Lead::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        // ── Chart data ───────────────────────────────────────────
        $chartQuery = Lead::select(
            DB::raw('count(*) as total'),
            DB::raw('YEAR(created_at)  as year_num'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        if ($from_date || $to_date) {
            // Date-range mode: dynamic YYYY-MM labels
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            // Full-year mode: Jan–Dec
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.leads_report', compact(
            'years',
            'year',
            'from_date',
            'to_date',
            'chartData',
            'chartLabels'
        ));
    }

    // Export method
    public function leadsExport()
    {
        $leads = $this->scopeOwnedRecords(
            Lead::with(['leadSource', 'assignedUser'])
        )->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Name', 'Email', 'Phone', 'Lead Source', 'Assigned To', 'Status', 'Is Converted', 'Created At']);

            foreach ($leads as $index => $lead) {
                fputcsv($handle, [
                    $index + 1,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->leadSource?->name ?? '-',
                    $lead->assignedUser?->name ?? 'Unassigned',
                    $lead->status,
                    $lead->is_converted ? 'Yes' : 'No',
                    $lead->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dealsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        // ── DataTable AJAX response ──────────────────────────────
        if ($request->ajax()) {
            $query = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhereHas('stage', function ($stageQuery) use ($search) {
                            $stageQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('status', function ($statusQuery) use ($search) {
                            $statusQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $this->applyReportFilters($query, $request, 'deals');
            $deals = $query->paginate($request->integer('per_page', 10))->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Deals retrieved successfully.',
                'data' => $deals,
            ]);
        }

        // ── Year dropdown ────────────────────────────────────────
        $years = Deal::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        // ── Chart data ───────────────────────────────────────────
        $chartQuery = Deal::select(
            DB::raw('count(*) as total'),
            DB::raw('YEAR(created_at)  as year_num'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        if ($from_date || $to_date) {
            // Date-range mode: dynamic YYYY-MM labels
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            // Full-year mode: Jan–Dec
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.deals_report', compact(
            'years',
            'year',
            'from_date',
            'to_date',
            'chartData',
            'chartLabels'
        ));
    }

    public function dealsExport()
    {
        $deals = $this->scopeOwnedRecords(
            Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])
        )->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="deals_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($deals) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Title', 'Customer', 'Amount', 'Currency', 'Probability', 'Stage', 'Status', 'Assigned To', 'Created By', 'Created At']);

            foreach ($deals as $index => $deal) {
                fputcsv($handle, [
                    $index + 1,
                    $deal->title,
                    $deal->customer?->name ?? '-',
                    $deal->amount,
                    $deal->currency?->code ?? '-',
                    $deal->probability . '%',
                    $deal->stage?->name ?? '-',
                    $deal->status?->name ?? '-',
                    $deal->assignedUser?->name ?? 'Unassigned',
                    $deal->creator?->name ?? '-',
                    $deal->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function projectsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = Project::with(['customer', 'assignedUser', 'creator'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $projects = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Projects retrieved successfully.',
                'data' => $projects,
            ]);
        }

        $years = Project::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $chartQuery = Project::select(
            DB::raw('count(*) as total'),
            DB::raw('YEAR(created_at)  as year_num'),
            DB::raw('MONTH(created_at) as month_num')
        );

        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery
            ->groupBy('year_num', 'month_num')
            ->orderBy('year_num')
            ->orderBy('month_num')
            ->get();

        if ($from_date || $to_date) {
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.projects_report', compact(
            'years',
            'year',
            'from_date',
            'to_date',
            'chartData',
            'chartLabels'
        ));
    }

    public function projectsExport()
    {
        $projects = $this->scopeOwnedRecords(
            Project::with(['customer', 'assignedUser', 'creator'])
        )->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="projects_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($projects) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Sr.No', 'Project Code', 'Name', 'Customer', 'Assigned To', 'Status', 'Start Date', 'End Date', 'Created By', 'Created At']);

            foreach ($projects as $index => $project) {
                fputcsv($handle, [
                    $index + 1,
                    $project->project_code,
                    $project->name,
                    $project->customer?->name ?? '-',
                    $project->assignedUser?->name ?? 'Unassigned',
                    $project->status ?? '-',
                    $project->start_date ? $project->start_date->format('Y-m-d') : '-',
                    $project->end_date ? $project->end_date->format('Y-m-d') : '-',
                    $project->creator?->name ?? '-',
                    $project->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function tasksReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = Task::with(['project.customer'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($projectQuery) use ($search) {
                            $projectQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $this->applyReportFilters($query, $request, 'tasks');
            $tasks = $query->paginate($request->integer('per_page', 10))->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully.',
                'data' => $tasks,
            ]);
        }

        $years = Task::selectRaw('YEAR(created_at) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $chartQuery = Task::select(DB::raw('count(*) as total'), DB::raw('YEAR(created_at)  as year_num'), DB::raw('MONTH(created_at) as month_num'));
        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery->groupBy('year_num', 'month_num')->orderBy('year_num')->orderBy('month_num')->get();

        if ($from_date || $to_date) {
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.tasks_report', compact('years', 'year', 'from_date', 'to_date', 'chartData', 'chartLabels'));
    }

    public function tasksExport()
    {
        $tasks = $this->scopeOwnedRecords(
            Task::with(['project.customer'])
        )->latest()->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"'];
        $callback = function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr.No', 'Customer Name', 'Project Name', 'Task Title', 'Priority', 'Status', 'Due Date']);
            foreach ($tasks as $index => $task) {
                fputcsv($handle, [
                    $index + 1,
                    $task->project?->customer?->name ?? '-',
                    $task->project?->name ?? '-',
                    $task->title ?? '-',
                    $task->priority ?? '-',
                    $task->status ?? '-',
                    $task->due_date ? $task->due_date->format('Y-m-d') : '-'
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function followupsReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');
        $search = trim((string) $request->get('search', ''));

        if ($request->ajax()) {
            $query = FollowUp::with(['lead', 'customer', 'assignedUser'])->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('purpose', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search) {
                            $leadQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($assignedUserQuery) use ($search) {
                            $assignedUserQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($from_date) {
                $query->whereDate('created_at', '>=', $from_date);
            }
            if ($to_date) {
                $query->whereDate('created_at', '<=', $to_date);
            }
            if ($year && !$from_date && !$to_date) {
                $query->whereYear('created_at', $year);
            }

            $this->applyReportFilters($query, $request, 'followups');
            $followups = $query->paginate($request->integer('per_page', 10))->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Followups retrieved successfully.',
                'data' => $followups,
            ]);
        }

        $years = FollowUp::selectRaw('YEAR(created_at) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        $chartQuery = FollowUp::select(DB::raw('count(*) as total'), DB::raw('YEAR(created_at)  as year_num'), DB::raw('MONTH(created_at) as month_num'));
        if ($from_date) {
            $chartQuery->whereDate('created_at', '>=', $from_date);
        }
        if ($to_date) {
            $chartQuery->whereDate('created_at', '<=', $to_date);
        }
        if ($year && !$from_date && !$to_date) {
            $chartQuery->whereYear('created_at', $year);
        }

        $chartDataRaw = $chartQuery->groupBy('year_num', 'month_num')->orderBy('year_num')->orderBy('month_num')->get();

        if ($from_date || $to_date) {
            $chartLabels = [];
            $chartData = [];
            foreach ($chartDataRaw as $row) {
                $chartLabels[] = sprintf('%d-%02d', $row->year_num, $row->month_num);
                $chartData[] = $row->total;
            }
        } else {
            $byMonth = $chartDataRaw->pluck('total', 'month_num')->toArray();
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $byMonth[$i] ?? 0;
            }
        }

        return view('crm.reports.followups_report', compact('years', 'year', 'from_date', 'to_date', 'chartData', 'chartLabels'));
    }

    public function followupsExport()
    {
        $followups = $this->scopeOwnedRecords(
            FollowUp::with(['lead', 'customer', 'assignedUser'])
        )->latest()->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="followups_export_' . date('Y-m-d') . '.csv"'];
        $callback = function () use ($followups) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr.No', 'Purpose', 'Entity Name', 'Entity Type', 'Follow Up Date', 'Priority', 'Assigned To', 'Status']);
            foreach ($followups as $index => $followup) {
                $entity = $followup->lead ?? $followup->customer;
                $entityType = $followup->lead ? 'Lead' : ($followup->customer ? 'Customer' : 'Unknown');
                fputcsv($handle, [
                    $index + 1,
                    $followup->purpose ?? '-',
                    $entity?->name ?? 'Unknown',
                    $entityType,
                    $followup->follow_up_at ? $followup->follow_up_at->format('Y-m-d H:i') : '-',
                    $followup->priority ?? '-',
                    $followup->assignedUser?->name ?? 'Unassigned',
                    $followup->status ?? '-'
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }
}
