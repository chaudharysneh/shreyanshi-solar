@php
    $bomSpecificationFields = ['technology_id', 'warranty_id', 'height', 'fitting_material', 'fitting_type', 'thickness', 'size_of_pipe', 'capacity', 'meter', 'nos'];
    $bomSpecificationDefaults = collect($bomProducts ?? [])->mapWithKeys(fn ($bom) => [(string) $bom->id => $bom->only($bomSpecificationFields)]);
@endphp
<script>
    window.bomSpecificationDefaults = @json($bomSpecificationDefaults);
    window.bomSpecificationFields = @json($bomSpecificationFields);
    window.getEstimateBomSpecifications = function (row, productId) {
        const saved = JSON.parse(row.dataset.bomSpecifications || '{}');
        return saved[String(productId)] || window.bomSpecificationDefaults[String(productId)] || {};
    };
</script>
<div class="row g-3 mb-3">
    @foreach (['technology_id' => ['Technology', \App\Models\Technology::orderBy('title')->get()], 'warranty_id' => ['Warranty', \App\Models\Warranty::orderBy('title')->get()]] as $field => [$label, $options])
        <div class="col-md-6">
            <label for="edit_bom_{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
            <select id="edit_bom_{{ $field }}" class="form-select">
                <option value="">Select {{ $label }}</option>
                @foreach ($options as $option)
                    <option value="{{ $option->id }}">{{ $option->title }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
    @foreach (['height' => 'Height', 'fitting_material' => 'Fitting Material', 'fitting_type' => 'Fitting Type', 'thickness' => 'Thickness', 'size_of_pipe' => 'Pipe Size', 'capacity' => 'Capacity', 'meter' => 'Meter', 'nos' => 'Nos'] as $field => $label)
        <div class="col-md-6">
            <label for="edit_bom_{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
            <input type="text" id="edit_bom_{{ $field }}" class="form-control" placeholder="Enter {{ $label }}">
        </div>
    @endforeach
</div>
