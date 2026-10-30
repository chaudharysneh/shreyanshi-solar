@php
    $technologyNames = \App\Models\Technology::pluck('title', 'id');
    $warrantyNames = \App\Models\Warranty::pluck('title', 'id');
@endphp
<div class="col-12 create-step-2">
    <h6 class="fw-semibold mb-2">BOM Specifications</h6>
    <div class="table-responsive border rounded-3" style="max-height: 360px; overflow: auto;">
        <table class="table table-bordered table-sm align-top mb-0" style="font-size: 12px;">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th scope="col" style="width: 30%;">BOM</th>
                    <th scope="col" style="width: 30%;">Make / Brands</th>
                    <th scope="col" style="width: 40%;">Technical Specifications &amp; Standards</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bomProducts as $bom)
                    @php
                        $specifications = [
                            'Capacity' => $bom->capacity,
                            'Technology' => $technologyNames[$bom->technology_id] ?? null,
                            'Warranty' => $warrantyNames[$bom->warranty_id] ?? null,
                            'Height' => $bom->height,
                            'Fitting Material' => $bom->fitting_material,
                            'Fitting Type' => $bom->fitting_type,
                            'Thickness' => $bom->thickness,
                            'Pipe Size' => $bom->size_of_pipe,
                            'Meter' => $bom->meter,
                            'Nos' => $bom->nos,
                        ];
                        $specifications = array_filter($specifications, fn ($value) => $value !== null && trim((string) $value) !== '');
                    @endphp
                    <tr>
                        <td class="p-2 fw-semibold">{{ $bom->product_name }}</td>
                        <td class="p-2">
                            <textarea class="form-control form-control-sm" rows="2" name="bom_specifications[{{ $bom->id }}][make]" aria-label="Make / Brands for {{ $bom->product_name }}" maxlength="2000">{{ old("bom_specifications.$bom->id.make", isset($estimate->bom_specifications[$bom->id]) ? ($estimate->bom_specifications[$bom->id]['make'] ?? '') : $bom->categories->pluck('name')->implode(', ')) }}</textarea>
                        </td>
                        <td class="p-2">
                            @php
                                $defaultTechnical = collect($specifications)->map(fn ($value, $label) => $label . ': ' . $value)->implode("\n");
                                if (trim((string) $bom->description) !== '') {
                                    $defaultTechnical .= ($defaultTechnical !== '' ? "\n" : '') . $bom->description;
                                }
                            @endphp
                            <textarea class="form-control form-control-sm" rows="4" name="bom_specifications[{{ $bom->id }}][technical]" aria-label="Technical specifications for {{ $bom->product_name }}" maxlength="10000">{{ old("bom_specifications.$bom->id.technical", isset($estimate->bom_specifications[$bom->id]) ? ($estimate->bom_specifications[$bom->id]['technical'] ?? '') : $defaultTechnical) }}</textarea>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-3 text-muted text-center">No BOMs available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
