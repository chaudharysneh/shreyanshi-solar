@if (!empty($showHeaderQuickEstimate))
    @php
        $templateComments = ($headerQuickEstimateTemplates ?? collect())->mapWithKeys(function ($template) {
            $formData = is_array($template->form_data) ? $template->form_data : (json_decode($template->form_data ?? '[]', true) ?: []);
            $comment = is_array($formData['estimate_comment'] ?? null) ? $formData['estimate_comment'] : [];
            return [
                (string) $template->id => [
                    'active' => (int) ($comment['active'] ?? 0),
                    'content' => (string) ($comment['content'] ?? ''),
                ],
            ];
        });
    @endphp
    <script>
        window.estimateTemplateComments = @json($templateComments);
        window.subsidiesData = @json($headerQuickEstimateSubsidies ?? []);
        window.estimateBomQuickAddConfig = {
            storeUrl: @json(route('api.bom-products.store')),
            makeStoreUrl: @json(route('api.make.store'))
        };
        window.estimatePriceMode = @json(\App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom');
        window.crmUserPermissions = window.crmUserPermissions || {};
        window.crmUserPermissions.estimates = {
            view: @json(auth()->user()?->hasMatrixPermission('view_estimates')),
            create: @json(auth()->user()?->hasMatrixPermission('create_estimates')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_estimates')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_estimates')),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/estimates.js') }}"></script>
@endif
