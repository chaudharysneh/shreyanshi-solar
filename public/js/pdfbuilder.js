/**
 * PDF Builder consolidated JS
 * Combines list, form wizard step logic, block toggles, dynamic block addition, and submissions.
 */
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    // ==================== LIST / INDEX PAGE LOGIC ====================
    function initPdfList() {
        const tableBody = document.getElementById("templatesTableBody");
        if (!tableBody) return;

        const paginationContainer = document.getElementById("templatePaginationContainer");
        const searchInput = document.getElementById("templateSearch");

        // ✅ RENDER ROWS
        function renderRows(items, startIndex = 0) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-file-earmark-pdf display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No templates found.</p>
                            <a href="/pdfbuilder/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Template</a>
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((template, index) => {
                const serialNo = startIndex + index + 1;
                const createdDate = new Date(template.created_at);
                const dateStr = createdDate.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
                const timeStr = createdDate.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });

                const templateNameLower = String(template.template_name || '').trim().toLowerCase();
                const isQtTemplate = templateNameLower === 'solar proposal' || templateNameLower === 'ux template';
                const viewUrl = isQtTemplate ? `/estimates/preview/qt-000150-pdf` : `/pdfbuilder/view/${template.id}`;
                const editUrl = `/pdfbuilder/edit/${template.id}`;
                const isStaticBasicTemplate = template.is_static_basic_template || templateNameLower === 'basic template';
                const editAction = (isStaticBasicTemplate || isQtTemplate) ? '' : `<a href="${editUrl}" class="btn crm-action-btn btn-sm" title="Edit Template"><i class="bi bi-pencil"></i></a>`;

                return `
                <tr class="template-row">
                    <td class="ps-4 fw-semibold text-muted template-id">${serialNo}</td>
                    <td class="template-name">
                        <div class="d-flex align-items-center gap-2 py-1">
                            <span class="fw-bold text-dark-blue">${template.template_name}</span>
                        </div>
                    </td>

                    <td class="text-muted d-none d-md-table-cell">
                        <div class="fw-semibold text-dark">${dateStr}</div>
                        <div class="template-time opacity-75">${timeStr}</div>
                    </td>

                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            <a href="${viewUrl}" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="View PDF"><i class="bi bi-eye"></i></a>
                            ${editAction}
                            <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${template.id}" title="Delete"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-template-id="${template.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none" id="details-${template.id}" style="display: none;">
                    <td colspan="5" class="p-0">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-file-alt"></i> Template Name :</div>
                                    <div class="expand-value">${template.template_name}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar"></i> Created :</div>
                                    <div class="expand-value">${dateStr} ${timeStr}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        <a href="${viewUrl}" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener"><i class="bi bi-eye"></i></a>
                                        ${editAction}
                                        <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${template.id}"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");
        }

        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchTemplates(1), 400);
            });
        }

        // ✅ FETCH API
        window.fetchTemplates = function(page = 1) {
            let baseUrl = window.pdfbuilderApiUrl || "/api/v1/pdfbuilderApi/templet";
            let url = baseUrl + (baseUrl.indexOf('?') !== -1 ? "&" : "?") + `page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                beforeSend: function () {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || [], res.data.from ? res.data.from - 1 : 0);
                        renderPagination(res.data);
                    }
                },
                error: function (xhr) {
                    console.error("Fetch Templates Error:", xhr);
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5">
                        <div class="text-danger mb-2"><i class="bi bi-exclamation-octagon display-4"></i></div>
                        <div>Error loading templates. Please try again.</div>
                        <small class="text-muted">${xhr.statusText || 'Network Error'}</small>
                    </td></tr>`;
                },
            });
        };

        function renderPagination(data) {
            if (!paginationContainer) return;
            if (data.total === 0) { paginationContainer.innerHTML = ""; return; }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `<div class="crm-pagination-container">
                <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            }

            html += `</ul></div>`;
            paginationContainer.innerHTML = html;

            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchTemplates(this.dataset.page);
                });
            });
        }

        fetchTemplates();
    }

    // ✅ DELETE FUNCTION
    function deleteTemplate(id, button) {
        window.showDeleteConfirm("You won't be able to revert this!").then((result) => {
            if (!result.isConfirmed) return;

            const btn = $(button);
            window.buttonLoader(btn, "Deleting", true);

            const apiUrl = (window.pdfbuilderApiDeletePath || "/api/v1/pdfbuilderApi/delete/") + id;
            $.ajax({
                url: apiUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                success: function (res) {
                    window.buttonLoader(btn, "", false);
                    if (res.status || res.success) {
                        if (window.fetchTemplates) window.fetchTemplates();
                        window.showAlert('success', res.message || 'Template deleted.');
                    } else {
                        window.showAlert('error', res.message || "Delete failed");
                    }
                },
                error: function (xhr) {
                    window.buttonLoader(btn, "", false);
                    window.showAlert('error', "Something went wrong");
                },
            });
        });
    }

    // ==================== FORM / CREATE / EDIT LOGIC ====================
    let editorCount = 0;
    let blockSeq = 0;
    window.__pdfWizardPendingEditors = window.__pdfWizardPendingEditors || [];
    window.__pdfWizardIsMobile = window.matchMedia ? window.matchMedia('(max-width: 767px)').matches : (window.innerWidth <= 767);
    window.__pdfWizardCurrentStep = 1;

    function setBlockEnabled(targetSelector, enabled) {
        const body = document.querySelector(targetSelector);
        if (!body) return;

        body.classList.toggle('d-none', !enabled);

        const fields = body.querySelectorAll('input, select, textarea, button');
        fields.forEach((el) => {
            el.disabled = !enabled;
            if (window.CKEDITOR && el.tagName === 'TEXTAREA' && CKEDITOR.instances && CKEDITOR.instances[el.id]) {
                CKEDITOR.instances[el.id].setReadOnly(!enabled);
            }
        });
    }

    function initBlockToggles() {
        document.querySelectorAll('.block-toggle').forEach((toggle) => {
            const target = toggle.getAttribute('data-target');
            const activeInputSel = toggle.getAttribute('data-active-input');
            const activeInput = activeInputSel ? document.querySelector(activeInputSel) : null;
            if (!target) return;
            if (activeInput) {
                activeInput.value = toggle.checked ? '1' : '0';
            }
            setBlockEnabled(target, !!toggle.checked);
            toggle.addEventListener('change', function () {
                if (activeInput) {
                    activeInput.value = this.checked ? '1' : '0';
                }
                setBlockEnabled(target, !!this.checked);
            });
        });
    }

    function initCkeditorIfPresent(textareaId) {
        if (!window.CKEDITOR) return;
        const el = document.getElementById(textareaId);
        if (!el) return;
        if (CKEDITOR.instances && CKEDITOR.instances[textareaId]) return;
        const editor = CKEDITOR.replace(textareaId);
        editor.on('instanceReady', function () {
            editor.setReadOnly(!!el.disabled);
        });
    }

    function initEditorsForPdfStep(stepNum) {
        if (stepNum === 1) {
            initCkeditorIfPresent('company_description');
        }
        if (stepNum === 3) {
            initCkeditorIfPresent('components_description');
            initCkeditorIfPresent('estimate_template_comment');
        }
        if (stepNum === 4) {
            initCkeditorIfPresent('environment_impact_content');
            if (Array.isArray(window.__pdfWizardPendingEditors) && window.__pdfWizardPendingEditors.length) {
                const pending = window.__pdfWizardPendingEditors.slice();
                window.__pdfWizardPendingEditors.length = 0;
                pending.forEach((id) => initCkeditorIfPresent(id));
            }
        }
    }

    function syncAllCkeditorsToTextareas() {
        if (!window.CKEDITOR || !CKEDITOR.instances) return;
        Object.keys(CKEDITOR.instances).forEach((key) => {
            CKEDITOR.instances[key].updateElement();
        });
    }

    function handleCompanyFileDrop(event, dropArea, type) {
        event.preventDefault();
        dropArea.classList.remove('bg-secondary-subtle');
        const fileInput = dropArea.querySelector('input[type=file]');
        fileInput.files = event.dataTransfer.files;
        showCompanyFileName(fileInput, type);
    }

    function attachRemoveButton(wrapper, fileInput, oldInputName) {
        let previewContainer = wrapper.querySelector('.mb-2');
        if (!previewContainer) return;
        if (previewContainer.querySelector('.remove-img-btn')) return;

        let img = previewContainer.querySelector('img');
        if (!img) return;

        let imgWrapper = img.parentElement;
        if (!imgWrapper.classList.contains('img-preview-wrapper')) {
            imgWrapper = document.createElement('div');
            imgWrapper.className = 'img-preview-wrapper position-relative d-inline-block mt-2';
            img.parentNode.insertBefore(imgWrapper, img);
            imgWrapper.appendChild(img);
        }

        let deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-danger rounded-circle position-absolute remove-img-btn shadow-sm';
        deleteBtn.style.cssText = 'top: -8px; right: -8px; width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 10; font-size: 12px;';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.title = 'Remove Image';
        
        deleteBtn.onclick = function() {
            fileInput.value = '';
            
            const fileNameContainer = wrapper.querySelector(`.company-file-name-${fileInput.name}`);
            if (fileNameContainer) fileNameContainer.textContent = '';
            
            let oldInput = previewContainer.querySelector(`input[type="hidden"]`);
            if (oldInput) {
                oldInput.value = '';
                wrapper.appendChild(oldInput);
            } else {
                oldInput = wrapper.querySelector(`input[name="${oldInputName}"]`);
                if (oldInput) oldInput.value = '';
            }
            
            if (fileInput.name === 'first_img') {
                let delFlag = wrapper.querySelector('input[name="delete_first_img"]');
                if (!delFlag) {
                    wrapper.insertAdjacentHTML('beforeend', '<input type="hidden" name="delete_first_img" value="1">');
                } else {
                    delFlag.value = '1';
                }
            }
            
            previewContainer.remove();
        };
        imgWrapper.appendChild(deleteBtn);
    }

    function showCompanyFileName(input, type) {
        const wrapper = input.closest('.mb-3') || input.parentElement;
        const fileNameContainer = wrapper.querySelector(`.company-file-name-${type}`);
        if (fileNameContainer) {
            fileNameContainer.textContent = input.files.length > 0 ?
                "Selected file: " + input.files[0].name :
                (input.previousElementSibling && input.previousElementSibling.tagName === 'DIV' ? 'Current file uploaded' : '');
        }
        
        if (input.files && input.files[0] && input.files[0].type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let img = wrapper.querySelector('img');
                if (img) {
                    img.src = e.target.result;
                } else {
                    const label = wrapper.querySelector('label');
                    if (label) {
                        const previewHtml = `
                            <div class="mb-2">
                                <span class="small text-muted d-block">Preview:</span>
                                <img src="${e.target.result}" style="width: 150px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                            </div>
                        `;
                        label.insertAdjacentHTML('afterend', previewHtml);
                    }
                }
                attachRemoveButton(wrapper, input, input.name + '_old');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function addServiceRow() {
        const container = document.getElementById('services-container');
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center mb-2 service-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="services_left[]" placeholder="Service">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="services_right[]" placeholder="Details">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeServiceRow(this)">Remove</button>
            </div>
        `;
        container.appendChild(row);
    }

    function removeServiceRow(btn) {
        const row = btn.closest('.service-row');
        if (!row) return;
        const container = document.getElementById('services-container');
        if (!container) return;
        const rows = container.querySelectorAll('.service-row');
        if (rows.length <= 1) {
            row.querySelectorAll('input').forEach(i => i.value = '');
            return;
        }
        row.remove();
    }

    function initPdfForm() {
        const form = document.getElementById('pdf-builder-form');
        if (!form) return;

        initBlockToggles();

        if (window.__pdfWizardIsMobile) {
            initEditorsForPdfStep(1);
        } else {
            initCkeditorIfPresent('company_description');
            initCkeditorIfPresent('components_description');
            initCkeditorIfPresent('estimate_template_comment');
            initCkeditorIfPresent('environment_impact_content');
        }

        const isMobile = !!window.__pdfWizardIsMobile;
        if (isMobile) {
            const totalSteps = 4;
            const $steps = document.querySelectorAll('.pdf-mobile-step');
            const $stepItems = document.querySelectorAll('.pdf-step-item');
            const prevBtn = document.getElementById('pdf-prev-step');
            const prevInlineBtn = document.getElementById('pdf-prev-step-inline');
            const nextBtn = document.getElementById('pdf-next-step');
            const nav = document.querySelector('.pdf-step-navigation');

            const clearStep1Errors = () => {
                const tErr = document.getElementById('template_name-error');
                const fErr = document.getElementById('first_img-error');
                if (tErr) tErr.textContent = '';
                if (fErr) fErr.textContent = '';
                const templateNameEl = document.getElementById('template_name');
                if (templateNameEl) templateNameEl.classList.remove('is-invalid');
                const fileInput = document.querySelector('input[name="first_img"]');
                if (fileInput) fileInput.classList.remove('is-invalid');
            };

            const validateStep1 = () => {
                clearStep1Errors();
                let ok = true;

                const templateNameEl = document.getElementById('template_name');
                const templateName = templateNameEl ? String(templateNameEl.value || '').trim() : '';
                if (!templateName) {
                    const tErr = document.getElementById('template_name-error');
                    if (tErr) tErr.textContent = 'Template Name is required.';
                    if (templateNameEl) templateNameEl.classList.add('is-invalid');
                    ok = false;
                }

                const existing = (document.getElementById('first_img_existing')?.value || '').trim();
                const fileInput = document.querySelector('input[name="first_img"]');
                const hasNew = !!(fileInput && fileInput.files && fileInput.files.length > 0);
                if (!hasNew && !existing) {
                    const fErr = document.getElementById('first_img-error');
                    if (fErr) fErr.textContent = 'Header Image (First Page) is required.';
                    if (fileInput) fileInput.classList.add('is-invalid');
                    ok = false;
                }

                if (!ok) {
                    const firstErr = document.querySelector('#template_name-error:not(:empty), #first_img-error:not(:empty)');
                    if (firstErr) {
                        const y = firstErr.getBoundingClientRect().top + window.scrollY - 120;
                        window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
                    }
                }
                return ok;
            };

            const scrollToTop = () => {
                const indicator = document.querySelector('.pdf-step-indicator');
                if (!indicator) return;
                window.scrollTo({ top: Math.max(0, indicator.getBoundingClientRect().top + window.scrollY - 10), behavior: 'smooth' });
            };

            const update = () => {
                const current = window.__pdfWizardCurrentStep || 1;

                $steps.forEach((el) => {
                    const s = parseInt(el.getAttribute('data-step') || '0', 10);
                    el.classList.toggle('active', s === current);
                });

                $stepItems.forEach((el) => {
                    const s = parseInt(el.getAttribute('data-step') || '0', 10);
                    el.classList.toggle('active', s === current);
                    el.classList.toggle('completed', s < current);
                });

                if (prevBtn) prevBtn.style.display = (current <= 1) ? 'none' : '';
                if (nav) nav.style.display = (current >= totalSteps) ? 'none' : '';
                if (nextBtn) nextBtn.textContent = 'Next';

                initEditorsForPdfStep(current);
                scrollToTop();
            };

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if ((window.__pdfWizardCurrentStep || 1) === 1) {
                        if (!validateStep1()) return;
                    }
                    if ((window.__pdfWizardCurrentStep || 1) < totalSteps) {
                        window.__pdfWizardCurrentStep = (window.__pdfWizardCurrentStep || 1) + 1;
                        update();
                    }
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if ((window.__pdfWizardCurrentStep || 1) > 1) {
                        window.__pdfWizardCurrentStep = (window.__pdfWizardCurrentStep || 1) - 1;
                        update();
                    }
                });
            }

            if (prevInlineBtn) {
                prevInlineBtn.addEventListener('click', () => {
                    if ((window.__pdfWizardCurrentStep || 1) > 1) {
                        window.__pdfWizardCurrentStep = (window.__pdfWizardCurrentStep || 1) - 1;
                        update();
                    }
                });
            }

            window.__pdfWizardCurrentStep = 1;
            update();

            const templateNameEl = document.getElementById('template_name');
            if (templateNameEl) templateNameEl.addEventListener('input', clearStep1Errors);
            const fileInput = document.querySelector('input[name="first_img"]');
            if (fileInput) fileInput.addEventListener('change', clearStep1Errors);
        }
        
        const firstImgInput = document.querySelector('input[name="first_img"]');
        if (firstImgInput) {
            firstImgInput.addEventListener('change', (e) => {
                if (firstImgInput.files && firstImgInput.files[0] && firstImgInput.files[0].type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const wrapper = firstImgInput.closest('.mb-3');
                        if (!wrapper) return;
                        let img = wrapper.querySelector('img');
                        if (img) {
                            img.src = evt.target.result;
                        } else {
                            const label = wrapper.querySelector('label');
                            if (label) {
                                const previewHtml = `
                                    <div class="mb-2">
                                        <span class="small text-muted d-block">Preview:</span>
                                        <img src="${evt.target.result}" style="width: 300px; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                    </div>
                                `;
                                label.insertAdjacentHTML('afterend', previewHtml);
                            }
                        }
                        attachRemoveButton(wrapper, firstImgInput, 'first_img_existing');
                        
                        let delFlag = wrapper.querySelector('input[name="delete_first_img"]');
                        if (delFlag) delFlag.value = '0';
                    };
                    reader.readAsDataURL(firstImgInput.files[0]);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            syncAllCkeditorsToTextareas();

            const submitButton = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);

            form.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
            form.querySelectorAll('[id$="-error"]').forEach(element => {
                element.textContent = '';
                element.classList.remove('d-block');
            });

            if (typeof window.buttonLoader === 'function' && submitButton) {
                window.buttonLoader($(submitButton), 'Saving...', true);
            }

            $.ajax({
                url: form.getAttribute('action'),
                type: form.getAttribute('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                success: function (response) {
                    if (typeof window.buttonLoader === 'function' && submitButton) {
                        window.buttonLoader($(submitButton), '', false);
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Template saved successfully.', '', response.redirect || '/pdfbuilder');
                    }
                },
                error: function (xhr) {
                    if (typeof window.buttonLoader === 'function' && submitButton) {
                        window.buttonLoader($(submitButton), '', false);
                    }

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(([key, messages]) => {
                            const field = form.querySelector(`[name="${key}"]`);
                            const errorElement = document.getElementById(`${key}-error`);

                            if (field) {
                                field.classList.add('is-invalid');
                            }

                            if (errorElement) {
                                errorElement.textContent = messages[0];
                            }
                        });

                        form.classList.add('was-validated');
                        return;
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', xhr.responseJSON?.message || 'Something went wrong');
                    }
                }
            });
        });

        const observer = new MutationObserver(() => {
            document.querySelectorAll('.cke_notification_warning').forEach(el => el.style.display = 'none');
        });
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Expose functions globally for inline HTML event bindings
    window.addServiceRow = addServiceRow;
    window.removeServiceRow = removeServiceRow;
    window.handleCompanyFileDrop = handleCompanyFileDrop;
    window.showCompanyFileName = showCompanyFileName;

    function initImageRemoveButtons() {
        const form = document.getElementById('pdf-builder-form');
        if (!form) return;
        
        form.querySelectorAll('input[type="file"][accept="image/*"]').forEach(fileInput => {
            const wrapper = fileInput.closest('.mb-3') || fileInput.parentElement;
            let oldInputName = fileInput.name === 'first_img' ? 'first_img_existing' : fileInput.name + '_old';
            attachRemoveButton(wrapper, fileInput, oldInputName);
        });
    }

    // ==================== GLOBAL INITIALIZATION ====================
    $(document).ready(function () {
        initPdfList();
        initPdfForm();
        initImageRemoveButtons();

        $(document).on("click", ".delete-btn", function () {
            deleteTemplate($(this).data("id"), this);
        });

        $(document).on("click", ".btn-user-expand", function () {
            const id = this.dataset.templateId;
            const detailsRow = document.getElementById(`details-${id}`);
            const icon = this.querySelector("i");
            
            if (detailsRow.style.display === "none") {
                detailsRow.style.display = "table-row";
                icon.classList.replace("fa-plus", "fa-minus");
                this.classList.add("active");
            } else {
                detailsRow.style.display = "none";
                icon.classList.replace("fa-minus", "fa-plus");
                this.classList.remove("active");
            }
        });
    });
})();
