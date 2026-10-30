@if (!empty($showHeaderQuickEstimate))
    @include('crm.estimates.partials.quick-estimate-modals', [
        'customers' => $headerQuickEstimateCustomers,
        'templates' => $headerQuickEstimateTemplates,
        'bomProducts' => $headerQuickEstimateBomProducts,
        'categories' => $headerQuickEstimateCategories,
        'gstTaxes' => $headerQuickEstimateGstTaxes,
    ])
@endif
