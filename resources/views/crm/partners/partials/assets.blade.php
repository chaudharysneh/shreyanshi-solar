@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.partner-kpi{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:22px;background:var(--crm-bg-card,#fff);border:1px solid var(--crm-border,#e2e8f0);border-left:4px solid var(--partner-tone);border-radius:16px;box-shadow:var(--crm-shadow-sm)}
.partner-kpi-icon{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;border-radius:12px;color:var(--partner-tone);background:color-mix(in srgb,var(--partner-tone) 10%,transparent);font-size:1.3rem}
.partner-form-section{padding:20px;border:1px solid var(--crm-border,#e2e8f0);border-radius:14px;background:var(--crm-bg-card,#fff)}
.partner-form-section h6{margin-bottom:18px;font-weight:700}
#partnerList[aria-busy="true"]{opacity:.55;pointer-events:none}
 .partner-page-size {white-space:nowrap;}
#partnerFilters .partner-page-size .select2-container {width:85px!important;flex:0 0 85px;}
#partnerPerPage {width:85px!important;flex:0 0 85px;}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ crm_asset('js/partners.js') }}"></script>
@endpush
