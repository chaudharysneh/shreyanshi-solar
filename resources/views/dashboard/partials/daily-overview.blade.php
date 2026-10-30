@php
    $todayMeetings = $solar['actions']->where('kind', 'Meeting');
    $todayFollowups = $solar['actions']->where('kind', 'Follow-up')->filter(fn ($item) => $item['date']->isToday());
    $urgentWork = $solar['actions']->filter(fn ($item) => $item['kind'] === 'Task' || $item['date']->lt(today()));
@endphp
<div class="modal fade" id="dailyOverviewModal" tabindex="-1" aria-labelledby="dailyOverviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered daily-overview-dialog">
        <div class="modal-content daily-overview">
            <div class="modal-header">
                <div>
                    <div class="daily-overview-eyebrow">YOUR SOLAR CRM AT A GLANCE</div>
                    <h5 class="modal-title" id="dailyOverviewTitle">Today's overview &middot; {{ now()->format('d M Y') }}</h5>
                    <p class="mb-0 small text-muted mt-1">Your schedule, work needing attention, and money to collect.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daily-overview-grid">
                    @foreach([
                        ['title' => "Today's meetings", 'icon' => 'calendar-event', 'items' => $todayMeetings, 'empty' => 'No meetings scheduled today.'],
                        ['title' => "Today's follow-ups", 'icon' => 'telephone', 'items' => $todayFollowups, 'empty' => 'No follow-ups scheduled today.'],
                        ['title' => 'Work needing attention', 'icon' => 'exclamation-circle', 'items' => $urgentWork, 'empty' => 'No overdue follow-ups or tasks due.'],
                    ] as $column)
                    <section class="daily-overview-column">
                        <h6><span><i class="bi bi-{{ $column['icon'] }} me-1"></i>{{ $column['title'] }}</span><span class="daily-overview-count">{{ $column['items']->count() }}</span></h6>
                        <div class="daily-overview-list">
                            @forelse($column['items'] as $item)
                                <a class="daily-overview-card" href="{{ $item['url'] }}">
                                    <strong>{{ $item['title'] }}</strong>
                                    <span class="small text-muted">{{ $item['detail'] }}</span>
                                    <span class="small">{{ $item['date']->format('d M Y, h:i A') }}</span>
                                    <span class="daily-overview-tag">{{ $item['kind'] }}{{ $item['date']->lt(today()) ? ' · Overdue' : '' }}</span>
                                </a>
                            @empty
                                <p class="daily-overview-empty">{{ $column['empty'] }}</p>
                            @endforelse
                        </div>
                    </section>
                    @endforeach
                    @if(auth()->user()->hasMatrixPermission('view_invoices'))
                    <section class="daily-overview-column daily-overview-money">
                        <h6><span><i class="bi bi-bank me-1"></i>Bank receivables</span><span class="daily-overview-count">{{ $bankReceivables->count() }}</span></h6>
                        <div class="daily-overview-list">
                            @forelse($bankReceivables as $bank)
                                <div class="daily-overview-card">
                                    <strong>{{ $bank['bank'] }}</strong>
                                    <span class="daily-overview-amount">&#8377; {{ $formatDashboardAmount($bank['pending']) }}</span>
                                    <span class="small text-muted">Pending from bank &middot; {{ $bank['items']->count() }} invoices</span>
                                    @if($bank['overdue'] > 0)<span class="small text-danger">Overdue: &#8377; {{ $formatDashboardAmount($bank['overdue']) }}</span>@endif
                                    <details>
                                        <summary class="small">View customers &amp; expected dates</summary>
                                        @foreach($bank['items'] as $item)
                                            <a class="daily-overview-bank-item" href="{{ $item['url'] }}">
                                                <strong>{{ $item['customer'] }}</strong>
                                                <span>{{ $item['invoice'] }} @if($item['branch']) &middot; {{ $item['branch'] }} @endif</span>
                                                <span>&#8377; {{ $formatDashboardAmount($item['pending']) }}</span>
                                                <span class="{{ $item['overdue'] ? 'text-danger' : 'text-muted' }}">{{ $item['date'] ? 'Expected '.$item['date']->format('d M Y') : 'Expected date not set' }}</span>
                                            </a>
                                        @endforeach
                                    </details>
                                </div>
                            @empty
                                <p class="daily-overview-empty">No pending bank-funded amounts.</p>
                            @endforelse
                        </div>
                    </section>
                    <section class="daily-overview-column">
                        <h6><span><i class="bi bi-receipt me-1"></i>Invoice dues</span><span class="daily-overview-count">{{ $solar['outstanding']->count() }}</span></h6>
                        <div class="daily-overview-list">
                            <p class="small text-muted mb-0">Total unpaid invoice balances, including bank-funded shares.</p>
                            @if($solar['outstanding']->isNotEmpty())
                                <div class="daily-overview-total">&#8377; {{ $formatDashboardAmount($solar['outstanding']->sum('outstanding_balance')) }}</div>
                            @endif
                            @forelse($solar['outstanding']->sortBy('due_date') as $invoice)
                                <a class="daily-overview-card" href="{{ route('invoices.show', $invoice) }}">
                                    <strong>{{ $invoice->customer?->name ?? 'Customer' }}</strong>
                                    <span class="small text-muted">{{ $invoice->invoice_no ?: $invoice->invoice_name }}</span>
                                    <span class="daily-overview-amount">&#8377; {{ $formatDashboardAmount($invoice->outstanding_balance) }}</span>
                                    <span class="small {{ $invoice->due_date?->lt(today()) ? 'text-danger' : 'text-muted' }}">{{ $invoice->due_date ? 'Due '.$invoice->due_date->format('d M Y') : 'Due date not set' }}</span>
                                </a>
                            @empty
                                <p class="daily-overview-empty">No outstanding invoices.</p>
                            @endforelse
                        </div>
                    </section>
                    @endif
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
@push('styles')
<style>
.solar-date-form .daily-overview-trigger{display:inline-flex;align-items:center;justify-content:center;gap:8px;flex-shrink:0;min-height:38px;padding:6px 12px;border:1px solid #c6d8ed;border-radius:9px;background:#edf4fd;color:#173f73;font-size:13px;font-weight:600;line-height:1.4;white-space:nowrap;box-shadow:0 2px 5px rgba(18,60,115,.05);transition:background .15s,border-color .15s,box-shadow .15s}
.solar-date-form .daily-overview-trigger:hover{background:#dfeafb;border-color:#94b5dc;box-shadow:0 3px 8px rgba(18,60,115,.1)}
.solar-date-form .daily-overview-trigger:focus-visible{outline:3px solid rgba(52,112,190,.3);outline-offset:2px}
.daily-overview-trigger-icon{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;background:#dce8f8;color:#245992}
[data-theme="dark"] .solar-date-form .daily-overview-trigger{background:#21344e;border-color:#3c5577;color:#dbeafe}
[data-theme="dark"] .solar-date-form .daily-overview-trigger:hover{background:#2a4262}
[data-theme="dark"] .daily-overview-trigger-icon{background:#324c6d;color:#bfdbfe}
.daily-overview-dialog{max-width:1280px;width:calc(100% - 48px);margin:24px auto}
.daily-overview{border:0;border-radius:20px;overflow:hidden;background:var(--crm-bg-card,#fff);color:var(--crm-text-primary,#1f3552)}
.daily-overview .modal-header{padding:14px 18px;background:linear-gradient(110deg,#eaf2ff,#f4f8ff);align-items:flex-start}
.daily-overview .modal-header .btn-close{align-self:center;flex:0 0 34px;box-sizing:border-box;width:34px;height:34px;margin:0 0 0 auto;padding:0;border:1px solid #cbd8e8;border-radius:50%;background-color:rgba(255,255,255,.8);background-size:12px;opacity:.8;box-shadow:0 2px 6px rgba(18,60,115,.06);transition:background-color .15s,box-shadow .15s,opacity .15s}
.daily-overview .modal-header .btn-close:hover{background-color:#fff;opacity:1;box-shadow:0 3px 10px rgba(18,60,115,.14)}
.daily-overview .modal-header .btn-close:focus-visible{outline:3px solid rgba(52,112,190,.3);outline-offset:3px}
[data-theme="dark"] .daily-overview .modal-header .btn-close{background-color:#cbd5e1;border-color:#94a3b8;filter:none}
[data-theme="dark"] .daily-overview .modal-header .btn-close:hover{background-color:#f1f5f9}
.daily-overview-eyebrow{font-size:10px;font-weight:700;letter-spacing:.12em;color:#456993;margin-bottom:6px}
.daily-overview .modal-title{font-weight:700;font-size:17px}
.daily-overview .modal-header p{font-size:12px}
.daily-overview .modal-body{padding:12px;max-height:65vh;overflow:auto}
.daily-overview .modal-footer{padding:8px 14px}
.daily-overview .modal-footer .btn{font-size:13px;padding:6px 14px}
.daily-overview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
.daily-overview-column{min-width:0;padding:10px;border:1px solid #e0e7ef;border-radius:12px;background:#f7f9fc}
.daily-overview-column h6{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;font-weight:700;min-height:32px;margin:0 0 12px}
.daily-overview-count{background:#183d70;color:#fff;border-radius:20px;padding:4px 9px;font-size:12px}
.daily-overview-list{height:270px;overflow:auto;display:flex;flex-direction:column;gap:8px;padding-right:4px;scrollbar-width:thin}
.daily-overview-card{display:flex;flex-direction:column;gap:5px;flex-shrink:0;padding:10px;border:1px solid #e0e7ef;border-left:3px solid #6286b7;border-radius:10px;background:var(--crm-bg-card,#fff);color:inherit;text-decoration:none;font-size:12px;line-height:1.45;overflow-wrap:anywhere}
a.daily-overview-card:hover{border-color:#6286b7;background:#f0f6ff;color:inherit}
.daily-overview-tag{align-self:flex-start;padding:3px 8px;border-radius:20px;font-size:11px;background:#edf2fa;color:#34547c}
.daily-overview-empty{padding:16px 12px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;color:#64748b}
.daily-overview-amount,.daily-overview-total{font-size:15px;font-weight:700;font-variant-numeric:tabular-nums}
.daily-overview-money .daily-overview-card{border-left-color:#25927b}
.daily-overview-total{padding:8px 0;color:#234c7d}
.daily-overview-bank-item{display:flex;flex-direction:column;gap:3px;padding:10px 0;border-top:1px solid #e0e7ef;margin-top:8px;font-size:12px;color:inherit;text-decoration:none}
.daily-overview summary{cursor:pointer;color:#345d8e;margin-top:4px}
[data-theme="dark"] .daily-overview .modal-header{background:#1e293b}
[data-theme="dark"] .daily-overview-column{background:#0f172a;border-color:#334155}
[data-theme="dark"] .daily-overview-card{border-color:#334155}
[data-theme="dark"] .daily-overview-total,[data-theme="dark"] .daily-overview-eyebrow,[data-theme="dark"] .daily-overview summary{color:#93c5fd}
[data-theme="dark"] a.daily-overview-card:hover{background:#1e293b}
@media(max-width:575px){.daily-overview-dialog{width:auto;margin:12px}.daily-overview-grid{grid-template-columns:1fr}.daily-overview-list{height:auto;max-height:280px}.daily-overview .modal-header{padding:18px}.daily-overview .modal-body{max-height:70vh;overflow:auto}}
</style>
@endpush
@if($showDailyOverview)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('dailyOverviewModal')).show();
});
</script>
@endpush
@endif
