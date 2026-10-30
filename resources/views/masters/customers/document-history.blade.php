@extends('layouts.app')
@section('page_title', 'Document History')
@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
            <div><h1 class="h5 fw-bold mb-1">{{ $record->type === 'solar' ? 'Solar Documents' : 'Subsidy Bond' }}</h1><p class="text-muted small mb-0">{{ $customer->name }} &middot; {{ $selected->filename }}</p></div>
            <a class="btn btn-outline-dark-blue" href="{{ route('masters.customers.show',$customer) }}#customer-{{ $record->type }}-documents">Back to Customer</a>
        </div>
        <div class="card-body p-4">
            <form method="get" class="row g-2 align-items-end mb-4">
                <div class="col-md-8"><label class="form-label fw-semibold" for="documentVersion">Document History</label><select id="documentVersion" name="version" class="form-select">
                    @foreach($versions as $version)<option value="{{ $version->version }}" @selected($version->id === $selected->id)>Version {{ $version->version }}{{ $loop->first ? ' (Latest)' : '' }} - {{ $version->created_at->format('d M Y, h:i A') }} - {{ $version->creator?->name ?? 'User' }}</option>@endforeach
                </select></div>
                <div class="col-md-4"><button type="submit" class="btn btn-dark-blue w-100">View Version</button></div>
            </form>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="badge bg-secondary">Version {{ $selected->version }}</span><span class="small text-muted">Saved {{ $selected->created_at->format('d M Y, h:i A') }}</span></div>
            <div class="table-responsive"><table class="table align-middle"><tbody>
                @foreach($selected->fields as $field=>$value)
                    <tr><th class="text-muted" style="width:35%">{{ match($field) { 'date_month_short'=>'Date Month Short', 'no_of_pv_module'=>'No. Of PV Module', default=>\Illuminate\Support\Str::headline($field) } }}</th><td class="text-break" style="white-space:pre-wrap">{{ $value ?: '-' }}</td></tr>
                @endforeach
            </tbody></table></div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-dark-blue" href="{{ route('customers.form-documents.download',[$customer,$record->id,$selected->version,'format'=>'pdf']) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
                    <a class="btn btn-outline-dark-blue" href="{{ route('customers.form-documents.download',[$customer,$record->id,$selected->version,'format'=>'word']) }}"><i class="bi bi-file-earmark-word me-1"></i>Download Word</a>
                @if($selected->aadhaar_path)<a class="btn btn-outline-dark-blue" href="{{ route('customers.form-documents.download',[$customer,$record->id,$selected->version,'format'=>'aadhaar']) }}">Download Aadhaar Image</a>@endif
            </div>
        </div>
    </div>
</div>
@endsection
