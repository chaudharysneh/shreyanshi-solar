@php
    $selectedSource = old('lead_source_id', isset($lead) ? $lead->lead_source_id : '');
@endphp
@if($sourceSideBySide ?? false)
<div class="{{ $sourceColumnClass ?? 'col-md-6' }}" id="leadSourceColumn">
    <label for="lead_source_id" class="form-label fw-semibold">{{ $sourceLabel ?? 'Customer Source' }}</label>
@endif
<div class="d-flex align-items-start gap-2 lead-source-master" data-url="{{ url('/api/masters/lead-sources') }}" data-can-manage="{{ auth()->user()->isAdmin() ? '1' : '0' }}">
    <div class="flex-grow-1" style="min-width:0">
        <select name="lead_source_id" id="lead_source_id" class="form-select @error('lead_source_id') is-invalid @enderror">
            <option value="">Select Lead Source</option>
            @foreach($sources as $source)
                <option value="{{ $source->id }}" data-master-id="{{ $source->id }}" data-kind="{{ $source->kind }}" @selected((string) $selectedSource === (string) $source->id)>{{ $source->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="source" id="source" value="{{ old('source', isset($lead) ? $lead->source : '') }}">
        <div class="invalid-feedback" id="lead_source_id-error"></div>
        <div class="text-danger small" id="source-error"></div>
    </div>
    @if(auth()->user()->isAdmin())
        <button type="button" class="btn btn-dark-blue lead-source-add flex-shrink-0" aria-label="Add lead source" title="Add lead source"><i class="bi bi-plus-lg"></i></button>
    @endif
</div>
@if($sourceSideBySide ?? false)
</div>
@endif
<div class="{{ ($sourceSideBySide ?? false) ? ($sourceColumnClass ?? 'col-md-6') : 'mt-3' }} d-none" id="leadReferralField">
    <label for="referral_name" class="form-label fw-semibold">Referral Name <span class="text-danger">*</span></label>
    <input type="text" id="referral_name" name="referral_name" class="form-control" maxlength="255" placeholder="Enter referral name" value="{{ old('referral_name', isset($lead) ? $lead->referral_name : '') }}" disabled>
    <div class="invalid-feedback" id="referral_name-error"></div>
</div>
<div class="{{ ($sourceSideBySide ?? false) ? ($sourceColumnClass ?? 'col-md-6') : 'mt-3' }} d-none" id="leadPartnerField">
    <label for="channel_partner_id" class="form-label fw-semibold">Partner Name <span class="text-danger">*</span></label>
    <div class="d-flex gap-2 align-items-start">
        <div class="flex-grow-1" style="min-width:0">
            <select id="channel_partner_id" name="channel_partner_id" class="form-select" data-url="{{ url('/api/channel-partners') }}" disabled>
                <option value="">Search channel partners</option>
                @php $selectedPartner = \App\Models\ChannelPartner::find(old('channel_partner_id', isset($lead) ? $lead->channel_partner_id : null)); @endphp
                @if($selectedPartner)<option value="{{ $selectedPartner->id }}" selected>{{ $selectedPartner->name }}</option>@endif
            </select>
            <div class="invalid-feedback" id="channel_partner_id-error"></div>
        </div>
        <button type="button" class="btn btn-dark-blue flex-shrink-0" id="addLeadChannelPartner" title="Add channel partner" aria-label="Add channel partner"><i class="bi bi-plus-lg"></i></button>
    </div>
</div>
