(function () {
    function init() {
        const $ = window.jQuery;
        if ($?.fn.select2) $('[data-workflow-status]').each(function () {
            $(this).select2({theme:'bootstrap-5',width:'100%',dropdownCssClass:'task-flow-select2',dropdownParent:$(this).parent(),minimumResultsForSearch:6});
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',init); else init();
    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-flow-now]');
        if (!button) return;
        const parts = Object.fromEntries(new Intl.DateTimeFormat('en-GB',{timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date()).map(part=>[part.type,part.value]));
        button.closest('form').elements[button.dataset.flowNow].value = `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
    });
    document.addEventListener('change', function (event) {
        if (!event.target.matches('.task-workflow-form input[type="file"]')) return;
        const list = event.target.closest('.task-flow-upload').querySelector('.task-flow-file-list');
        list.textContent = Array.from(event.target.files).map(file=>file.name).join(', ');
    });
    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('.task-workflow-form');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-flow-save]');
        const error = form.querySelector('.workflow-error');
        const original = button.innerHTML;
        const data = new FormData(form);
        const files = form.querySelector('input[type="file"]').files;
        error.textContent = '';
        if (files.length > 10 || Array.from(files).some(file=>file.size > 10*1024*1024)) {
            error.textContent = 'Select up to 10 files, no larger than 10 MB each.';return;
        }
        if (!files.length) data.delete('attachments[]');
        data.set('_method','PATCH');
        button.disabled = true;
        button.textContent = 'Saving update...';
        try {
            const response = await fetch(form.dataset.url, {
                method:'POST', credentials:'same-origin',
                headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:data,
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Unable to save update.');
            try { sessionStorage.setItem('crm.task.workflow.stage',form.elements.stage_id.value); } catch (_) {}
            window.location.reload();
        } catch (failure) {
            error.textContent = failure.message;
            button.disabled = false;
            button.innerHTML = original;
        }
    });
    window.addEventListener('load',function () {
        const key = 'crm.task.workflow.stage';
        try { const stage=sessionStorage.getItem(key);if(stage!==null){sessionStorage.removeItem(key);document.getElementById('task-stage-'+stage)?.scrollIntoView({block:'start'});} } catch (_) {}
    });
})();
