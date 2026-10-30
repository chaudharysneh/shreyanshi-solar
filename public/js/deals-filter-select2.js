(function () {
    function init() {
        const $ = window.jQuery;
        if (!document.getElementById('dealsTable') || !$?.fn.select2) return;
        const fields = $('#moduleFilters select[data-list-filter]');
        fields.each(function () {
            const field = this;
            if ($(field).hasClass('select2-hidden-accessible')) return;
            $(field).select2({
                theme: 'bootstrap-5', width: '100%', minimumResultsForSearch: 0,
                dropdownCssClass: 'deal-filter-dropdown'
            });
            // The shared theme sets every Select2 container to 100% width.
            // Its body-mounted dropdown wrapper must not span the page from its left offset.
            $(field).data('select2').dropdown.$dropdownContainer.addClass('deal-filter-portal');
            // Select2 emits jQuery events; the list filters listen for native changes.
            $(field).on('select2:select.dealFilters select2:clear.dealFilters', function () {
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        document.getElementById('moduleFiltersClear')?.addEventListener('click', function () {
            // Run after the existing Clear handler resets the underlying selects.
            queueMicrotask(() => fields.trigger('change.select2'));
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
