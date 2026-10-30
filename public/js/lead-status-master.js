(function ($) {
    'use strict';
    $(function () {
        const $container = $('.lead-status-master');
        if (!$container.length || !$.fn.select2) return;
        const $select = $container.find('select');
        const canManage = $container.attr('data-can-manage') === '1';
        const url = $container.attr('data-url');
        const modalElement = document.getElementById('leadStatusMasterModal');
        const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
        const $form = $('#leadStatusMasterForm');
        const $name = $('#leadStatusMasterName');
        const $error = $('#leadStatusMasterError');
        let editingOption = null;
        let deleting = false;

        function openEditor(option) {
            $select.select2('close');
            editingOption = option || null;
            $('#leadStatusMasterTitle').text(option ? 'Edit Lead Status' : 'Add Lead Status');
            $name.val(option ? option.text : '');
            $error.text('');
            modal.show();
        }
        function errorMessage(xhr) {
            const response = xhr.responseJSON || {};
            return response.errors && response.errors.name ? response.errors.name[0]
                : response.message || 'Unable to save changes. Please try again.';
        }
        function request(method, endpoint, data) {
            return $.ajax({
                url: endpoint, method: method, data: data,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' }
            });
        }
        function deleteOption(option) {
            if (deleting) return;
            $select.select2('close');
            deleting = true;
            request('DELETE', url + '/' + option.dataset.masterId)
                .done(function () {
                    const wasSelected = option.selected;
                    option.remove();
                    if (wasSelected) {
                        $select.val($select.find('option[value="new"]').length ? 'new' : '');
                        $select.trigger('change');
                    } else {
                        $select.trigger('change.select2');
                    }
                })
                .fail(function (xhr) { window.alert(errorMessage(xhr)); })
                .always(function () { deleting = false; });
        }
        function actionButton(icon, label, callback) {
            return $('<button>', { type: 'button', class: 'lead-status-option-action', title: label, 'aria-label': label })
                .append($('<i>', { class: icon, 'aria-hidden': 'true' }))
                // Select2 selects options on mouseup; these actions must not select the row.
                .on('mousedown mouseup', function (event) { event.preventDefault(); event.stopPropagation(); })
                .on('click', function (event) { event.preventDefault(); event.stopPropagation(); callback(); });
        }
        $select.select2({
            theme: 'bootstrap-5', width: '100%', dropdownCssClass: 'lead-status-master-dropdown',
            templateResult: function (item) {
                if (!item.id || !item.element || !canManage) return item.text;
                const option = item.element;
                const $row = $('<span>', { class: 'lead-status-option' });
                $row.append($('<span>', { class: 'lead-status-option-label', text: item.text }));
                const $actions = $('<span>', { class: 'lead-status-option-actions' });
                $actions.append(actionButton('bi bi-pencil', 'Edit ' + item.text, function () { openEditor(option); }));
                $actions.append(actionButton('bi bi-x-lg', 'Delete ' + item.text, function () { deleteOption(option); }));
                return $row.append($actions);
            }
        });
        $select.on('select2:select', function () {
            if (this.classList.contains('js-status-comment-trigger')) {
                this.dispatchEvent(new Event('crm:status-change'));
            }
        });
        $container.find('.lead-status-add').on('click', function () { openEditor(null); });
        $(modalElement).on('shown.bs.modal', function () { $name.trigger('focus'); });
        $form.on('submit', function (event) {
            event.preventDefault();
            const name = $name.val().trim();
            if (!name) { $error.text('Please enter a status name.'); $name.trigger('focus'); return; }
            const $buttons = $form.find('button');
            if ($form.data('saving')) return;
            $form.data('saving', true);
            $buttons.prop('disabled', true);
            $error.text('');
            request(editingOption ? 'PUT' : 'POST', url + (editingOption ? '/' + editingOption.dataset.masterId : ''), { name: name })
                .done(function (response) {
                    const status = response.data;
                    if (editingOption) {
                        editingOption.text = status.name;
                        // Select2 caches option data; update it without firing a lead status change.
                        const cached = $select.data('select2').dataAdapter.item($(editingOption));
                        if (cached) cached.text = status.name;
                        $select.trigger('change.select2');
                    } else {
                        const option = new Option(status.name, status.code, true, true);
                        option.dataset.masterId = status.id;
                        $select.append(option).trigger('change');
                        if ($select.hasClass('js-status-comment-trigger')) {
                            $(modalElement).one('hidden.bs.modal', function () {
                                $select[0].dispatchEvent(new Event('crm:status-change'));
                            });
                        }
                    }
                    $form.data('saving', false);
                    modal.hide();
                })
                .fail(function (xhr) { $error.text(errorMessage(xhr)); })
                .always(function () { $form.data('saving', false); $buttons.prop('disabled', false); });
        });
        $(modalElement).on('hide.bs.modal', function (event) {
            if ($form.data('saving')) event.preventDefault();
        });
    });
})(jQuery);
