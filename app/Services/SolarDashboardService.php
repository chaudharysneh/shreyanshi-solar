<?php

namespace App\Services;

use App\Models;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SolarDashboardService
{
    public function validateFilters(Request $request): array
    {
        return $request->validate([
            'solar_period' => ['nullable', 'in:today,month,custom'],
            'solar_from' => ['required_if:solar_period,custom', 'nullable', 'date_format:Y-m-d'],
            'solar_to' => ['required_if:solar_period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:solar_from'],
        ]);
    }

    public function dailyActivity(Request $request): array
    {
        $end = now()->endOfDay();
        $start = $end->copy()->subDays(29)->startOfDay();
        $dates = collect(range(0, 29))->map(fn ($day) => $start->copy()->addDays($day));
        $datasets = [];
        foreach ([
            ['leads', Models\Lead::class, 'created_at', 'Leads created', '#3b648f'],
            ['followups', Models\FollowUp::class, 'follow_up_at', 'Follow-ups scheduled', '#a27738'],
            ['meetings', Models\Meeting::class, 'scheduled_at', 'Meetings scheduled', '#218477'],
        ] as [$permission, $model, $column, $label, $color]) {
            if (!$request->user()->hasMatrixPermission('view_'.$permission)) continue;
            $counts = $model::query()->whereBetween($column, [$start, $end])
                ->selectRaw("DATE($column) as activity_day, COUNT(*) as total")
                ->groupByRaw("DATE($column)")->pluck('total', 'activity_day');
            $datasets[] = ['label' => $label, 'color' => $color, 'values' => $dates->map(fn ($date) => (int) ($counts[$date->format('Y-m-d')] ?? 0))->all()];
        }
        return ['labels' => $dates->map(fn ($date) => $date->format('d M'))->all(), 'datasets' => $datasets];
    }

    public function build(Request $request): array
    {
        $input = $this->validateFilters($request);
        $today = now()->startOfDay();
        $period = $input['solar_period'] ?? 'month';
        $from = match ($period) {
            'today' => $today->copy(),
            'custom' => Carbon::parse($input['solar_from'])->startOfDay(),
            default => $today->copy()->startOfMonth(),
        };
        $to = $period === 'custom' ? Carbon::parse($input['solar_to'])->endOfDay() : now()->endOfDay();
        $days = $from->diffInDays($to->copy()->startOfDay()) + 1;
        $previousFrom = $from->copy()->subDays($days);
        $previousTo = $from->copy()->subSecond();
        $user = $request->user();
        $can = fn ($module) => $user->hasMatrixPermission('view_'.$module);
        $allowed = function ($records) use ($user) {
            return $records->filter(fn ($record) => !Gate::getPolicyFor($record) || $user->can('view', $record))->values();
        };
        $leads = $can('leads') ? Models\Lead::whereBetween('created_at', [$from, $to])->get() : collect();
        $previousLeads = $can('leads') ? Models\Lead::whereBetween('created_at', [$previousFrom, $previousTo])->count() : 0;
        $change = $previousLeads ? round(($leads->count() - $previousLeads) / $previousLeads * 100, 1) : null;
        $deals = $can('deals') ? $allowed(Models\Deal::with(['status', 'currency', 'customer'])->get()) : collect();
        $openDeals = $deals->filter(function ($deal) {
            $status = strtolower(trim($deal->status?->name ?? ''));
            return !in_array($status, ['won', 'lost', 'closed won', 'closed lost', 'closed', 'cancelled']);
        });
        $followups = $can('followups') ? $allowed(Models\FollowUp::with('lead')->whereNotIn('status', ['completed', 'cancelled'])->get()) : collect();
        $overdueFollowups = $followups->filter(fn ($row) => $row->follow_up_at && $row->follow_up_at->lt($today));
        $invoices = $can('invoices') ? $allowed(Models\Invoice::with(['customer', 'currency'])->withSum('payments', 'amount')->whereNotIn('status', ['paid', 'cancelled', 'void', 'draft'])->where(function ($q) { $q->where('is_quotation', false)->orWhereNull('is_quotation'); })->get()) : collect();
        $outstanding = $invoices->map(function ($invoice) {
            $invoice->setAttribute('outstanding_balance', max(0, (float) $invoice->amount - (float) $invoice->payments_sum_amount));
            return $invoice;
        })->filter(fn ($invoice) => $invoice->outstanding_balance > 0);
        $balances = $outstanding->groupBy(fn ($row) => $row->currency?->code ?? 'Unspecified currency')->map(fn ($rows) => $rows->sum('outstanding_balance'));
        $overdueBalances = $outstanding->filter(fn ($row) => $row->due_date && $row->due_date->lt($today))->groupBy(fn ($row) => $row->currency?->code ?? 'Unspecified currency')->map(fn ($rows) => $rows->sum('outstanding_balance'));
        $actions = collect();
        foreach ($followups->filter(fn ($row) => $row->follow_up_at && $row->follow_up_at->lte(now()->endOfDay())) as $row) {
            $actions->push(['title' => $row->purpose ?: 'Follow-up', 'detail' => $row->lead?->name ?? 'Lead follow-up', 'date' => $row->follow_up_at, 'kind' => 'Follow-up', 'url' => route('followups.show', $row)]);
        }
        if ($can('tasks')) {
            foreach ($allowed(Models\Task::whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_date', '<=', $today)->get()) as $row) {
                $actions->push(['title' => $row->title, 'detail' => 'Task due', 'date' => $row->due_date, 'kind' => 'Task', 'url' => route('tasks.show', $row)]);
            }
        }
        if ($can('meetings')) {
            foreach ($allowed(Models\Meeting::with('customer')->whereDate('scheduled_at', $today)->whereNotIn('status', ['completed', 'cancelled'])->get()) as $row) {
                $actions->push(['title' => $row->title, 'detail' => $row->customer?->name ?? 'Meeting', 'date' => $row->scheduled_at, 'kind' => 'Meeting', 'url' => route('meetings.show', $row)]);
            }
        }
        $actionCount = $actions->count();
        $actions = $actions->sortBy('date')->values();
        return compact('period', 'from', 'to', 'change', 'previousLeads', 'leads', 'openDeals', 'followups', 'overdueFollowups', 'outstanding', 'balances', 'overdueBalances', 'actions', 'actionCount');
    }
}
