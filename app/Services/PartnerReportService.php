<?php

namespace App\Services;

use App\Models\ChannelPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartnerReportService
{
    public function build(ChannelPartner $partner): array
    {
        $currency = $partner->commission_currency ?: 'INR';
        $defaultCurrency = \App\Support\DocumentCurrency::defaultCode();
        $leads = $partner->leads()->get();
        $converted = $leads->where('is_converted', true);
        $customerIds = $converted->pluck('converted_customer_id')->filter()->unique();
        // A customer can have several converted leads. Attribute once, to the earliest
        // converted referral lead, so two partners cannot both earn on the same customer.
        $owners = DB::table('leads')->whereNull('deleted_at')->where('is_converted', true)
            ->whereNotNull('channel_partner_id')->whereIn('converted_customer_id', $customerIds)
            ->orderBy('created_at')->orderBy('id')->get()->unique('converted_customer_id');
        $attributedIds = $owners->where('channel_partner_id', $partner->id)->pluck('converted_customer_id');
        if (Schema::hasColumn('customers','channel_partner_id')) {
            $direct = DB::table('customers')->whereNull('deleted_at')->whereNotNull('channel_partner_id')
                ->where(fn ($q) => $q->whereIn('id',$customerIds)->orWhere('channel_partner_id',$partner->id))->pluck('channel_partner_id','id');
            $attributedIds = $attributedIds->filter(fn ($id) => !isset($direct[$id]) || (int) $direct[$id] === (int) $partner->id)
                ->merge($direct->filter(fn ($id) => (int) $id === (int) $partner->id)->keys())->unique()->values();
        }

        $rows = collect(); $excludedInvoices = 0;
        $currencies = Schema::hasTable('currencies') ? DB::table('currencies')->pluck('code','id') : collect();
        $customers = $attributedIds->isNotEmpty() && Schema::hasTable('customers')
            ? DB::table('customers')->whereIn('id',$attributedIds)->pluck('name','id') : collect();
        $estimates = $attributedIds->isNotEmpty() && Schema::hasTable('estimates')
            ? DB::table('estimates')->whereIn('customer_id',$attributedIds)->whereNull('deleted_at')->where(fn ($q) => $q->where('isDeleted',false)->orWhereNull('isDeleted'))->get() : collect();
        $invoices = $attributedIds->isNotEmpty() && Schema::hasTable('invoices')
            ? DB::table('invoices')->whereIn('customer_id',$attributedIds)->whereNull('deleted_at')->whereNotIn('status',['draft','cancelled','void'])
                ->where(fn ($q) => $q->where('is_quotation',false)->orWhereNull('is_quotation'))->get() : collect();
        $receipts = $invoices->isNotEmpty() && Schema::hasTable('payments')
            ? DB::table('payments')->when(Schema::hasColumn('payments','voided_at'),fn($q)=>$q->whereNull('voided_at'))->whereIn('invoice_id',$invoices->pluck('id'))->selectRaw('invoice_id, SUM(amount) as paid')->groupBy('invoice_id')->pluck('paid','invoice_id') : collect();
        $installations = $attributedIds->isNotEmpty() && Schema::hasTable('installations')
            ? DB::table('installations')->whereIn('customer_id',$attributedIds)->where('status','completed')->pluck('customer_id')->all() : [];
        foreach ($attributedIds as $customerId) {
            $customerEstimates = $estimates->where('customer_id',$customerId)->filter(fn ($row) => (strtoupper(trim((string) $row->currency)) ?: $defaultCurrency) === $currency);
            $approved = $customerEstimates->whereIn('status',['approved','completed']);
            $customerInvoices = $invoices->where('customer_id',$customerId)->filter(function ($row) use ($currencies,$currency,$defaultCurrency,$estimates,$customerId,&$excludedInvoices) {
                $linkedEstimate = $estimates->first(fn ($estimate) => $estimate->estimate_id == ($row->estimate_id ?? null) && $estimate->customer_id == $customerId);
                // Explicit invoice currency wins; legacy converted invoices inherit their
                // estimate currency, then the application's default (not the partner's currency).
                $code = $row->currency_id ? ($currencies[$row->currency_id] ?? '')
                    : (trim((string) ($linkedEstimate->currency ?? '')) ?: $defaultCurrency);
                $matches = strtoupper($code) === $currency;
                if (!$matches) $excludedInvoices++;
                return $matches;
            });
            $subtotal = $approved->sum(fn ($row) => $this->cents($row->total));
            $billed = $customerInvoices->sum(fn ($row) => $this->cents($row->amount));
            // The invoice status action records full settlement through status alone.
            // Receipts supply partial payments; never add receipts on top of a paid invoice.
            $received = $customerInvoices->sum(fn ($row) => $row->status === 'paid' ? max(0,$this->cents($row->amount)) : max(0,min($this->cents($row->amount),$this->cents($receipts[$row->id] ?? 0))));
            $eligible = match ($partner->payable_on) {
                'conversion' => true,
                'installation' => in_array($customerId,$installations),
                default => $received > 0,
            };
            $basis = $partner->commission_basis === 'amount_received' ? $received : $subtotal;
            $commission = !$eligible ? 0 : ($partner->commission_type === 'percent'
                ? (int) round($basis * (float) $partner->commission_value / 100)
                : $this->cents($partner->commission_value));
            $rows->push([
                'customer_id'=>$customerId,'customer_name'=>$customers[$customerId] ?? 'Customer #'.$customerId,
                'estimated'=>$customerEstimates->whereIn('status',['pending','approved','completed'])->sum(fn ($row) => $this->cents($row->total))/100,
                'subtotal'=>$subtotal/100,'billed'=>$billed/100,'received'=>$received/100,
                'commission'=>$commission/100,'eligible'=>$eligible,
            ]);
        }
        $payments = Schema::hasTable('channel_partner_payments') ? $partner->payments()->where('currency',$currency)->whereNull('voided_at')->get() : collect();
        $earned = $rows->sum('commission');
        $paid = $payments->sum(fn ($payment) => $this->cents($payment->amount))/100;
        $collected = $rows->sum('received');
        $leadChart = [];
        for ($i=5;$i>=0;$i--) {
            $month = now()->timezone('Asia/Kolkata')->startOfMonth()->subMonths($i);
            $cohort = $leads->filter(fn ($lead) => $lead->created_at?->format('Y-m') === $month->format('Y-m'));
            $leadChart[] = ['label'=>$month->format('M Y'),'leads'=>$cohort->count(),'converted'=>$cohort->where('is_converted',true)->count()];
        }
        return [
            'currency'=>$currency,'leads'=>$leads->count(),'converted'=>$converted->count(),
            'conversion_rate'=>$leads->count() ? round($converted->count()/$leads->count()*100,1) : 0,
            'attributed_customers'=>$attributedIds->count(),'excluded_customers'=>$customerIds->diff($attributedIds)->count(),
            'excluded_invoices'=>$excludedInvoices,'rows'=>$rows,'estimated'=>$rows->sum('estimated'),'approved_subtotal'=>$rows->sum('subtotal'),
            'billed'=>$rows->sum('billed'),'collected'=>$collected,'earned'=>$earned,'paid'=>$paid,
            'pending'=>max(0,round($earned-$paid,2)),'overpaid'=>max(0,round($paid-$earned,2)),
            'net_after_commission'=>round($collected-$earned,2),'cash_after_payouts'=>round($collected-$paid,2),
            'eligible_customers'=>$rows->where('eligible',true)->count(),'lead_chart'=>$leadChart,
        ];
    }

    private function cents($amount): int { return (int) round((float) $amount*100); }
}
