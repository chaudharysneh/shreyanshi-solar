@extends('layouts.app')
@section('page_title', 'Partner Dashboard')
@section('content')
@php $money = fn ($value) => number_format($value,2).' '.$report['currency']; @endphp
<div class="container-fluid p-0 partner-dashboard">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3"><span class="partner-avatar"><i class="bi bi-person-badge"></i></span><div><h1 class="h4 fw-bold mb-1">{{ $partner->name }}</h1><span class="text-muted small">{{ $partner->partner_code }} @if($partner->company_name)· {{ $partner->company_name }}@endif</span><span class="badge rounded-pill ms-2 {{ $partner->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($partner->status) }}</span></div></div>
            <div class="d-flex flex-wrap gap-2">@if(auth()->user()->hasMatrixPermission('edit_partners'))<button class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#partnerPaymentModal" @disabled($report['pending'] <= 0)><i class="bi bi-wallet2 me-1"></i>Record Payment</button><a class="btn btn-outline-dark-blue" href="{{ route('partners.edit',$partner) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endif<a class="btn btn-outline-dark-blue" href="{{ route('partners.index') }}">Back</a></div>
        </div>
        <div class="card-body px-4 py-3 d-flex flex-wrap gap-4 small text-muted"><span><i class="bi bi-telephone me-1"></i>{{ $partner->phone }}</span><span>Assigned: {{ $partner->assignedUser?->name ?: 'Unassigned' }}</span><span>Joined {{ $partner->joining_date?->format('d M Y') ?: '—' }}</span><span>{{ number_format($partner->commission_value,2) }}{{ $partner->commission_type === 'percent' ? '%' : ' '.$report['currency'].' fixed' }} commission</span><span>All-time report</span></div>
    </div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-3 mb-4">
        @foreach(['Leads Given'=>[$report['leads'],'bi-megaphone','#3b5bdb'],'Converted Leads'=>[$report['converted'],'bi-person-check','#059669'],'Conversion Rate'=>[$report['conversion_rate'].'%','bi-graph-up-arrow','#7c3aed'],'Eligible Customers'=>[$report['eligible_customers'],'bi-award','#d97706']] as $label=>[$value,$icon,$tone])
        <div class="col-6 col-xl-3"><div class="partner-kpi h-100" style="--partner-tone:{{ $tone }}"><div><div class="small text-muted fw-semibold mb-2">{{ $label }}</div><div class="h3 fw-bold mb-0">{{ $value }}</div></div><span class="partner-kpi-icon"><i class="bi {{ $icon }}"></i></span></div></div>
        @endforeach
    </div>
    <div class="row g-3 mb-4">
        @foreach(['Customer Collections'=>[$report['collected'],'bi-cash-stack','#3b5bdb'],'Partner Earned'=>[$report['earned'],'bi-wallet2','#7c3aed'],'Paid to Partner'=>[$report['paid'],'bi-check2-circle','#059669'],'Pending to Partner'=>[$report['pending'],'bi-hourglass-split','#d97706']] as $label=>[$value,$icon,$tone])
        <div class="col-sm-6 col-xl-3"><div class="partner-kpi h-100" style="--partner-tone:{{ $tone }}"><div style="min-width:0"><div class="small text-muted fw-semibold mb-2">{{ $label }}</div><div class="h4 fw-bold mb-0 text-break">{{ $money($value) }}</div></div><span class="partner-kpi-icon"><i class="bi {{ $icon }}"></i></span></div></div>
        @endforeach
    </div>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="px-4 pt-4"><h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Partner Insights</h6></div>
        <ul class="nav nav-tabs px-3 px-md-4 gap-2 flex-wrap" id="partnerDashboardTabs" role="tablist">
            @foreach(['overview'=>['bi-grid','Overview'],'details'=>['bi-person-vcard','Partner Details'],'leads'=>['bi-megaphone','Referred Leads'],'payments'=>['bi-wallet2','Payment History']] as $tab=>[$icon,$label])
            <li class="nav-item" role="presentation"><button class="nav-link {{ $loop->first ? 'active' : '' }}" id="partner-{{ $tab }}-tab" data-bs-toggle="tab" data-bs-target="#partner-{{ $tab }}" type="button" role="tab" aria-controls="partner-{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</button></li>
            @endforeach
        </ul>
        <div class="tab-content p-3 p-md-4">
            <div class="tab-pane fade show active" id="partner-overview" role="tabpanel" aria-labelledby="partner-overview-tab">
                <div class="row g-4 mb-4">
                    <div class="col-lg-7"><div class="partner-form-section h-100"><h6>Lead &amp; Conversion Trend</h6><p class="small text-muted">Leads created each month and how many of those leads are converted now.</p><div class="partner-chart"><canvas id="partnerLeadTrend" role="img" aria-label="Monthly referred leads and converted leads"></canvas></div></div></div>
                    <div class="col-lg-5"><div class="partner-form-section h-100"><h6>Lead Conversion</h6><div class="partner-chart"><canvas id="partnerConversionChart" role="img" aria-label="Converted versus unconverted leads"></canvas></div><p class="small text-muted text-center mb-0">{{ $report['converted'] }} converted from {{ $report['leads'] }} referred leads</p></div></div>
                    <div class="col-lg-7"><div class="partner-form-section h-100"><h6>Your Business from this Partner</h6><dl class="row mb-0">
                        @foreach(['Estimates subtotal'=>$report['estimated'],'Approved estimate subtotal'=>$report['approved_subtotal'],'Invoiced revenue'=>$report['billed'],'Customer collections'=>$report['collected'],'Partner commission earned'=>$report['earned'],'Collections after earned commission'=>$report['net_after_commission'],'Cash remaining after partner payments'=>$report['cash_after_payouts']] as $label=>$value)<dt class="col-sm-8 text-muted fw-normal mb-3">{{ $label }}</dt><dd class="col-sm-4 text-sm-end fw-semibold text-break mb-3">{{ $money($value) }}</dd>@endforeach
                    </dl><p class="small text-muted mb-0">Collections after commission exclude materials, installation, taxes and other costs; this is not a profit calculation.</p></div></div>
                    <div class="col-lg-5"><div class="partner-form-section h-100"><h6>Commission Settlement</h6><div class="partner-chart"><canvas id="partnerSettlementChart" role="img" aria-label="Partner commission paid and pending"></canvas></div>@if($report['overpaid'] > 0)<div class="alert alert-warning small mb-0">Recorded payments exceed current calculated commission by {{ $money($report['overpaid']) }}.</div>@endif</div></div>
                </div>
                <div class="partner-form-section mb-4"><h6>Customer Contribution</h6><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Customer</th><th>Estimates Subtotal</th><th>Approved Subtotal</th><th>Invoiced</th><th>Collected</th><th>Partner Earned</th><th>Eligibility</th></tr></thead><tbody>
                    @forelse($report['rows'] as $row)<tr><td>@if(auth()->user()->hasMatrixPermission('view_customers'))<a href="{{ route('masters.customers.show',$row['customer_id']) }}">{{ $row['customer_name'] }}</a>@else{{ $row['customer_name'] }}@endif</td><td>{{ $money($row['estimated']) }}</td><td>{{ $money($row['subtotal']) }}</td><td>{{ $money($row['billed']) }}</td><td>{{ $money($row['received']) }}</td><td class="fw-semibold">{{ $money($row['commission']) }}</td><td><span class="badge {{ $row['eligible'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $row['eligible'] ? 'Eligible' : 'Awaiting milestone' }}</span></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No converted customers attributed to this partner yet.</td></tr>@endforelse
                </tbody></table></div></div>
                <div class="small text-muted partner-report-notes"><strong>How this report is calculated</strong><ul class="mt-2 mb-0"><li>Amounts use {{ $report['currency'] }} only. {{ $report['excluded_invoices'] }} invoices in another or unrecognized currency are excluded. Legacy records without a currency use the estimate currency or application default.</li><li>Commission uses current partner settings: {{ ucfirst(str_replace('_',' ',$partner->commission_basis)) }}, payable on {{ str_replace('_',' ',$partner->payable_on) }}. Fixed commission applies once per eligible converted customer.</li><li>The customer's saved channel partner takes priority; otherwise the earliest converted partner lead by creation date is used, with lead ID breaking ties. {{ $report['excluded_customers'] }} customers attributed elsewhere are excluded.</li><li>Estimates subtotal includes pending and approved/completed estimates; commission on estimate subtotal uses approved/completed estimates only. Issued, non-cancelled invoices count as revenue. Paid invoices count as fully collected; otherwise saved receipts count up to invoice value, without double counting. Paid amounts use recorded, non-voided partner payments.</li></ul></div>
            </div>
            <div class="tab-pane fade" id="partner-details" role="tabpanel" aria-labelledby="partner-details-tab">@include('crm.partners.partials.details')</div>
            <div class="tab-pane fade" id="partner-leads" role="tabpanel" aria-labelledby="partner-leads-tab">
                <h6 class="fw-bold">Referred Leads <span class="badge bg-secondary">{{ $leads->total() }}</span></h6><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Lead</th><th>Phone</th><th>Status</th><th>Converted</th><th>Date</th></tr></thead><tbody>@forelse($leads as $lead)<tr><td><a href="{{ route('leads.show',$lead) }}">{{ $lead->name }}</a></td><td>{{ $lead->phone }}</td><td>{{ ucfirst(str_replace('_',' ',$lead->status)) }}</td><td>{{ $lead->is_converted ? 'Yes' : 'No' }}</td><td>{{ $lead->created_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No accessible referred leads yet.</td></tr>@endforelse</tbody></table></div>{{ $leads->fragment('partner-leads')->links('pagination::bootstrap-5') }}
            </div>
            <div class="tab-pane fade" id="partner-payments" role="tabpanel" aria-labelledby="partner-payments-tab">
                <h6 class="fw-bold">Payment History</h6><p class="small text-muted">Recorded payments to this partner. Voided entries remain visible for reference.</p>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Date</th><th>Amount</th><th>Method / Reference</th><th>Recorded By</th><th>Notes</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->payment_date->format('d M Y') }}</td><td class="text-nowrap fw-semibold">{{ number_format($payment->amount,2) }} {{ $payment->currency }}</td><td>{{ ucfirst(str_replace('_',' ',$payment->method)) }}<div class="small text-muted">{{ $payment->reference }}</div></td><td>{{ $payment->creator?->name ?: '—' }}</td><td class="text-break">{{ $payment->notes ?: '—' }}@if($payment->voided_at)<div class="small text-muted">Void reason: {{ $payment->void_reason }}</div>@endif</td><td><span class="badge {{ $payment->voided_at ? 'bg-secondary-subtle text-secondary' : 'bg-success-subtle text-success' }}">{{ $payment->voided_at ? 'Voided' : 'Recorded' }}</span></td><td>@if(!$payment->voided_at && auth()->user()->hasMatrixPermission('edit_partners'))<button type="button" class="btn btn-sm btn-outline-danger partner-void-payment" data-url="{{ route('partners.payments.void',[$partner,$payment]) }}">Void</button>@endif</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No partner payments recorded yet.</td></tr>@endforelse</tbody></table></div>{{ $payments->fragment('partner-payments')->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@include('crm.partners.partials.payment-modals')
@endsection
@include('crm.partners.partials.assets')
@push('styles')
<style>.partner-avatar{display:inline-flex;align-items:center;justify-content:center;width:60px;height:60px;flex-shrink:0;background:#e9efff;color:#3b5bdb;border-radius:16px;font-size:1.8rem}.partner-chart{position:relative;height:270px}.partner-dashboard .nav-link{border-radius:10px 10px 0 0;padding:12px 18px;font-weight:600}.partner-report-notes{padding:18px;background:var(--crm-bg-soft,#f8fafc);border-radius:12px}.partner-report-notes li{margin-top:6px}</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>window.partnerReportData = @json(collect($report)->except('rows'));</script>
<script src="{{ crm_asset('js/partner-report.js') }}"></script>
@endpush
