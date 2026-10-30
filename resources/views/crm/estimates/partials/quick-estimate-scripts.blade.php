@php
    $templateComments = ($templates ?? collect())->mapWithKeys(function ($template) {
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
    window.subsidiesData = @json($subsidies ?? []);
    window.estimateBomQuickAddConfig = {
        storeUrl: @json(route('api.bom-products.store')),
        makeStoreUrl: @json(route('api.make.store'))
    };
    window.estimatePriceMode = @json($estimatePriceMode ?? (\App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom'));
</script>
