<div class="card border-0 shadow-sm h-100" id="solar-actions">
    <div class="card-header dashboard-widget-head d-flex align-items-center justify-content-between py-3">
        <h5 class="mb-0 fw-bold">Today's Action List</h5>
        <span class="badge bg-light text-dark px-3 py-2 fw-semibold small">{{ $solar['actionCount'] }} due</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive dashboard-actions-scroll" tabindex="0" aria-label="Today's actions, scroll for more">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Action</th><th>Type</th><th class="text-end">Due</th></tr></thead>
                <tbody>
                    @forelse($solar['actions'] as $action)
                        <tr>
                            <td><a href="{{ $action['url'] }}" class="fw-semibold text-decoration-none">{{ $action['title'] }}</a><div class="text-muted small">{{ $action['detail'] }}</div></td>
                            <td><span class="dashboard-label-badge dashboard-label-badge--{{ ['Task' => 'blue', 'Follow-up' => 'amber', 'Meeting' => 'purple'][$action['kind']] ?? 'slate' }}">{{ $action['kind'] }}</span></td>
                            <td class="text-end small {{ $action['date']->lt(today()) ? 'text-danger' : 'text-muted' }}"><time class="text-nowrap" datetime="{{ $action['date']->toIso8601String() }}">{{ $action['date']->format('d M, h:i A') }}</time>@if($action['date']->lt(today()))<span class="small d-block">Overdue</span>@endif</td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-4">No tasks, follow-ups, or meetings due today.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const viewport = document.querySelector('#solar-actions .dashboard-actions-scroll');
        const table = viewport.querySelector('table');
        const sizeActions = function () {
            const rows = Array.from(table.tBodies[0].rows);
            if (rows.length <= 5) { viewport.style.maxHeight = 'none'; return; }
            const height = table.tHead.getBoundingClientRect().height + rows.slice(0, 5).reduce(function (sum, row) {
                return sum + row.getBoundingClientRect().height;
            }, 0);
            viewport.style.maxHeight = Math.ceil(height) + 'px';
        };
        sizeActions();
        if (window.ResizeObserver) new ResizeObserver(sizeActions).observe(table);
        else window.addEventListener('resize', sizeActions);
    });
</script>
@endpush
