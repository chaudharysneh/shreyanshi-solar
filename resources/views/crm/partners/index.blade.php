@extends('layouts.app')
@section('page_title', 'Manage Partners')
@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h4 fw-bold mb-1">Manage Partners</h1><p class="text-muted small mb-0">Manage channel partners, referrals, and commission settings.</p></div>
        <div class="d-flex gap-2">
            <a href="{{ route('partners.export', request()->query()) }}" id="partnerExport" class="btn btn-outline-dark-blue"><i class="bi bi-download me-1"></i>Export</a>
            @if(auth()->user()->hasMatrixPermission('create_partners'))<a href="{{ route('partners.create') }}" class="btn btn-dark-blue"><i class="bi bi-plus-lg me-1"></i>Add Partner</a>@endif
        </div>
    </div>
    <div class="row g-3 mb-4">
        @foreach($kpis as $label => $value)
        <div class="col-6 col-xl-3"><div class="partner-kpi h-100" style="--partner-tone:{{ ['#3b5bdb','#059669','#d97706','#7c3aed'][$loop->index] }}"><div><div class="text-muted small fw-semibold mb-2">{{ $label }}</div><div class="h3 fw-bold mb-0" data-partner-kpi>{{ str_pad(number_format($value), 2, '0', STR_PAD_LEFT) }}</div></div><span class="partner-kpi-icon"><i class="bi {{ ['bi-people','bi-person-check','bi-megaphone','bi-check2-circle'][$loop->index] }}"></i></span></div></div>
        @endforeach
    </div>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header py-3 px-4">
            <form id="partnerFilters" action="{{ route('partners.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label small fw-semibold" for="partnerSearch">Search partners</label><input id="partnerSearch" name="search" class="form-control" placeholder="Name, code, phone, email or company" value="{{ request('search') }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold" for="partnerStatus">Status</label><select id="partnerStatus" name="status" class="form-select partner-select"><option value="">All statuses</option>@foreach(['active'=>'Active','inactive'=>'Inactive'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold" for="partnerCommission">Commission</label><select id="partnerCommission" name="commission_type" class="form-select partner-select"><option value="">All types</option><option value="fixed" @selected(request('commission_type') === 'fixed')>Fixed</option><option value="percent" @selected(request('commission_type') === 'percent')>Percent</option></select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold" for="partnerStaff">Assigned staff</label><select id="partnerStaff" name="assigned_user_id" class="form-select partner-select"><option value="">All staff</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned_user_id') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><button type="button" class="btn btn-dark-blue w-100" id="clearPartnerFilters" title="Clear filters" aria-label="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></button></div>
                <div class="col-auto ms-auto d-flex align-items-center gap-2 partner-page-size"><label for="partnerPerPage" class="small text-muted">Show</label><select id="partnerPerPage" name="per_page" class="form-select form-select-sm partner-select" style="width:85px">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected(request('per_page',10) == $size)>{{ $size }}</option>@endforeach</select><span class="small text-muted">per page</span></div>
            </form>
        </div>
        <div id="partnerList" aria-live="polite">@include('crm.partners.partials.table')</div>
    </div>
</div>
@endsection
@include('crm.partners.partials.assets')
