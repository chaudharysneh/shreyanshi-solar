(function () {
    window.CustomerDocumentUploader = function (options = {}) {
        const permissions = { edit: options.edit !== false };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[char]));
        const docsModalElement = document.getElementById('estimateDocsModal');
        const docsEstimateIdInput = document.getElementById('estimateDocsEstimateId');
        const docsFields = document.getElementById('estimateDocsFields');
        const docsExtraFields = document.getElementById('estimateDocsExtraFields');
        let uploadedDocs = [];
        let docsLoaded = false;
        let extraDocSequence = 0;
        const docsList = document.getElementById('estimateDocsList');
        const docsUploadBtn = document.getElementById('estimateDocsUploadBtn');
        const docsFilesError = document.getElementById('estimateDocsFilesError');
        // Keep the modal outside layout stacking contexts and dismiss the instance we opened.
        if (docsModalElement) document.body.appendChild(docsModalElement);
        const docsModal = docsModalElement && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(docsModalElement) : null;
        docsModalElement?.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                docsModal?.hide();
            });
        });

        function renderDocsList(estimateId, docs) {
            uploadedDocs = docs || [];
            docsModalElement.querySelectorAll('#estimateDocsFields .customer-doc-field').forEach(function (field) {
                const count = uploadedDocs.filter(doc => doc.category === field.dataset.category).length;
                field.querySelector('.customer-doc-status').textContent = count ? `${count} uploaded` : 'Required';
                field.classList.toggle('is-complete', count > 0);
            });
            if (!docsList) {
                return;
            }

            if (!docs || !docs.length) {
                docsList.innerHTML = '<div class="text-muted small">No customer documents uploaded yet.</div>';
                return;
            }

            docsList.innerHTML = docs.map(function (doc, index) {
                const downloadUrl = options.customer ? doc.download_url : `/estimates/${estimateId}/customer-docs/${index}/download`;
                const deleteUrl = options.customer ? doc.delete_url : `/api/estimates/${estimateId}/customer-docs/${index}`;
                return `
                    <div class="border rounded px-3 py-2 d-flex justify-content-between align-items-center gap-3">
                        <div class="small overflow-hidden"><div class="fw-semibold text-truncate">${escapeHtml(doc.label || 'Other document')}</div><div class="text-muted text-break">${escapeHtml(doc.original_name || 'Document')}</div></div>
                        <div class="d-flex align-items-center gap-2">
                            ${downloadUrl ? `<a href="${escapeHtml(downloadUrl)}" class="btn btn-sm btn-outline-primary" target="_blank">Download</a>` : '<span class="small text-muted">Unavailable</span>'}
                            ${permissions.edit && deleteUrl ? `<button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" data-delete-url="${escapeHtml(deleteUrl)}" data-estimate-id="${estimateId}" data-doc-index="${index}">Delete</button>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            docsList.querySelectorAll('.delete-doc-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteEstimateDocument(button.dataset.estimateId, button.dataset.docIndex, button.dataset.deleteUrl);
                });
            });
        }

        function loadEstimateDocs(estimateId) {
            docsLoaded = false;
            docsUploadBtn.disabled = true;
            docsList.textContent = 'Loading documents...';
            $.ajax({
                url: options.url(estimateId),
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (String(docsEstimateIdInput.value) !== String(estimateId)) return;
                    docsLoaded = true;
                    docsUploadBtn.disabled = false;
                    renderDocsList(estimateId, response.data?.docs || []);
                },
                error: function () {
                    if (String(docsEstimateIdInput.value) !== String(estimateId)) return;
                    docsList.textContent = 'Unable to load documents. Close and reopen to try again.';
                },
            });
        }

        function openEstimateDocsModal(estimateId) {
            if (!docsModal || !docsEstimateIdInput || !docsFields) {
                return;
            }

            docsEstimateIdInput.value = estimateId;
            docsExtraFields.innerHTML = '';
            uploadedDocs = [];
            docsFields.querySelectorAll('input').forEach(input => { input.value = ''; input.classList.remove('is-invalid'); });
            docsFields.querySelectorAll('.customer-doc-selection').forEach(label => { label.textContent = 'No files selected'; });
            if (docsFilesError) {
                docsFilesError.style.display = 'none';
                docsFilesError.textContent = '';
            }
            loadEstimateDocs(estimateId);
            docsModal.show();
        }

        function uploadEstimateDocuments() {
            const estimateId = docsEstimateIdInput?.value;
            if (!estimateId || !docsFields || !docsLoaded || docsUploadBtn.disabled) {
                return;
            }

            const formData = new FormData();
            const errors = [];
            let groupIndex = 0;
            docsModalElement.querySelectorAll('.customer-doc-field').forEach(function (field) {
                const input = field.querySelector('.customer-doc-input');
                const customLabel = field.querySelector('.customer-doc-label');
                const label = customLabel ? customLabel.value.trim() : field.dataset.label;
                input.classList.remove('is-invalid');
                if (!input.files.length) return;
                if (customLabel) {
                    customLabel.classList.toggle('is-invalid', !label);
                    if (!label) errors.push('Enter a name for each extra document.');
                }
                formData.append(`documents[${groupIndex}][category]`, field.dataset.category);
                formData.append(`documents[${groupIndex}][label]`, label);
                Array.from(input.files).forEach(function (file) {
                    if (file.size > 10 * 1024 * 1024) errors.push(`${file.name} must be 10 MB or smaller.`);
                    formData.append(`documents[${groupIndex}][files][]`, file);
                });
                groupIndex++;
            });
            if (!groupIndex && !errors.length) {
                docsModal.hide();
                return;
            }
            if (errors.length) {
                docsFilesError.textContent = errors.join(' ');
                docsFilesError.style.display = 'block';
                docsModalElement.querySelector('.is-invalid')?.focus();
                return;
            }

            docsUploadBtn.disabled = true;
            docsUploadBtn.textContent = 'Uploading...';

            $.ajax({
                url: options.url(estimateId),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (docsFilesError) {
                        docsFilesError.style.display = 'none';
                        docsFilesError.textContent = '';
                    }
                    docsModalElement.querySelectorAll('.customer-doc-input').forEach(input => { input.value = ''; });
                    docsFields.querySelectorAll('.customer-doc-selection').forEach(label => { label.textContent = 'No files selected'; });
                    docsExtraFields.innerHTML = '';
                    if (options.customer) { loadEstimateDocs(estimateId); options.onChanged?.(); } else { renderDocsList(estimateId, response.data?.docs || []); }
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Customer documents uploaded successfully.');
                    }
                },
                error: function (xhr) {
                    const message = Object.values(xhr.responseJSON?.errors || {}).flat().join(' ')
                        || xhr.responseJSON?.message
                        || 'Unable to upload customer documents.';

                    if (docsFilesError) {
                        docsFilesError.textContent = message;
                        docsFilesError.style.display = 'block';
                    }
                },
                complete: function () {
                    docsUploadBtn.disabled = false;
                    docsUploadBtn.textContent = 'Upload';
                },
            });
        }

        document.getElementById('estimateDocsAddMore')?.addEventListener('click', function () {
            const id = `extra-doc-${++extraDocSequence}`;
            const field = document.createElement('div');
            field.className = 'customer-doc-field';
            field.dataset.category = 'extra';
            field.innerHTML = `<div class="d-flex align-items-center justify-content-between mb-2"><label for="${id}-label" class="form-label fw-semibold mb-0">Document name</label><button type="button" class="btn btn-sm btn-outline-danger customer-doc-remove" aria-label="Remove extra document"><i class="bi bi-trash"></i></button></div><input id="${id}-label" class="form-control customer-doc-label mb-3" maxlength="100" placeholder="e.g. Property ownership proof"><label for="${id}-files" class="form-label small">Attach files</label><input type="file" id="${id}-files" class="form-control customer-doc-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.jfif"><div class="customer-doc-selection small text-muted mt-2" aria-live="polite">No files selected</div>`;
            field.querySelector('.customer-doc-remove').addEventListener('click', () => field.remove());
            docsExtraFields.appendChild(field);
            field.querySelector('.customer-doc-label').focus();
        });

        docsModalElement?.addEventListener('change', function (event) {
            if (!event.target.matches('.customer-doc-input')) return;
            const input = event.target;
            input.classList.remove('is-invalid');
            input.closest('.customer-doc-field').querySelector('.customer-doc-selection').textContent = input.files.length
                ? Array.from(input.files).map(file => file.name).join(', ')
                : 'No files selected';
        });

        function deleteEstimateDocument(estimateId, docIndex, deleteUrl) {
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (options.customer) { loadEstimateDocs(estimateId); options.onChanged?.(); } else { renderDocsList(estimateId, response.data?.docs || []); }
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Customer document deleted successfully.');
                    }
                },
                error: function (xhr) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', xhr.responseJSON?.message || 'Unable to delete customer document.');
                    }
                },
            });
        }


        docsUploadBtn?.addEventListener('click', uploadEstimateDocuments);
        return { open: openEstimateDocsModal };
    };
})();
