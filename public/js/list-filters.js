window.moduleListFilters = {
    values() {
        const values = {};
        document.querySelectorAll('[data-list-filter]').forEach(field => {
            if (field.value) values[field.dataset.listFilter] = field.value;
        });
        document.querySelectorAll('[data-list-date]').forEach(field => {
            const picker = field._flatpickr;
            if (picker && picker.selectedDates.length === 2) {
                values[field.dataset.listDate + '_from'] = picker.formatDate(picker.selectedDates[0], 'Y-m-d');
                values[field.dataset.listDate + '_to'] = picker.formatDate(picker.selectedDates[1], 'Y-m-d');
            }
        });
        return values;
    },
    bind(reload, searchId) {
        let timer;
        document.querySelectorAll('[data-list-date]').forEach(field => {
            if (typeof window.flatpickr !== 'function') return;
            flatpickr(field, {
            mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
            onChange(dates) { if (dates.length !== 1) reload(1); }
            });
        });
        document.querySelectorAll('[data-list-filter]').forEach(field => {
            field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', () => {
                clearTimeout(timer); timer = setTimeout(() => reload(1), field.tagName === 'SELECT' ? 0 : 350);
            });
        });
        document.getElementById('moduleFiltersClear')?.addEventListener('click', () => {
            clearTimeout(timer);
            document.querySelectorAll('[data-list-filter]').forEach(field => { if (field.dataset.listFilter !== 'per_page') field.value = ''; });
            document.querySelectorAll('[data-list-date]').forEach(field => { if (field._flatpickr) field._flatpickr.clear(false); else field.value = ''; });
            const search = document.getElementById(searchId);
            if (search) search.value = '';
            reload(1);
        });
    }
};
