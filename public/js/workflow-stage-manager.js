(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const config = window.workflowStageManager;
        const modalElement = document.getElementById('manageStagesModal');
        if (!config || !modalElement) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('addStageForm');
        const nameInput = document.getElementById('newStageName');
        const table = document.getElementById('stagesTableBody');
        const error = document.getElementById('stageManagerError');
        let busy = false;
        function context() { return { workflow_type: config.workflowType, finance_id: config.masterId() || null }; }
        function escape(value) { const span = document.createElement('span'); span.textContent = value; return span.innerHTML.replace(/"/g, '&quot;'); }
        async function request(method, path, values) {
            const response = await fetch('/api/workflow/stages' + path, {
                method: method,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                ...(method !== 'GET' ? { body: JSON.stringify({ ...context(), ...values }) } : {})
            });
            const result = await response.json();
            if (!response.ok || result.success === false) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || 'Unable to save stages.');
            return result;
        }
        async function reload() {
            const params = new URLSearchParams({ workflow_type: config.workflowType });
            if (config.masterId()) params.set('finance_id', config.masterId());
            const response = await request('GET', '?' + params.toString());
            table.innerHTML = '';
            response.data.forEach(function (stage, index) {
                const row = document.createElement('tr');
                row.innerHTML = `<td class="text-center text-muted">${index + 1}</td><td><input class="form-control form-control-sm" type="text" maxlength="255" value="${escape(stage.name)}" aria-label="Stage name" disabled></td><td><input type="number" min="1" max="${response.data.length}" value="${stage.order}" class="form-control form-control-sm text-center" style="width:75px" aria-label="Order for ${escape(stage.name)}"></td><td class="text-center text-nowrap"><button type="button" class="btn btn-sm btn-outline-dark-blue me-1" title="Edit stage" aria-label="Edit ${escape(stage.name)}"><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-sm btn-outline-danger" title="Delete stage" aria-label="Delete ${escape(stage.name)}"><i class="bi bi-trash"></i></button></td>`;
                const [name, order] = row.querySelectorAll('input');
                const [edit, remove] = row.querySelectorAll('button');
                edit.addEventListener('click', function () {
                    if (name.disabled) {
                        name.disabled = false; name.focus(); edit.innerHTML = '<i class="bi bi-check-lg"></i>'; edit.title = 'Save stage'; edit.setAttribute('aria-label', 'Save stage');
                    } else mutate('PUT', '/' + stage.id, { name: name.value.trim(), order: Number(order.value) });
                });
                name.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); edit.click(); } });
                order.addEventListener('change', function () {
                    if (!order.checkValidity()) { order.reportValidity(); return; }
                    mutate('PUT', '/' + stage.id, { order: Number(order.value) });
                });
                remove.addEventListener('click', async function () {
                    if (busy) return;
                    const accepted = window.Swal ? (await Swal.fire({ title: 'Delete stage?', text: stage.name, icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#0a2540' })).isConfirmed : window.confirm('Delete stage "' + stage.name + '"?');
                    if (accepted) mutate('DELETE', '/' + stage.id, {});
                });
                table.appendChild(row);
            });
        }
        async function mutate(method, path, data) {
            if (busy) return;
            if (data.name !== undefined && !data.name) { error.textContent = 'Please enter a stage name.'; return; }
            busy = true; error.textContent = '';
            form.querySelector('button').disabled = true;
            table.querySelectorAll('button, input').forEach(function (element) { element.disabled = true; });
            try {
                await request(method, path, data);
                if (method === 'POST') nameInput.value = '';
                await reload();
                await config.refresh();
            } catch (failure) {
                error.textContent = failure.message;
                await reload().catch(function () {});
            } finally { busy = false; form.querySelector('button').disabled = false; }
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!nameInput.value.trim()) { error.textContent = 'Please enter a stage name.'; nameInput.focus(); return; }
            mutate('POST', '', { name: nameInput.value.trim() });
        });
        window.openManageStagesModal = async function () {
            document.getElementById('modalWorkflowTitle').textContent = config.workflowType.replace(/-/g, ' ');
            error.textContent = ''; modal.show();
            try { await reload(); } catch (failure) { error.textContent = failure.message; }
        };
    });
})();
