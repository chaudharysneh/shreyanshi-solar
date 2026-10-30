<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class DailyOverviewService
{
    public function bankReceivables(User $user): \Illuminate\Support\Collection
    {
        if (!$user->hasMatrixPermission('view_invoices') || !Schema::hasTable('invoice_finance_plans')) {
            return collect();
        }

        $rows = collect();
        $invoices = Invoice::with(['financePlan', 'customer', 'currency', 'payments'])
            ->whereNotIn('status', ['cancelled', 'void', 'draft'])
            ->where(function ($query) { $query->where('is_quotation', false)->orWhereNull('is_quotation'); })
            ->whereHas('financePlan', fn ($query) => $query->where('funding_type', 'bank'))
            ->get();

        foreach ($invoices as $invoice) {
            if (Gate::getPolicyFor($invoice) && !$user->can('view', $invoice)) continue;
            $summary = app(InvoiceFinanceService::class)->summary($invoice);
            if ($summary['bank_pending'] <= 0) continue;
            $plan = $invoice->financePlan;
            $rows->push([
                'bank' => trim($plan->bank_name ?: 'Unspecified bank'),
                'branch' => $plan->branch_name,
                'currency' => 'INR',
                'pending' => $summary['bank_pending'],
                'date' => $plan->expected_bank_date,
                'overdue' => $plan->expected_bank_date?->lt(today()) ?? false,
                'customer' => $invoice->customer?->name ?? 'Customer',
                'invoice' => $invoice->invoice_no ?: $invoice->invoice_name,
                'url' => route('invoices.show', $invoice),
            ]);
        }

        return $rows->groupBy(fn ($row) => json_encode([$row['bank'], $row['currency']]))
            ->map(fn ($items) => [
                'bank' => $items->first()['bank'], 'currency' => $items->first()['currency'],
                'pending' => $items->sum('pending'),
                'overdue' => $items->where('overdue', true)->sum('pending'),
                'items' => $items->sortBy(fn ($row) => $row['date']?->timestamp ?? PHP_INT_MAX)->values(),
            ])->sortByDesc('pending')->values();
    }
}
