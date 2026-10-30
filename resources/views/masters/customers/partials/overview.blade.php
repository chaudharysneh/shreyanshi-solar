<div class="px-3 px-md-4 py-3 border-bottom row g-3 m-0">
    <div class="col-md-4"><div class="small text-muted">Customer Source</div><div class="fw-semibold">{{ $customer->leadSource?->name ?: ($customer->source ?: '—') }}</div></div>
    @if($customer->referral_name)<div class="col-md-4"><div class="small text-muted">Referral Name</div><div class="fw-semibold">{{ $customer->referral_name }}</div></div>@endif
    @if($customer->channelPartner)<div class="col-md-4"><div class="small text-muted">Channel Partner</div><div class="fw-semibold">@if(auth()->user()->hasMatrixPermission('view_partners'))<a href="{{ route('partners.show',$customer->channelPartner) }}">{{ $customer->channelPartner->name }}</a>@else{{ $customer->channelPartner->name }}@endif</div><div class="small text-muted">{{ $customer->channelPartner->phone }}</div></div>@endif
</div>
<div class="tab-pane fade p-3 p-md-4" id="customer-pipeline" role="tabpanel" aria-labelledby="customer-pipeline-tab">
    <h6 class="fw-bold">Customer Pipeline</h6>
    <p class="text-muted small">Current stage: <strong>{{ $overview['stages']->firstWhere('id', $customer->pipeline_stage_id)?->name ?? 'Not assigned' }}</strong></p>
    <div class="row g-3">
        @forelse($overview['stages'] as $stage)
            @php
                $hue = fmod(abs($stage->id) * 137.508, 360);
                $current = (int) $customer->pipeline_stage_id === (int) $stage->id;
            @endphp
            <div class="col-6 col-md-4 col-xl-3">
                <div class="h-100 border rounded-3 p-3" style="border-color:hsl({{ $hue }},55%,76%) !important;background:hsl({{ $hue }},75%,94%);color:hsl({{ $hue }},70%,32%);{{ $current ? 'outline:2px solid currentColor;outline-offset:2px;' : '' }}" @if($current) aria-current="step" @endif>
                    <span class="small fw-bold">{{ $loop->iteration }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></span>
                    <div class="fw-semibold mt-1">{{ $stage->name }}</div>
                    @if($current)<span class="badge bg-white text-dark mt-2"><i class="bi bi-check-circle me-1"></i>Current stage</span>@endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No pipeline stages configured.</p>
        @endforelse
    </div>
</div>

<div class="tab-pane fade" id="customer-documents" role="tabpanel" aria-labelledby="customer-documents-tab">
    <div class="p-3 p-md-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h6 class="fw-bold mb-1">Customer Documents</h6><p class="small text-muted mb-0">Files attached to this customer and their accessible estimates, invoices, tasks, and workflow records.</p></div>
        @if(auth()->user()->hasMatrixPermission('edit_customers') && auth()->user()->can('update', $customer))
            <button type="button" class="btn btn-dark-blue" id="customerUploadDocuments"><i class="bi bi-cloud-arrow-up me-2"></i>Upload documents</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">Document</th><th>Source</th><th>Uploaded</th><th class="text-end pe-4">Action</th></tr></thead>
            <tbody>
                @forelse($overview['documents'] as $document)
                    <tr><td class="ps-4 text-break">@if($document['label'])<div class="small fw-semibold mb-1">{{ $document['label'] }}</div>@endif<i class="bi bi-file-earmark-text me-2"></i>{{ $document['name'] }}</td><td>{{ $document['source'] }}</td><td>{{ $document['uploaded_at'] ? \Carbon\Carbon::parse($document['uploaded_at'])->format('d M Y h:i A') : '--' }}</td><td class="text-end pe-4 text-nowrap">
                        @if($document['available'])
                            <a href="{{ route('masters.customers.related-documents.download', [$customer, $document['key']]) }}" class="btn btn-sm btn-outline-dark-blue" aria-label="Download {{ $document['name'] }}"><i class="bi bi-download me-1"></i>Download</a>
                        @else<span class="text-muted small">File unavailable</span>@endif
                        @if($document['can_delete'])
                            <button type="button" class="btn btn-sm btn-outline-danger customer-delete-document ms-1" data-url="{{ route('masters.customers.related-documents.destroy', [$customer, $document['key']]) }}" aria-label="Delete {{ $document['name'] }}"><i class="bi bi-trash me-1"></i>Delete</button>
                        @endif
                    </td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-5">No documents found for this customer.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($overview['sections'] as $key => $section)
    @continue(in_array($key, ['pipelines', 'bookings', 'messages', 'leads'], true))
<div class="tab-pane fade" id="customer-{{ $key }}" role="tabpanel" aria-labelledby="customer-{{ $key }}-tab">
    @if($key === 'estimates')
        <div class="px-3 px-md-4 pb-4">
            @foreach($section['records'] as $equipmentEstimate)
                @include('crm.estimates.partials.equipment-details',['estimate'=>$equipmentEstimate,'equipmentContext'=>['customer_id'=>$customer->id]])
            @endforeach
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">{{ $section['label'] }}</th><th>Status</th><th>Date</th><th>Amount / Assigned To</th><th class="text-end pe-4">Action</th></tr></thead>
            <tbody>
                @forelse($section['records'] as $record)
                    @php
                        $status = $record->status;
                        $date = $record->{$section['date']} ?? $record->created_at;
                    @endphp
                    <tr>
                        <td class="ps-4"><div class="fw-semibold">{{ $record->{$section['name']} ?: '#' . $record->getKey() }}</div>@if(isset($section['number']))<small class="text-muted">{{ $record->{$section['number']} }}</small>@endif</td>
                        <td><span class="badge bg-secondary">{{ $status ? ucwords(str_replace('_', ' ', (string) $status)) : '--' }}</span></td>
                        <td>{{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '--' }}</td>
                        <td>@if($record->amount !== null){{ number_format((float) $record->amount, 2) }}@else{{ method_exists($record, 'assignedUser') ? ($record->assignedUser?->name ?? '--') : '--' }}@endif</td>
                        <td class="text-end pe-4">@if($section['route'])<a href="{{ route($section['route'], $record->getKey()) }}" class="btn btn-sm btn-outline-dark-blue" aria-label="View {{ $record->{$section['name']} }}"><i class="bi bi-eye me-1"></i>View</a>@else<span class="text-muted">--</span>@endif</td>
                    </tr>
                @empty<tr><td colspan="5" class="text-center text-muted py-5">No {{ strtolower($section['label']) }} available for this customer.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach

<div class="tab-pane fade p-3 p-md-4" id="customer-workflow" role="tabpanel" aria-labelledby="customer-workflow-tab">
    <div class="row g-3">
        @foreach($overview['workflow'] as $type => $records)
        <div class="col-12 col-lg-6"><div class="border rounded-3 h-100 p-3">
            <h6 class="fw-bold">{{ preg_replace('/(?<!^)[A-Z]/', ' $0', $type) }}</h6>
            @forelse($records as $record)
                <dl class="row small mb-0 {{ !$loop->first ? 'border-top pt-3 mt-3' : '' }}">
                    <dt class="col-5">Status</dt><dd class="col-7">{{ $record->status ? ucwords(str_replace('_', ' ', $record->status)) : '--' }}</dd>
                    <dt class="col-5">Assigned To</dt><dd class="col-7">{{ $record->assignedTo?->name ?? '--' }}</dd>
                    @if($record->relationLoaded('currentStage'))<dt class="col-5">Stage</dt><dd class="col-7">{{ $record->currentStage?->name ?? '--' }}</dd>@endif
                    @foreach(['aspl_id' => 'ASPL ID', 'amount' => 'Amount', 'payment_type' => 'Payment Type', 'meter_number' => 'Meter Number', 'discom_status' => 'DISCOM Status', 'subsidy_amount' => 'Subsidy Amount', 'application_number' => 'Application Number', 'remarks' => 'Remarks'] as $field => $label)
                        @if($record->$field !== null && $record->$field !== '')<dt class="col-5">{{ $label }}</dt><dd class="col-7 text-break">{{ $record->$field }}</dd>@endif
                    @endforeach
                    <dt class="col-5">Updated</dt><dd class="col-7">{{ $record->updated_at?->format('d M Y h:i A') ?? '--' }}</dd>
                </dl>
            @empty<p class="small text-muted mb-0">No records for this customer.</p>@endforelse
        </div></div>
        @endforeach
    </div>
</div>

<div class="tab-pane fade p-3 p-md-4" id="customer-additional" role="tabpanel" aria-labelledby="customer-additional-tab">
    <dl class="row mb-0">
        <dt class="col-sm-4">Finance Plan</dt><dd class="col-sm-8">{{ $overview['financeMaster']?->name ?? '--' }}</dd>
        <dt class="col-sm-4">Installation Plan</dt><dd class="col-sm-8">{{ $overview['installationMaster']?->name ?? '--' }}</dd>
        <dt class="col-sm-4">Customer ID</dt><dd class="col-sm-8">{{ $customer->id }}</dd>
        <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $customer->is_active ? 'Active' : 'Inactive' }}</dd>
        <dt class="col-sm-4">Updated By</dt><dd class="col-sm-8">{{ $customer->updater?->name ?? '--' }}</dd>
        <dt class="col-sm-4">Updated At</dt><dd class="col-sm-8">{{ $customer->updated_at?->format('d M Y h:i A') ?? '--' }}</dd>
        @foreach($overview['customFields'] as $field)
            @php $value = $customer->customFieldValues->firstWhere('custom_field_id', $field->id)?->value; @endphp
            <dt class="col-sm-4">{{ $field->label }}</dt><dd class="col-sm-8 text-break">{{ is_array($value) ? implode(', ', $value) : ($value ?? '--') }}</dd>
        @endforeach
    </dl>
</div>

@if(auth()->user()->hasMatrixPermission('view_invoices'))
<div class="tab-pane fade p-3 p-md-4" id="customer-finance" role="tabpanel" aria-labelledby="customer-finance-tab">
    <h6 class="fw-bold">Customer & bank payment records</h6><p class="small text-muted">Funding and receipts remain separate for each invoice.</p>
    @php
        $financeInvoices=$overview['sections']['invoices']['records'];
        if($financeInvoices->isNotEmpty()) $financeInvoices->load(['financePlan','payments','currency']);
    @endphp
    @forelse($financeInvoices as $invoice)@include('crm.invoices.partials.finance-summary')@empty<p class="text-muted small">No accessible invoices for this customer.</p>@endforelse
</div>
@endif
