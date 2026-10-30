@include('crm.leads.partials.source-master-modal')
@include('crm.leads.partials.channel-partner-modal')
@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/lead-status-master.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ crm_asset('js/lead-source-master.js') }}"></script>
<script src="{{ crm_asset('js/lead-source-details.js') }}"></script>
@endpush
