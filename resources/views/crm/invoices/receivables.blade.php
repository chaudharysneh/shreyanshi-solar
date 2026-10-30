@extends('layouts.app')
@section('page_title','Bank & Customer Receivables')
@section('content')
<div class="card border-0 rounded-4 shadow-sm"><div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><h4 class="fw-bold mb-1">Bank & Customer Receivables</h4><p class="text-muted small mb-0">Invoice-wise funding, collections and outstanding amounts. Totals use {{ $currency }} only.</p></div><a href="{{ route('invoices.index') }}" class="btn btn-dark-blue">All invoices</a></div>
    <div class="invoice-finance-kpis mb-3">@foreach(['received'=>'Total received','customer_pending'=>'Due from customers','bank_received'=>'Received from banks','bank_pending'=>'Due from banks'] as $key=>$label)<div><span>{{ $label }}</span><strong>{{ number_format($totals[$key],2) }}</strong></div>@endforeach</div>
    @if($totals['legacy_unallocated']>0)<div class="alert alert-warning small">{{ number_format($totals['legacy_unallocated'],2) }} {{ $currency }} was previously marked Paid without a receipt breakdown. Customer/bank dues for these invoices need reconciliation.</div>@endif
    <form id="financeReportFilters" method="GET" class="finance-report-filters border rounded-3 p-3 mt-3 mb-4">
        <div class="finance-filter-field"><label for="finance-search" class="form-label small">Search</label><input id="finance-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search finance records"></div>
        <div class="finance-filter-field"><label for="finance-bank-filter" class="form-label small">Bank</label><select id="finance-bank-filter" class="form-select" name="bank_id"><option value="">All banks</option>@foreach($banks as $bank)<option value="{{ $bank->id }}" @selected((string)request('bank_id')===(string)$bank->id)>{{ $bank->name }}</option>@endforeach</select></div>
        <div class="finance-filter-field"><label for="finance-branch-filter" class="form-label small">Branch</label><select id="finance-branch-filter" class="form-select" name="branch_id"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)request('branch_id')===(string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="finance-filter-field"><label for="finance-funding-filter" class="form-label small">Funding</label><select id="finance-funding-filter" class="form-select" name="funding"><option value="">All funding</option><option value="bank" @selected(request('funding')==='bank')>Bank financed</option><option value="customer" @selected(request('funding')==='customer')>Customer funded</option></select></div>
        <div class="finance-filter-field"><label for="finance-status-filter" class="form-label small">Payment status</label><select id="finance-status-filter" class="form-select" name="status"><option value="">All statuses</option><option value="unpaid" @selected(request('status')==='unpaid')>Unpaid</option><option value="partial" @selected(request('status')==='partial')>Partially paid</option><option value="paid" @selected(request('status')==='paid')>Paid</option></select></div>
        <div class="finance-filter-clear d-flex align-items-end"><a href="{{ route('invoices.receivables') }}" class="btn btn-dark-blue w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Clear</a></div>
        <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
    </form>
    @if(count($bankTotals))
    <div class="invoice-finance-section mb-4"><h5 class="fw-bold mb-1">Bank collections overview</h5><p class="small text-muted">All matching invoices, across every page. Amounts in {{ $currency }}.</p><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Bank</th><th>Branch</th><th>Invoices</th><th>Total loan allocation</th><th>Received from bank</th><th>Remaining from bank</th></tr></thead><tbody>@foreach($bankTotals as $bankTotal)<tr><td class="fw-semibold">{{ $bankTotal['bank'] }}</td><td>{{ $bankTotal['branch'] ?: '?' }}</td><td>{{ $bankTotal['invoices'] }}</td><td>{{ number_format($bankTotal['loan']/100,2) }}</td><td class="text-success">{{ number_format($bankTotal['received']/100,2) }}</td><td class="fw-bold">{{ number_format($bankTotal['pending']/100,2) }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Customer</th><th>Invoice</th><th>Bank / branch</th><th>Invoice total</th><th>Customer received</th><th>Customer pending</th><th>Bank received</th><th>Bank pending / final release</th><th>Action</th></tr></thead><tbody>
        @forelse($rows as $row)@php $item=$row['invoice'];$f=$row['finance']; @endphp
        <tr><td><div class="fw-semibold">{{ $item->customer?->name }}</div>@if($item->customer?->phone)<a class="small" href="tel:{{ $item->customer->phone }}">{{ $item->customer->phone }}</a>@endif</td><td><a href="{{ route('invoices.show',$item) }}">{{ $item->invoice_name }}</a><div class="small text-muted">{{ $item->invoice_no }}</div><span class="badge bg-{{ $item->status==='paid'?'success':'warning' }}-subtle text-{{ $item->status==='paid'?'success':'warning' }}">{{ $item->status==='partial'?'Partially paid':ucfirst($item->status) }}</span></td><td>{{ $f['plan']?->bank_name ?? ($f['legacy_unallocated'] ? 'Funding not recorded' : 'Customer funded') }}<div class="small text-muted">{{ $f['plan']?->branch_name }}</div><div class="small">{{ $f['plan']?->loan_reference }}</div></td><td>{{ number_format($f['total'],2) }}</td>@foreach(['customer_received','customer_pending','bank_received','bank_pending'] as $key)<td>{{ $f[$key]===null?'Needs allocation':number_format($f[$key],2) }}</td>@endforeach<td><button type="button" class="btn btn-outline-dark-blue btn-sm" data-invoice-finance="{{ $item->id }}">Finance records</button></td></tr>
        @empty<tr><td colspan="9" class="text-center text-muted py-5">No matching invoices found.</td></tr>@endforelse
    </tbody></table></div>
    {{ $invoices->links('pagination::bootstrap-5') }}
</div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 const form=document.getElementById('financeReportFilters');let timer;
 function submitFilter(select){clearTimeout(timer);if(select.name==='bank_id')form.elements.branch_id.value='';form.requestSubmit();}
 form.querySelectorAll('select').forEach(select=>{if(window.jQuery?.fn.select2){jQuery(select).select2({width:'100%',theme:'bootstrap-5'}).on('change',()=>submitFilter(select));}else select.addEventListener('change',()=>submitFilter(select));});
 form.querySelector('[name="search"]').addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>form.requestSubmit(),500);});
});
</script>
@endpush
