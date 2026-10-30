(function () {
    function enhance() {
        const $ = window.jQuery;
        if (!$?.fn.select2) return;
        // Only full-size index filters; pagination, forms and modal fields stay as they are.
        const fields = $('#moduleFilters select[data-list-filter], #leadFilters select, #customerStatus, #customerType');
        fields.each(function () {
            const field = this;
            if ($(field).hasClass('select2-hidden-accessible')) return;
            $(field).addClass('crm-index-filter-select').select2({
                theme: 'bootstrap-5', width: '100%', minimumResultsForSearch: 0,
                dropdownCssClass: 'crm-index-filter-dropdown'
            });
            $(field).data('select2').dropdown.$dropdownContainer.addClass('crm-index-filter-portal');
            $(field).on('select2:select.indexFilters select2:clear.indexFilters', function () {
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        ['moduleFiltersClear', 'clearLeadFilters', 'clearCustomerFilters'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', () => {
                queueMicrotask(() => fields.trigger('change.select2'));
            });
        });
    }
    function init() {
        if (window.jQuery?.fn.select2) { enhance(); return; }
        // Some index pages do not load Select2 for users without BOM permissions.
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = enhance;
        document.head.append(script);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
