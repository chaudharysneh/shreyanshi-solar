(function ($) {
    'use strict';
    $(function () {
        if ($.fn.select2) $('.partner-select').select2({ theme: 'bootstrap-5', width: 'resolve' });
        const $type = $('#commission_type');
        function syncCommission() {
            const percent = $type.val() === 'percent';
            $('#commission_value').attr('max', percent ? '100' : '999999999999.99');
            $('#commissionValueHelp').text(percent ? 'Percentage from 0 to 100.' : 'Fixed amount per eligible referral.');
        }
        if ($type.length) { $type.on('change', syncCommission); syncCommission(); }
        $('#partnerForm').on('submit', function () { $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...'); });

        const filters = document.getElementById('partnerFilters');
        const list = document.getElementById('partnerList');
        if (!filters || !list) return;
        let pending, timer;
        async function refresh(url) {
            pending?.abort();
            pending = new AbortController();
            const signal = pending.signal;
            list.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal });
                if (!response.ok) throw new Error('Unable to load partners. Please try again.');
                const data = await response.json();
                list.innerHTML = data.html;
                document.querySelectorAll('[data-partner-kpi]').forEach((node, index) => { node.textContent = Number(data.kpis[index]).toLocaleString(undefined, { minimumIntegerDigits: 2 }); });
                history.replaceState(null, '', url);
                const exportLink = document.getElementById('partnerExport');
                exportLink.search = new URL(url, location.href).search;
            } catch (error) {
                if (error.name !== 'AbortError' && window.showAlert) window.showAlert('error', error.message);
            } finally {
                if (!signal.aborted) list.removeAttribute('aria-busy');
            }
        }
        function filterUrl() { return filters.action + '?' + new URLSearchParams(new FormData(filters)); }
        $(filters).on('change', 'select', () => { clearTimeout(timer); refresh(filterUrl()); });
        $('#partnerSearch').on('input', () => { clearTimeout(timer); timer = setTimeout(() => refresh(filterUrl()), 300); });
        $(filters).on('submit', event => { event.preventDefault(); clearTimeout(timer); refresh(filterUrl()); });
        $('#clearPartnerFilters').on('click', function () {
            clearTimeout(timer);
            filters.reset();
            $(filters).find('input[name="search"]').val('');
            $(filters).find('select').val('').trigger('change.select2');
            $('#partnerPerPage').val('10').trigger('change.select2');
            refresh(filterUrl());
        });
        list.addEventListener('click', event => {
            const link = event.target.closest('.pagination a');
            if (link) { event.preventDefault(); clearTimeout(timer); refresh(link.href); }
        });
    });
})(jQuery);
