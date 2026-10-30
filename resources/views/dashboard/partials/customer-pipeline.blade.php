<div class="card border-0 shadow-sm h-100">
    <div class="card-header dashboard-widget-head d-flex align-items-center justify-content-between py-3">
        <h5 class="mb-0 fw-bold">Customer Pipeline Summary</h5>
        @if($customerPipeline['allowed'])<a href="{{ route('masters.customers.index') }}" class="badge bg-light text-dark px-3 py-2 fw-semibold small">View All</a>@endif
    </div>
    <div class="card-body">
        @if($customerPipeline['allowed'])
            <p class="small text-muted mb-3">Current customer counts. Select a stage to open its customer list.</p>
            <div class="dashboard-pipeline-grid">
                @forelse($customerPipeline['stages'] as $stage)
                    @php $hue = fmod($stage['id'] * 137.508, 360); @endphp
                    <a class="dashboard-pipeline-stage" href="{{ route('masters.customers.index', ['pipeline_stage_id' => $stage['id']]) }}" style="--stage-tone:hsl({{ $hue }},45%,38%);">
                        <span class="dashboard-pipeline-dot" aria-hidden="true"></span><span class="dashboard-pipeline-name">{{ $stage['name'] }}</span><strong>{{ number_format($stage['count']) }}</strong><i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @empty<p class="text-muted small">No pipeline stages configured.</p>@endforelse
            </div>
            <p class="small text-muted mt-3 mb-0">{{ $customerPipeline['unassigned'] }} customers have no stage assigned.</p>
        @else<p class="text-muted small mb-0">Customer pipeline is unavailable with your permissions.</p>@endif
    </div>
</div>
