@if (!empty($showHeaderQuickEstimate))
    <link rel="stylesheet" href="{{ crm_asset('css/estimates.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    @include('crm.estimates.partials.quick-estimate-wizard-styles')
@endif
