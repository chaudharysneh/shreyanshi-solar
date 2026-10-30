@if(!($reportOnly ?? false))
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div class="d-flex gap-2 align-items-center" style="width:100%;max-width:{{ isset($filterExportUrl) ? '570' : '450' }}px;min-width:0;">
        <div class="input-group input-group-sm" style="flex:1;min-width:0;">
            <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
            <input id="{{ $searchId }}" class="form-control crm-search-input border-0" placeholder="{{ $placeholder }}" value="{{ request('search') }}">
        </div>
        <button type="button" class="btn btn-outline-dark-blue flex-shrink-0" data-bs-toggle="collapse" data-bs-target="#moduleFilters" aria-controls="moduleFilters" aria-expanded="true"><i class="fa-solid fa-filter me-1"></i>Filters</button>
        @isset($filterExportUrl)
            <a href="{{ $filterExportUrl }}" class="btn btn-outline-dark-blue flex-shrink-0"><i class="fa-solid fa-download me-1"></i>Export</a>
        @endisset
    </div>
    <div class="d-flex align-items-center gap-2"><label for="modulePerPage" class="small text-muted text-nowrap">Show per page:</label><select id="modulePerPage" data-list-filter="per_page" class="form-select form-select-sm" style="width:88px;">@foreach([10,25,50,100] as $size)<option>{{ $size }}</option>@endforeach</select></div>
</div>
@endif
<div id="moduleFilters" class="collapse show">
    <div class="row gx-3 gy-3 gy-xl-0 mt-3 p-3 border rounded-4" style="background:#f8fafc;">
        @foreach($fields as $key => $field)
        <div class="col-12 col-md-6 col-xl" style="min-width:0;">
            <label for="filter-{{ $key }}" class="form-label fw-semibold">{{ $field['label'] }}</label>
            @if($field['kind'] === 'date')
                <div class="input-group"><span class="input-group-text"><i class="bi bi-calendar"></i></span><input id="filter-{{ $key }}" data-list-date="{{ $key }}" class="form-control" placeholder="From - To"></div>
            @elseif($field['kind'] === 'range')
                <div class="d-flex gap-2"><input id="filter-{{ $key }}" data-list-filter="{{ $key }}_min" type="number" min="0" step="0.01" class="form-control" placeholder="Min" aria-label="Minimum {{ $field['label'] }}"><input data-list-filter="{{ $key }}_max" type="number" min="0" step="0.01" class="form-control" placeholder="Max" aria-label="Maximum {{ $field['label'] }}"></div>
            @elseif($field['kind'] === 'text')
                <input id="filter-{{ $key }}" data-list-filter="{{ $key }}" class="form-control" placeholder="{{ $field['placeholder'] }}">
            @else
                <select id="filter-{{ $key }}" data-list-filter="{{ $key }}" class="form-select"><option value="">{{ $field['placeholder'] }}</option>@foreach($field['options'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            @endif
        </div>
        @endforeach
        <div class="col-12 col-md-6 {{ ($reportOnly ?? false) ? 'col-xl-auto' : 'col-xl' }} d-flex align-items-end"><button id="moduleFiltersClear" type="button" class="btn btn-dark-blue w-100"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button></div>
    </div>
</div>
@push('scripts')
<script src="{{ crm_asset('js/list-filters.js') }}"></script>
@endpush
