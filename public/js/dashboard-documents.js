const dashboardDocumentRoot = document.querySelector('[data-document-base]');
const documentBase = dashboardDocumentRoot?.dataset.documentBase;
const customerBase = dashboardDocumentRoot?.dataset.customerBase;
function parseDocumentResponse(raw) {
    const text = raw.replace(/^\uFEFF/, '').trim();
    try { return JSON.parse(text); } catch (error) {
        // Some PHP setups flush a startup notice before Laravel can clear it.
        // Recover only the JSON object following a recognisable PHP diagnostic.
        const start = text.indexOf('{');
        const prefix = start >= 0 ? text.slice(0, start) : '';
        if (start >= 0 && /(?:PHP (?:Request Startup|Warning|Notice)|<b>(?:Notice|Warning|Deprecated)<\/b>)/i.test(prefix)) {
            const parsed = JSON.parse(text.slice(start));
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) return parsed;
        }
        throw error;
    }
}
async function documentJson(url, options = {}) {
    const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', ...options.headers } });
    const raw = await response.text();
    let data;
    try { data = parseDocumentResponse(raw); }
    catch {
        if (response.redirected || response.status === 401 || response.status === 419) throw new Error('Your session expired. Please sign in again before saving.');
        if (response.status === 413) throw new Error('The image exceeds the server upload limit. Choose a smaller image.');
        throw new Error(`Unable to confirm the save (HTTP ${response.status}). Your form and selected image are retained.`);
    }
    if (!response.ok || response.redirected || data.success === false) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to complete request. Please sign in again or retry.');
    return data;
}
function documentSaveResult(response) {
    const record = response?.document || response?.data?.document || response?.data || response;
    const id = record?.id ?? response?.id ?? record?.document_id ?? response?.document_id;
    if (!/^[1-9][0-9]*$/.test(String(id))) {
        throw new Error('The server did not confirm a saved document ID. Your form and selected image have been kept. Please retry after refreshing the page.');
    }
    const hasAadhaar = record?.has_aadhaar ?? response?.has_aadhaar;
    return { id: String(id), has_aadhaar: typeof hasAadhaar === 'boolean' ? hasAadhaar : undefined };
}

document.querySelectorAll('.dashboard-document-form').forEach(form => {
    const notice = form.querySelector('.dashboard-document-notice');
    const exportStep = form.querySelector('.dashboard-document-export');
    const filename = form.querySelector('[data-document-filename]');
    let filenameInitialized = false;
    let documentId = null;
    let saving = false;
    const customerSelect = form.querySelector('.dashboard-document-customer');
    const createPanel = form.querySelector('[data-customer-create]');
    const errorBox = form.querySelector('[data-document-error]');
    const preview = form.querySelector('[data-aadhaar-preview]');
    const previewImage = form.querySelector('[data-aadhaar-preview-image]');
    const fileInput = form.querySelector('input[name="consumer_aadhaar_image"]');
    let previewObjectUrl = null;
    let savedPreviewUrl = null;
    function showImage(source, name = 'Saved Aadhaar image', detail = 'Included in your saved document', selected = false) {
        if (!preview) return;
        if (previewObjectUrl && previewObjectUrl !== source) { URL.revokeObjectURL(previewObjectUrl); previewObjectUrl = null; }
        preview.classList.toggle('d-none', !source);
        if (source) previewImage.src = source;
        else previewImage.removeAttribute('src');
        form.querySelector('[data-aadhaar-preview-name]').textContent = name;
        form.querySelector('[data-aadhaar-preview-detail]').textContent = detail;
        form.querySelector('[data-aadhaar-preview-clear]').classList.toggle('d-none', !selected);
    }
    function updateProgress() {
        let filled = 0, total = 0;
        form.querySelectorAll('.dashboard-document-section').forEach(section => {
            const inputs = [...section.querySelectorAll('input[name]:not([type="hidden"]),select[name],textarea[name]')];
            const complete = inputs.filter(input => input.type === 'file' ? Boolean(input.files?.length || savedPreviewUrl) : Boolean(input.value.trim())).length;
            filled += complete; total += inputs.length;
            section.classList.toggle('document-section-is-complete', complete > 0 && complete === inputs.length);
        });
        form.querySelector('[data-document-completion]').textContent = `${filled} / ${total} fields filled`;
    }
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        const message = form.querySelector('[data-aadhaar-preview-error]');
        message.textContent = '';
        if (!file) { showImage(savedPreviewUrl); updateProgress(); return; }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            message.textContent = 'Choose a JPG, PNG or WebP image up to 5 MB.';
            fileInput.value = ''; showImage(savedPreviewUrl); updateProgress(); return;
        }
        const url = URL.createObjectURL(file);
        showImage(url, file.name, `${(file.size / 1024).toFixed(0)} KB | Ready to save`, true);
        previewObjectUrl = url;
        updateProgress();
    });
    form.querySelector('[data-aadhaar-preview-clear]')?.addEventListener('click', () => { fileInput.value = ''; showImage(savedPreviewUrl); updateProgress(); });
    previewImage?.addEventListener('error', () => { preview.classList.add('d-none'); form.querySelector('[data-aadhaar-preview-error]').textContent = 'Preview unavailable. You can still select a new image or download the saved attachment.'; });
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    function selectCustomer(customer, fill = true) {
        const option = new Option(`${customer.name}${customer.phone ? ' (' + customer.phone + ')' : ''}`, customer.id, true, true);
        option.dataset.name = customer.name;
        customerSelect.replaceChildren(option);
        window.jQuery(customerSelect).trigger('change.select2');
        form.elements.namedItem('consumer_name').value = customer.name;
        updateProgress();
        if (fill) {
            const address = form.elements.namedItem('address');
            if (address) address.value = customer.address || '';
            const mobile = form.elements.namedItem('mobile_number');
            if (mobile) mobile.value = customer.phone || '';
        }
        updateProgress();
    }
    window.jQuery(customerSelect).select2({
        theme: 'bootstrap-5', width: '100%', dropdownParent: window.jQuery(form.closest('.modal')),
        placeholder: 'Search customer by name or phone', allowClear: true,
        ajax: { url: customerBase, dataType: 'json', delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: customers => ({ results: customers.map(customer => ({ ...customer, text: `${customer.name}${customer.phone ? ' (' + customer.phone + ')' : ''}` })) }),
        },
    }).on('select2:select', event => selectCustomer(event.params.data)).on('select2:clear', () => { form.elements.namedItem('consumer_name').value = ''; });
    function toggleCreate(visible) {
        createPanel.classList.toggle('d-none', !visible);
        form.querySelector('[data-customer-add]')?.setAttribute('aria-expanded', String(visible));
        if (visible) createPanel.querySelector('[data-customer-name]').focus();
    }
    form.querySelector('[data-customer-add]')?.addEventListener('click', () => toggleCreate(createPanel.classList.contains('d-none')));
    form.querySelector('[data-customer-create-cancel]')?.addEventListener('click', () => toggleCreate(false));
    form.querySelector('[data-customer-create-save]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        const error = form.querySelector('[data-customer-error]');
        const name = form.querySelector('[data-customer-name]').value.trim();
        const phone = form.querySelector('[data-customer-phone]').value.trim();
        if (!name || !/^[0-9]{10}$/.test(phone)) { error.textContent = 'Enter a customer name and a valid 10-digit phone number.'; return; }
        button.disabled = true; error.textContent = '';
        try {
            const data = new FormData(); data.set('name', name); data.set('phone', phone);
            selectCustomer(await documentJson(customerBase, { method: 'POST', body: data }));
            toggleCreate(false);
            form.querySelector('[data-customer-name]').value = '';
            form.querySelector('[data-customer-phone]').value = '';
        } catch (failure) { error.textContent = failure.message; }
        finally { button.disabled = false; }
    });
    async function persist() {
        const data = { type: form.dataset.documentType, customer_id: customerSelect.value, filename: filename.value.trim(), fields: {} };
        if (documentId) data.document_id = documentId;
        const aadhaarInput = form.querySelector('input[type="file"][name="consumer_aadhaar_image"]');
        const aadhaarFile = aadhaarInput?.files?.[0];
        for (const [key, value] of new FormData(form)) {
            if (key !== 'customer_id' && typeof value === 'string') data.fields[key] = value;
        }
        if (aadhaarFile) {
            if (aadhaarFile.size > 5 * 1024 * 1024) throw new Error('Choose an Aadhaar image smaller than 5 MB.');
            data.consumer_aadhaar_image_data = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(new Error('Unable to read the Aadhaar image. Please select it again.'));
                reader.readAsDataURL(aadhaarFile);
            });
        }
        const result = documentSaveResult(await documentJson(documentBase, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }));
        // Keep the saved ID even if attachment verification fails, so a retry updates this row.
        documentId = result.id;
        const saved = typeof result.has_aadhaar === 'boolean' ? result : await documentJson(`${documentBase}/${documentId}`);
        const hasAadhaar = saved.has_aadhaar === true;
        if (aadhaarFile && !hasAadhaar) throw new Error('Document saved, but its Aadhaar attachment could not be verified. Your selected image is retained; please retry.');
        const link = form.querySelector('[data-saved-aadhaar]');
        if (link) {
            link.classList.toggle('d-none', !hasAadhaar);
            link.href = `${documentBase}/${documentId}/aadhaar`;
        }
        if (aadhaarFile && hasAadhaar) aadhaarInput.value = '';
        if (preview) {
            savedPreviewUrl = hasAadhaar ? `${documentBase}/${documentId}/aadhaar?preview=1` : null;
            showImage(savedPreviewUrl);
        }
        updateProgress();
        if (form.dataset.documentType === 'solar') {
            form.querySelector('[data-export-status]').textContent = hasAadhaar ? 'Aadhaar image saved. It will be included on the guarantee certificate page.' : 'No Aadhaar image is attached to this document. Use Back to Edit to upload it before downloading.';
        }
        document.dispatchEvent(new Event('dashboard-documents-saved'));
    }
    form.addEventListener('dashboard-document-open', event => {
        const record = event.detail;
        form.reset();
        documentId = record.id;
        savedPreviewUrl = record.has_aadhaar ? `${documentBase}/${record.id}/aadhaar?preview=1` : null;
        showImage(savedPreviewUrl);
        const aadhaarLink = form.querySelector('[data-saved-aadhaar]');
        if (aadhaarLink) { aadhaarLink.classList.toggle('d-none', !record.has_aadhaar); aadhaarLink.href = `${documentBase}/${record.id}/aadhaar`; }
        filenameInitialized = true;
        filename.value = record.filename;
        for (const [key, value] of Object.entries(record.fields)) {
            const input = form.elements.namedItem(key);
            if (input && input.type !== 'file') input.value = value || '';
        }
        selectCustomer(record.customer, false);
        form.elements.namedItem('consumer_name').value = record.fields.consumer_name;
        form.querySelector('[data-export-status]').textContent = '';
        errorBox.classList.add('d-none');
        toggleCreate(false);
        showStep(false);
    });
    function showStep(exporting) {
        form.querySelector('.dashboard-document-edit').classList.toggle('d-none', exporting);
        form.querySelector('.dashboard-document-edit-footer').classList.toggle('d-none', exporting);
        exportStep.classList.toggle('d-none', !exporting);
        form.querySelector('.dashboard-document-export-footer').classList.toggle('d-none', !exporting);
        notice.classList.add('d-none');
        form.querySelector('[data-document-step="details"]').classList.toggle('is-active', !exporting);
        form.querySelector('[data-document-step="export"]').classList.toggle('is-active', exporting);
        const summary = form.querySelector('[data-document-summary]');
        summary.textContent = `${form.elements.namedItem('consumer_name').value || 'Document'} | ${form.dataset.documentType === 'solar' ? 'Solar Documents' : 'Subsidy Bond'}`;
        updateProgress();
        form.querySelector('.modal-body').scrollTop = 0;
        if (exporting) exportStep.querySelector('h5').focus();
        else form.querySelector('.dashboard-document-edit input')?.focus();
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        if (exportStep) {
            if (!filenameInitialized) {
                const consumer = form.elements.namedItem('consumer_name').value.trim();
                const today = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Kolkata', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date()).replaceAll('/', '-');
                const documentTitle = form.dataset.documentType === 'solar' ? 'Solar Documents' : 'Subsidy Bond';
                filename.value = `${consumer ? consumer + ' - ' : ''}${documentTitle}_${today}`;
                filenameInitialized = true;
            }
            saving = true;
            const submit = form.querySelector('button[type="submit"]'); const submitLabel = submit.innerHTML; submit.disabled = true; submit.textContent = 'Saving document...'; form.setAttribute('aria-busy', 'true');
            errorBox.classList.add('d-none');
            try { await persist(); showStep(true); }
            catch (failure) { errorBox.textContent = failure.message; errorBox.classList.remove('d-none'); errorBox.scrollIntoView({ block: 'nearest' }); }
            finally { saving = false; submit.disabled = false; submit.innerHTML = submitLabel; form.removeAttribute('aria-busy'); }
            return;
        }
        notice.classList.remove('d-none');
        notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
    form.addEventListener('input', () => notice.classList.add('d-none'));
    form.querySelector('[data-document-back]')?.addEventListener('click', () => showStep(false));
    form.querySelector('[data-document-restart]')?.addEventListener('click', () => {
        form.reset();
        documentId = null;
        savedPreviewUrl = null; showImage(null);
        form.querySelector('[data-saved-aadhaar]')?.classList.add('d-none');
        customerSelect.replaceChildren(new Option('', '', true, true));
        window.jQuery(customerSelect).val(null).trigger('change');
        toggleCreate(false);
        errorBox.classList.add('d-none');
        filenameInitialized = false;
        form.querySelector('[data-export-status]').textContent = '';
        showStep(false);
    });
    updateProgress();
    form.querySelectorAll('[data-document-export]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = form.querySelector('[data-export-status]');
            if (!form.dataset.exportUrl) {
                status.textContent = 'Your solar document details are saved. Solar document generation and sharing need the solar template.';
                return;
            }
            if (!form.checkValidity()) { showStep(false); form.reportValidity(); return; }
            if (!filename.value.trim()) { status.textContent = 'Please enter a file name.'; filename.focus(); return; }
            const action = button.dataset.documentExport;
            const controls = form.querySelectorAll('button');
            controls.forEach(control => control.disabled = true);
            status.textContent = 'Preparing your document...';
            try {
                await persist();
                const data = Object.fromEntries(Array.from(new FormData(form)).filter(([, value]) => typeof value === 'string'));
                data.document_id = documentId;
                data.filename = filename.value.trim();
                data.format = action === 'word' ? 'word' : 'pdf';
                const response = await fetch(form.dataset.exportUrl, {
                    method: 'POST', body: JSON.stringify(data),
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
                if (!response.ok || (response.headers.get('Content-Type') || '').includes('text/html')) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(Object.values(error.errors || {}).flat().join(' ') || error.message || 'Unable to generate document. Please sign in again or retry.');
                }
                const blob = await response.blob();
                const extension = action === 'word' ? '.docx' : '.pdf';
                const safeName = filename.value.trim().replace(/[\\/:*?"<>|]/g, '-').replace(/\.(docx|pdf)$/i, '') || 'Subsidy Bond';
                const file = new File([blob], safeName + extension, { type: blob.type });
                if (action === 'share' && navigator.canShare?.({ files: [file] })) {
                    await navigator.share({ files: [file], title: safeName });
                    status.textContent = 'PDF shared.';
                } else {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a'); link.href = url; link.download = file.name;
                    document.body.appendChild(link); link.click(); link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 60000);
                    status.textContent = action === 'share' ? 'File sharing is unavailable in this browser. PDF downloaded so you can share it manually.' : 'Download ready.';
                }
            } catch (error) {
                status.textContent = error.name === 'AbortError' ? 'Sharing cancelled.' : error.message;
            } finally { controls.forEach(control => control.disabled = false); }
        });
    });
});

const documentList = document.getElementById('dashboard-document-list');
if (documentList) {
    let page = 1;
    let lastPage = 1;
    let requestSequence = 0;
    const rows = documentList.querySelector('[data-document-rows]');
    const error = documentList.querySelector('[data-document-list-error]');
    async function loadDocuments() {
        const sequence = ++requestSequence;
        error.textContent = '';
        const params = new URLSearchParams({ page, q: documentList.querySelector('[data-document-search]').value, type: documentList.querySelector('[data-document-filter]').value });
        try {
            const result = await documentJson(`${documentBase}?${params}`);
            if (sequence !== requestSequence) return;
            rows.replaceChildren();
            for (const record of result.data) {
                const row = document.createElement('tr');
                for (const value of [record.filename, record.customer?.name || '-', record.customer?.phone || '-', record.type === 'solar' ? 'Solar Documents' : 'Subsidy Bond', new Date(record.updated_at).toLocaleDateString('en-GB')]) {
                    const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
                }
                const cell = document.createElement('td');
                const open = document.createElement('button'); open.type = 'button'; open.className = 'btn btn-sm btn-outline-dark-blue'; open.textContent = 'Open / Edit';
                open.addEventListener('click', async () => {
                    open.disabled = true;
                    try {
                        const saved = await documentJson(`${documentBase}/${record.id}`);
                        const modal = document.getElementById(`dashboard-document-${saved.type}`);
                        documentList.addEventListener('hidden.bs.modal', () => {
                            bootstrap.Modal.getOrCreateInstance(modal).show();
                            modal.querySelector('form').dispatchEvent(new CustomEvent('dashboard-document-open', { detail: saved }));
                        }, { once: true });
                        bootstrap.Modal.getOrCreateInstance(documentList).hide();
                    } catch (failure) { error.textContent = failure.message; }
                    finally { open.disabled = false; }
                });
                cell.appendChild(open); row.appendChild(cell); rows.appendChild(row);
            }
            if (!result.data.length) { const row = rows.insertRow(); const cell = row.insertCell(); cell.colSpan = 6; cell.className = 'text-center text-muted py-4'; cell.textContent = 'No saved documents found.'; }
            lastPage = result.last_page;
            documentList.querySelector('[data-document-page]').textContent = `${result.total} document(s) | Page ${page} of ${lastPage}`;
            documentList.querySelector('[data-document-prev]').disabled = page <= 1;
            documentList.querySelector('[data-document-next]').disabled = page >= lastPage;
        } catch (failure) { if (sequence === requestSequence) error.textContent = failure.message; }
    }
    documentList.addEventListener('show.bs.modal', () => { page = 1; loadDocuments(); });
    documentList.querySelector('[data-document-prev]').addEventListener('click', () => { if (page > 1) { page--; loadDocuments(); } });
    documentList.querySelector('[data-document-next]').addEventListener('click', () => { if (page < lastPage) { page++; loadDocuments(); } });
    let searchTimer;
    documentList.querySelector('[data-document-search]').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page = 1; loadDocuments(); }, 300); });
    documentList.querySelector('[data-document-filter]').addEventListener('change', () => { page = 1; loadDocuments(); });
}
