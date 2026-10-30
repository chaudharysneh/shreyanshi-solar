@php
    $solarKpis = [
        ['label' => 'New Leads', 'value' => str_pad(number_format($solar['leads']->count()), 2, '0', STR_PAD_LEFT), 'icon' => 'bi-megaphone', 'theme' => 'customers', 'href' => route('leads.index'), 'module' => 'leads', 'note' => 'Selected period'],
        ['label' => 'Open Deals', 'value' => str_pad(number_format($solar['openDeals']->count()), 2, '0', STR_PAD_LEFT), 'icon' => 'bi-briefcase', 'theme' => 'deals', 'href' => route('deals.index'), 'module' => 'deals', 'note' => 'Current pipeline'],
        ['label' => 'Pending Follow-ups', 'value' => str_pad(number_format($solar['followups']->count()), 2, '0', STR_PAD_LEFT), 'icon' => 'bi-clock-history', 'theme' => 'followups', 'href' => '#solar-actions', 'module' => 'followups', 'note' => $solar['overdueFollowups']->count().' overdue'],
        ['label' => 'Outstanding Payments', 'value' => str_pad(number_format($solar['outstanding']->count()), 2, '0', STR_PAD_LEFT), 'icon' => 'bi-wallet2', 'theme' => 'followups', 'href' => route('invoices.index'), 'module' => 'invoices', 'note' => 'Invoices with an unpaid balance'],
    ];
@endphp
<div id="dashboardStats" class="solar-dashboard mb-2">
    <div class="solar-toolbar mb-3">
        <div><h2 class="h5 fw-bold mb-1">Overview</h2><p class="small text-muted mb-0">{{ $solar['from']->format('d M Y') }} to {{ $solar['to']->format('d M Y') }}. New leads use this period; other KPIs show current totals.</p></div>
        <form method="POST" action="{{ url('/dashboard/filters') }}" class="solar-date-form" aria-label="Dashboard activity period">
            @csrf
            <button type="button" class="daily-overview-trigger" data-bs-toggle="modal" data-bs-target="#dailyOverviewModal" aria-controls="dailyOverviewModal">
                <span class="daily-overview-trigger-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                <span>Today's overview</span>
            </button>
            <label for="solarPeriod" class="visually-hidden">Activity period</label>
            <select name="solar_period" id="solarPeriod" class="form-select form-select-sm">
                @foreach(['today' => 'Today', 'month' => 'Month to date', 'custom' => 'Custom range'] as $value => $label)<option value="{{ $value }}" @selected($solar['period'] === $value)>{{ $label }}</option>@endforeach
            </select>
            <div id="solarCustomDates" class="solar-custom-dates {{ $solar['period'] !== 'custom' ? 'd-none' : '' }}">
                <input type="date" name="solar_from" class="form-control form-control-sm" value="{{ $solar['from']->format('Y-m-d') }}" aria-label="Start date">
                <input type="date" name="solar_to" class="form-control form-control-sm" value="{{ $solar['to']->format('Y-m-d') }}" aria-label="End date">
            </div>
            <button class="btn btn-dark-blue btn-sm" type="submit">Apply</button>
        </form>
    </div>
    <div class="row g-3">
        @foreach($solarKpis as $kpi)
            @if(auth()->user()->hasMatrixPermission('view_'.$kpi['module']))
            <div class="col-6 col-lg-3"><a href="{{ $kpi['href'] }}" class="text-decoration-none d-block h-100">
                <div class="metric-card metric-card--{{ $kpi['theme'] }} card h-100"><div class="card-body d-flex align-items-center justify-content-between gap-2">
                    <div class="metric-content"><p class="metric-label mb-2">{{ $kpi['label'] }}</p><div class="metric-value">{{ $kpi['value'] }}</div><div class="solar-kpi-note {{ $kpi['label'] === 'Pending Follow-ups' && $solar['overdueFollowups']->isNotEmpty() ? 'text-danger' : '' }}">{{ $kpi['note'] }}</div>
                    @if($kpi['label'] === 'New Leads')<div class="solar-kpi-note">@if($solar['change'] !== null){{ $solar['change'] > 0 ? '+' : '' }}{{ $solar['change'] }}% vs previous {{ $solar['from']->diffInDays($solar['to']->copy()->startOfDay()) + 1 }} days @else No previous-period baseline @endif</div>@endif</div>
                    <span class="metric-icon" aria-hidden="true"><i class="bi {{ $kpi['icon'] }}"></i></span>
                </div></div>
            </a></div>
            @endif
        @endforeach
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('solarPeriod').addEventListener('change', function () {
        document.getElementById('solarCustomDates').classList.toggle('d-none', this.value !== 'custom');
    });
</script>
@endpush
