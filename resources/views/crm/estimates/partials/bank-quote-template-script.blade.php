@push('scripts')
<script>
    $(function () {
        const type = $('#type');
        const templates = $('#template_id');
        let previousTemplate = templates.val();
        function syncBankQuoteTemplate() {
            const isBank = type.val() === 'bank quote';
            const bankOption = templates.find('[data-bank-quote-template]');
            if (isBank && templates.val() !== 'bank_quote') previousTemplate = templates.val();
            templates.find('option').not(bankOption).prop('disabled', isBank);
            bankOption.prop('disabled', !isBank).prop('hidden', !isBank);
            if (isBank) {
                templates.val('bank_quote').trigger('change');
                $('#edit_template_link').addClass('d-none');
            } else if (templates.val() === 'bank_quote' || !templates.val()) {
                templates.val(previousTemplate === 'bank_quote' ? '' : previousTemplate).trigger('change');
            }
        }
        type.on('change', syncBankQuoteTemplate);
        syncBankQuoteTemplate();
    });
</script>
@endpush
