@php
    $renderBomProducts = \App\Models\BomProduct::with(['categories', 'technology', 'warranty'])
        ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
        ->orderBy('product_name')->get();
    $savedBomSpecifications = is_array($estimate->bom_specifications ?? null) ? $estimate->bom_specifications : [];
@endphp
<section class="estimate-bom-specifications" style="width:calc(100% - 48px); margin:24px auto; page-break-before:always; font-family:sans-serif;">
    <h2 style="font-size:18px; margin:0 0 12px; font-weight:700; color:#2f7f3d;">BOM Specifications</h2>
    <table style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead><tr>
            <th style="border:1px solid #333; padding:10px; text-align:left; width:25%; background:#4b9349; color:#fff; font-weight:700;">BOM</th>
            <th style="border:1px solid #333; padding:10px; text-align:left; width:30%; background:#4b9349; color:#fff; font-weight:700;">Make / Brands</th>
            <th style="border:1px solid #333; padding:10px; text-align:left; width:45%; background:#4b9349; color:#fff; font-weight:700;">Technical Specifications &amp; Standards</th>
        </tr></thead>
        <tbody>
        @foreach ($renderBomProducts as $bom)
            @php
                $saved = $savedBomSpecifications[$bom->id] ?? [];
                $make = array_key_exists('make', $saved) ? $saved['make'] : $bom->categories->pluck('name')->implode(', ');
                $defaultTechnical = collect(['Capacity' => $bom->capacity, 'Technology' => $bom->technology?->title, 'Warranty' => $bom->warranty?->title, 'Height' => $bom->height, 'Fitting Material' => $bom->fitting_material, 'Fitting Type' => $bom->fitting_type, 'Thickness' => $bom->thickness, 'Pipe Size' => $bom->size_of_pipe, 'Meter' => $bom->meter, 'Nos' => $bom->nos])
                    ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                    ->map(fn ($value, $label) => $label . ': ' . $value)->implode("\n");
                if (trim((string) $bom->description) !== '') $defaultTechnical .= ($defaultTechnical !== '' ? "\n" : '') . $bom->description;
                $technical = array_key_exists('technical', $saved) ? $saved['technical'] : $defaultTechnical;
            @endphp
            <tr>
                <td style="border:1px solid #333; padding:8px; vertical-align:top; font-weight:700;">
                    @if ($bom->image)<img src="{{ route('bom-products.image', $bom->id) }}" alt="" onerror="this.remove();" style="display:block; max-width:70px; max-height:55px; object-fit:contain; margin-bottom:5px;">@endif
                    {{ $bom->product_name }}
                </td>
                <td style="border:1px solid #333; padding:8px; vertical-align:top; white-space:pre-line;">{{ $make ?: '—' }}</td>
                <td style="border:1px solid #333; padding:8px; vertical-align:top; white-space:pre-line;">{{ $technical ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

