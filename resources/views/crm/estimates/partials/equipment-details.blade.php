@php
    $equipmentContext=$equipmentContext ?? [];
    $equipmentPermission=isset($equipmentContext['invoice_id']) ? 'edit_invoices' : (isset($equipmentContext['customer_id']) ? 'edit_customers' : 'edit_estimates');
    $equipmentUrl=url('/api/equipment/estimates/'.$estimate->estimate_id).($equipmentContext ? '?'.http_build_query($equipmentContext) : '');
@endphp
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><h6 class="fw-bold mb-0"><i class="bi bi-upc-scan me-2"></i>Equipment Details · Estimate #{{ $estimate->estimate_no }} <span class="badge ms-2 equipment-progress" data-progress="{{ $estimate->equipment_progress }}">{{ $estimate->equipment_progress }}</span></h6>@if(auth()->user()->hasMatrixPermission($equipmentPermission))<button type="button" class="btn btn-sm btn-dark-blue" data-estimate-id="{{ $estimate->estimate_id }}" data-equipment-url="{{ $equipmentUrl }}"><i class="bi bi-pencil me-1"></i>Edit Equipment Details</button>@endif</div>
    <div class="card-body p-4">
        <p class="small text-muted">Configuration: {{ $estimate->equipment_serials['panels_per_kw'] ?? 2 }} panel(s) per kW. Inverters are recorded separately.</p>
        <div class="row g-4">
            @foreach(['panels' => 'Panels', 'inverters' => 'Inverters'] as $type => $label)
                <div class="col-md-6">
                    <h6 class="fw-bold">{{ $label }}</h6>
                    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>#</th><th>Serial No.</th></tr></thead><tbody>
                        @php($serialCount = $type === 'panels' ? (int) ceil($estimate->quantity*($estimate->equipment_serials['panels_per_kw'] ?? 2)) : count($estimate->equipment_serials['inverters'] ?? []))
                        @for($index=0;$index<$serialCount;$index++)
                            <tr><td>{{ $index+1 }}</td><td>{{ ($estimate->equipment_serials[$type][$index] ?? '') ?: '-' }}</td></tr>
                        @endfor
                        @if(!$serialCount)<tr><td colspan="2" class="text-muted">No entries recorded.</td></tr>@endif
                    </tbody></table></div>
                </div>
            @endforeach
        </div>
        @if(!empty($estimate->equipment_serials['notes']))<div class="small text-muted fw-semibold">Remarks / Notes</div><p class="mb-0 text-break" style="white-space:pre-wrap">{{ $estimate->equipment_serials['notes'] }}</p>@endif
    </div>
</div>
