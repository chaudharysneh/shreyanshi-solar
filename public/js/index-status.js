(function () {
    'use strict';
    const modules = ['leads', 'follow-ups', 'meetings', 'projects', 'pipelines', 'services', 'products', 'deals', 'estimates', 'invoices', 'tickets', 'users', 'tasks', 'customers', 'stages', 'lead-sources', 'statuses', 'finance-masters', 'installation-masters', 'countries', 'cities', 'agents', 'currencies', 'suppliers', 'hotels', 'room-categories', 'travel-types', 'transport-types'];
    const module = location.pathname.split('/').filter(Boolean).pop();
    let menu;
    function tone(label) {
        const value = label.toLowerCase();
        if (/inactive|cancel|reject|lost|overdue|not interested/.test(value)) return 'red';
        if (/completed|paid|approved|active|resolved|won|interested/.test(value) && !/unpaid/.test(value)) return 'green';
        if (/progress|working|pending|partial|unpaid|paused/.test(value)) return 'amber';
        if (/qualified|ready|scheduled/.test(value)) return 'purple';
        if (/closed/.test(value)) return 'slate';
        if (/new|open/.test(value)) return 'blue';
        return ['blue', 'purple', 'amber', 'green', 'slate'][Array.from(value).reduce((sum, c) => sum + c.charCodeAt(0), 0) % 5];
    }
    function close() {
        menu?.remove(); menu = null;
        document.querySelectorAll('.index-status-pill[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));
    }
    function message(text) {
        let notice = document.getElementById('indexStatusNotice');
        if (!notice) { notice = document.createElement('div'); notice.id = 'indexStatusNotice'; notice.setAttribute('role', 'alert'); document.body.append(notice); }
        notice.textContent = text; notice.hidden = false;
        setTimeout(() => { notice.hidden = true; }, 6000);
    }
    async function request(id, method = 'GET', status, comment, followUpAt, extra = {}) {
        const response = await fetch(`/api/index-status/${module}/${id}`, {
            method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            ...(method === 'PATCH' ? { body: JSON.stringify({ status, comment, follow_up_at: followUpAt, ...extra }) } : {})
        });
        const data = await response.json();
        if (!response.ok || data.success === false) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to update status.');
        return data;
    }
    function decorate(container, id, editable) {
        if (container.dataset.indexStatusReady || container.colSpan > 1) return;
        const label = container.textContent.trim();
        if (!label || (label === '-' && module !== 'customers')) return;
        container.dataset.indexStatusReady = '1';
        const button = document.createElement(editable ? 'button' : 'span');
        const existingPipelineBadge = container.querySelector('[id^="pipeline-badge-"]');
        if (existingPipelineBadge) button.id = existingPipelineBadge.id;
        button.className = `index-status-pill index-status-pill--${tone(label)}`;
        const text = document.createElement('span'); text.textContent = label.replace(/_/g, ' '); button.append(text); button.title = label;
        if (editable) {
            button.type = 'button'; button.dataset.recordId = id;
            button.setAttribute('aria-haspopup', 'menu'); button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', `Change status: ${label}`); button.classList.add('index-status-editable');
        }
        container.replaceChildren(button);
    }
    function scan() {
        document.querySelectorAll('table').forEach(table => {
            if (table.closest('.modal')) return;
            const header = module === 'customers' ? 'pipeline stage' : 'status';
            const column = Array.from(table.querySelectorAll('thead tr:first-child th')).findIndex(th => th.textContent.trim().toLowerCase() === header);
            if (column < 0) return;
            table.querySelectorAll('tbody > tr').forEach(row => {
                if (row.querySelector('td[colspan]')) return;
                const links = Array.from(row.querySelectorAll('a[href]'));
                const match = links.map(a => new URL(a.href).pathname.match(new RegExp(`/${module}/(\\d+)(?:/edit)?$`))).find(Boolean);
                const id = match?.[1] || row.querySelector('[data-user-id]')?.dataset.userId || row.querySelector('[data-task-id]')?.dataset.taskId || (module === 'stages' ? row.querySelector('[data-stage-id]')?.dataset.stageId : null);
                const editable = !!id && (module === 'tasks' ? !!row.querySelector('.quick-task-status-btn')
                    : module === 'users' || links.some(a => a.pathname.endsWith(`/${id}/edit`)) || !!row.querySelector('.estimate-status-btn, .change-status-btn') || (module === 'stages' && !!row.querySelector('.editStage')));
                if (row.children[column]) decorate(row.children[column], id, editable);
                const details = row.nextElementSibling;
                if (details?.querySelector('td[colspan]')) details.querySelectorAll('.expand-label').forEach(label => {
                    if (label.textContent.replace(/:/g, '').trim().toLowerCase() === header) {
                        const value = label.parentElement.querySelector('.expand-value'); if (value) decorate(value, id, editable);
                    }
                });
            });
        });
    }
    function commentModal(button, option) {
        close();
        const element = document.createElement('div'); element.className = 'modal fade'; element.tabIndex = -1;
        element.setAttribute('aria-labelledby', 'indexStatusCommentTitle'); element.setAttribute('aria-modal', 'true'); element.setAttribute('role', 'dialog');
        element.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="indexStatusCommentTitle">Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><form><div class="modal-body"><p class="index-status-selection"></p><label for="indexStatusComment" class="form-label fw-semibold">Comment <span class="text-danger">*</span></label><textarea class="form-control" id="indexStatusComment" rows="3" required maxlength="2000" placeholder="Add a comment about this status change"></textarea><div class="index-status-error text-danger small mt-2" role="alert"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark-blue">Update Status</button></div></form></div></div>';
        element.querySelector('.index-status-selection').textContent = `New status: ${option.label}`;
        if (module === 'follow-ups' && option.value === 'resheduled') {
            const schedule = document.createElement('div'); schedule.className = 'mb-3';
            schedule.innerHTML = '<label for="indexStatusRescheduleAt" class="form-label fw-semibold">Reschedule Date &amp; Time <span class="text-danger">*</span></label><input type="datetime-local" id="indexStatusRescheduleAt" class="form-control" required><div class="form-text">Timezone: Asia/Kolkata</div>';
            element.querySelector('.index-status-selection').after(schedule);
        }
        document.body.append(element);
        const modal = bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static' });
        const input = element.querySelector('textarea'); const error = element.querySelector('.index-status-error'); const save = element.querySelector('[type="submit"]');
        let saving = false;
        element.addEventListener('shown.bs.modal', () => input.focus());
        element.addEventListener('hide.bs.modal', event => { if (saving) event.preventDefault(); });
        element.addEventListener('hidden.bs.modal', () => { modal.dispose(); element.remove(); button.focus(); });
        element.querySelector('form').addEventListener('submit', async event => {
            event.preventDefault(); if (saving) return;
            const comment = input.value.trim();
            if (!comment) { error.textContent = 'Please enter a comment.'; input.focus(); return; }
            saving = true; save.disabled = true; save.textContent = 'Saving...'; error.textContent = '';
            try { await request(button.dataset.recordId, 'PATCH', option.value, comment, element.querySelector('#indexStatusRescheduleAt')?.value); location.reload(); }
            catch (exception) { error.textContent = exception.message; saving = false; save.disabled = false; save.textContent = 'Update Status'; }
        });
        modal.show();
    }
    async function open(button) {
        if (module === 'invoices' && window.openInvoiceFinance) { await window.openInvoiceFinance(button.dataset.recordId, 'payment'); return; }
        close();
        const id = button.dataset.recordId;
        // Tasks retain the existing comment, location and document collection flow.
        const taskAction = module === 'tasks' ? document.querySelector(`.quick-task-status-btn[data-task-id="${id}"]`) : null;
        button.disabled = true;
        try {
            const data = taskAction ? {
                value: '__current', options: [
                    { value: '__current', label: button.textContent.trim() },
                    { value: taskAction.dataset.nextStatus, label: taskAction.dataset.nextStatus === 'completed' ? 'Completed' : 'In Progress' }
                ]
            } : await request(id);
            if (!button.isConnected) return;
            menu = document.createElement('div'); menu.className = 'index-status-menu'; menu.setAttribute('role', 'menu');
            const heading = document.createElement('div'); heading.className = 'index-status-heading'; heading.textContent = 'Change Status'; menu.append(heading);
            data.options.forEach(option => {
                const item = document.createElement('button'); item.type = 'button'; item.setAttribute('role', 'menuitem');
                item.textContent = option.label; item.className = String(option.value) === String(data.value) ? 'active' : '';
                item.addEventListener('click', async () => {
                    if (String(option.value) === String(data.value) && !(module === 'follow-ups' && option.value === 'resheduled')) { close(); return; }
                    if (taskAction) { close(); taskAction.click(); return; }
                    if (data.requires_comment) { commentModal(button, option); return; }
                    close();
                    if (module === 'estimates' && option.value === 'approved' && window.collectEstimateEquipment) {
                        const equipment = await window.collectEstimateEquipment(id);
                        if (equipment === null) return;
                        button.disabled = true;
                        try { await request(id,'PATCH',option.value,undefined,undefined,equipment); location.reload(); }
                        catch (error) { message(error.message); button.disabled=false; }
                        return;
                    }
                    const confirmed = window.Swal ? (await Swal.fire({ title: `Change status to ${option.label}?`, icon: 'question', showCancelButton: true, confirmButtonColor: '#0a2540', confirmButtonText: 'Update' })).isConfirmed : window.confirm(`Change status to ${option.label}?`);
                    if (!confirmed) return;
                    button.disabled = true;
                    try { await request(id, 'PATCH', option.value); location.reload(); }
                    catch (exception) { message(exception.message); button.disabled = false; }
                }); menu.append(item);
            });
            document.body.append(menu); button.setAttribute('aria-expanded', 'true');
            const rect = button.getBoundingClientRect(); menu.style.left = `${Math.max(8, Math.min(rect.left, innerWidth - 228))}px`; menu.style.top = `${rect.bottom + 5}px`;
            if (rect.bottom + menu.offsetHeight > innerHeight) menu.style.top = `${Math.max(8, rect.top - menu.offsetHeight - 5)}px`;
            menu.querySelector('.active')?.scrollIntoView({ block: 'nearest' });
            (menu.querySelector('button.active') || menu.querySelector('button'))?.focus();
        } catch (exception) { message(exception.message); }
        finally { button.disabled = false; }
    }
    function init() {
        if (!document.body.classList.contains('crm-index-page') || !modules.includes(module)) return;
        scan(); let pending = false;
        new MutationObserver(() => { if (!pending) { pending = true; requestAnimationFrame(() => { pending = false; scan(); }); } }).observe(document.querySelector('main') || document.querySelector('.main-content') || document.body, { childList: true, subtree: true });
        document.addEventListener('click', event => {
            const button = event.target.closest('.index-status-editable');
            if (button) { event.preventDefault(); event.stopImmediatePropagation(); open(button); }
            else if (!event.target.closest('.index-status-menu')) close();
        }, true);
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') close();
            if (menu && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault(); const items = Array.from(menu.querySelectorAll('button')); const at = items.indexOf(document.activeElement);
                items[(at + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length]?.focus();
            }
        });
        window.addEventListener('resize', close);
        document.addEventListener('scroll', event => { if (!event.target.closest?.('.index-status-menu')) close(); }, true);
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
