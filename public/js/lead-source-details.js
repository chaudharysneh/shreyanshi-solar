(function ($) {
    'use strict';
    $(function () {
        const $source = $('#lead_source_id');
        const $partner = $('#channel_partner_id');
        if (!$source.length || !$partner.length) return;

        function syncSourceFields() {
            const option = $source[0].selectedOptions[0];
            const kind = option?.dataset.kind;
            if ($('#leadStatusColumn').length) {
                const hasDetails = kind === 'referral' || kind === 'channel_partner';
                $('#leadSourceColumn, #leadStatusColumn')
                    .toggleClass('col-md-4', hasDetails)
                    .toggleClass('col-md-6', !hasDetails);
            }
            $('#source').val($source.val() ? option.textContent.trim() : '');
            $('#leadReferralField').toggleClass('d-none', kind !== 'referral');
            $('#referral_name').prop('disabled', kind !== 'referral').prop('required', kind === 'referral');
            $('#leadPartnerField').toggleClass('d-none', kind !== 'channel_partner');
            $partner.prop('disabled', kind !== 'channel_partner').prop('required', kind === 'channel_partner');
        }
        $source.on('change', syncSourceFields);
        syncSourceFields();

        if ($.fn.select2) {
            $partner.select2({
                theme: 'bootstrap-5', width: '100%', placeholder: 'Search channel partners', allowClear: true,
                ajax: {
                    url: $partner.data('url'), dataType: 'json', delay: 250,
                    data: params => ({ q: params.term || '', page: params.page || 1 }),
                },
            });
        }

        const modalElement = document.getElementById('leadChannelPartnerModal');
        document.body.appendChild(modalElement);
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('leadChannelPartnerForm');
        $('#addLeadChannelPartner').on('click', function () {
            $partner.select2('close');
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
            $('#leadChannelPartnerError').text('');
            modal.show();
        });
        modalElement.addEventListener('shown.bs.modal', () => form.elements.name.focus());
        $(form).on('submit', function (event) {
            event.preventDefault();
            if (form.dataset.saving === '1') return;
            if (!form.reportValidity()) return;
            form.dataset.saving = '1';
            const $buttons = $(form).find('button').prop('disabled', true);
            $(form).find('.is-invalid').removeClass('is-invalid');
            $('#leadChannelPartnerError').text('');
            $.ajax({
                url: $partner.data('url'), method: 'POST', data: $(form).serialize(),
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (response) {
                const partner = response.data;
                $partner.append(new Option(partner.name, partner.id, true, true)).trigger('change');
                modal.hide();
            }).fail(function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.entries(errors).forEach(function ([name, messages]) {
                    const input = form.elements.namedItem(name);
                    if (input) $(input).addClass('is-invalid');
                    $(form).find('[data-error="' + name + '"]').text(messages[0]);
                });
                $('#leadChannelPartnerError').text(xhr.responseJSON?.message || 'Unable to save partner. Please try again.');
            }).always(function () {
                form.dataset.saving = '0';
                $buttons.prop('disabled', false);
            });
        });
    });
})(jQuery);
