@if(auth()->user()->hasMatrixPermission('view_invoices') && auth()->user()->can('view',$invoice))
@php
    $finance = app(\App\Services\InvoiceFinanceService::class)->summary($invoice);
    $financeMoney = fn ($value) => $value === null ? 'Needs allocation' : number_format($value,2).' '.$finance['currency'];
@endphp
<section class="invoice-finance-panel my-3">
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3"><div><h5 class="fw-bold mb-1">Invoice finance & collections</h5><div class="small text-muted">{{ $invoice->invoice_name }} &middot; {{ $invoice->invoice_no }} &middot; {{ $finance['status'] === 'partial' ? 'Partially paid' : ucfirst($finance['status']) }}</div></div><button type="button" class="btn btn-dark-blue" data-invoice-finance="{{ $invoice->id }}"><i class="bi bi-wallet2 me-2"></i>{{ auth()->user()->hasMatrixPermission('edit_invoices') && auth()->user()->can('update',$invoice) ? 'Manage payments' : 'View finance records' }}</button></div>
    <div class="invoice-finance-kpis">@foreach(['customer_received'=>'Customer received','customer_pending'=>'Customer pending','bank_received'=>'Bank received','bank_pending'=>'Bank pending'] as $key=>$label)<div><span>{{ $label }}</span><strong>{{ $financeMoney($finance[$key]) }}</strong></div>@endforeach</div>
    @if($finance['plan']?->funding_type === 'bank')<div class="small text-muted mt-3"><i class="bi bi-bank me-1"></i>{{ $finance['plan']->bank_name }} &middot; {{ $finance['plan']->branch_name }} &middot; Bank share: {{ $financeMoney($finance['bank_expected']) }} @if($finance['plan']->loan_reference)&middot; Loan reference: {{ $finance['plan']->loan_reference }}@endif</div>@endif
    @if($finance['legacy_unallocated'])<div class="alert alert-warning small mt-3 mb-0">Earlier Paid status has {{ $financeMoney($finance['legacy_unallocated']) }} without a customer/bank receipt breakdown. Open finance records to reconcile it.</div>@endif
</section>
@endif
