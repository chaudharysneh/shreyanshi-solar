// Fixed: Removed syntax error from template literal - v2
(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const config = window.inventoryMasterConfig || {};
        const moduleKey = config.moduleKey;
        const permissions = config.permissions || {
            view: true,
            create: true,
            edit: true,
            delete: true
        };

        if (!moduleKey) return;

        const dummyImageUrl = config.dummyImageUrl || "";

        const tableBody = document.querySelector(`#${moduleKey}Table tbody`);
        const searchInput = document.getElementById(`${moduleKey}Search`);
        const paginationContainer = document.getElementById(`${moduleKey}Pagination`);
        const modalEl = document.getElementById(`${moduleKey}Modal`);
        const form = document.getElementById(`${moduleKey}Form`);
        const addBtn = document.getElementById(`${moduleKey}AddBtn`);
        let modal = null;
        let searchTimer = null;

        if (!tableBody || !searchInput || !paginationContainer || !modalEl || !form || !addBtn) return;

        function notify(message, type = "info") {
            if (typeof window.showAlert === "function") {
                window.showAlert(type, message);
                return;
            }
            alert(message);
        }

        function buildUrl(template, id) {
            return (template || "").replace("__ID__", id);
        }

        async function api(url, options = {}) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const headers = {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...options.headers,
            };

            if (csrf) headers["X-CSRF-TOKEN"] = csrf;

            const response = await fetch(url, {
                credentials: "same-origin",
                ...options,
                headers,
            });

            const payload = response.status === 204 ? null : await response.json().catch(() => null);

            if (!response.ok) {
                const error = new Error(payload?.message || "Request failed");
                error.payload = payload;
                throw error;
            }

            return payload;
        }

        function formatDate(value) {
            if (!value) return "-";
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return "-";
            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return "";

            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function imageCell(item) {
            if (!config.hasImage) return "";
            const imageUrl = item.image_url || dummyImageUrl;
            if (!imageUrl) {
                return `<div class="d-flex align-items-center justify-content-center bg-light rounded" style="height:48px;width:48px;">
                            <i class="bi bi-image text-muted"></i>
                        </div>`;
            }

            const fallbackAttr = dummyImageUrl
                ? ` onerror="this.onerror=null;this.src='${escapeHtml(dummyImageUrl)}';"`
                : ` onerror="this.style.display='none'"`;

            return `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(item[config.fieldName] || config.resourceTitle)}" class="img-thumbnail" style="height:48px;width:48px;object-fit:contain;" loading="lazy"${fallbackAttr}>`;
        }

        function columnCount() {
            return 4 + (config.hasDescription ? 1 : 0) + (config.hasImage ? 1 : 0);
        }

        function renderRows(items, meta) {
            if (!items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="${columnCount()}" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No ${config.resourcePlural.toLowerCase()} found.</p>
                            ${permissions.create ? `<button class="btn btn-dark-blue btn-sm rounded-pill px-4" id="${moduleKey}EmptyAddBtn">Add ${config.resourceTitle}</button>` : ''}
                        </td>
                    </tr>
                `;
                document.getElementById(`${moduleKey}EmptyAddBtn`)?.addEventListener("click", () => openModal());
                return;
            }

            tableBody.innerHTML = items.map((item, index) => {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const name = escapeHtml(item[config.fieldName] || "-");
                const description = escapeHtml(item.description || "-");
                const createdAt = escapeHtml(formatDate(item.created_at));

                return `
                    <tr>
                        <td class="ps-4"><span class="text-muted small fw-medium">${srNo}</span></td>
                        ${config.hasImage ? `<td class="d-none d-md-table-cell">${imageCell(item)}</td>` : ""}
                        <td><div class="fw-bold small">${name}</div></td>
                        ${config.hasDescription ? `<td class="d-none d-md-table-cell">${description}</td>` : ""}
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                ${permissions.edit ? `<button type="button" class="btn crm-action-btn btn-sm editRecord" data-id="${item.id}" title="Edit"><i class="bi bi-pencil"></i></button>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteRecord" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-id="${item.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="${moduleKey}-details-${item.id}" style="display:none;">
                        <td colspan="${columnCount()}" class="p-0 border-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    ${config.hasDescription ? `<div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label"><i class="fa-solid fa-align-left"></i> Description :</div><div class="expand-value text-end">${description}</div></div>` : ""}
                                    ${config.hasImage ? `<div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label"><i class="fa-regular fa-image"></i> Image :</div><div class="expand-value text-end">${imageCell(item)}</div></div>` : ""}
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3"><div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div><div class="expand-value text-end">${createdAt}</div></div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<button type="button" class="btn crm-action-btn btn-sm editRecord" data-id="${item.id}" title="Edit"><i class="bi bi-pencil"></i></button>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteRecord" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const detailsRow = document.getElementById(`${moduleKey}-details-${this.dataset.id}`);
                    const icon = this.querySelector("i");
                    if (!detailsRow) return;

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
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} results</div><ul class="pagination crm-pagination mb-0">`;
            html += data.prev_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>` : `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += data.next_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>` : `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            html += "</ul></div>";
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
                link.addEventListener("click", (event) => {
                    event.preventDefault();
                    fetchRecords(Number(link.dataset.page), searchInput.value.trim());
                });
            });
        }

        async function fetchRecords(page = 1, query = "") {
            const params = new URLSearchParams({ page });
            if (query) params.set("search", query);

            tableBody.innerHTML = `<tr><td colspan="${columnCount()}" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;

            try {
                const url = `${config.indexUrl}?${params.toString()}`;
                console.log('Fetching from:', url);
                const response = await api(url);
                console.log('API Response:', response);
                renderRows(response?.data?.data || [], response?.data);
                renderPagination(response?.data);
            } catch (error) {
                console.error('Error fetching records:', error);
                tableBody.innerHTML = `<tr><td colspan="${columnCount()}" class="text-center py-5 text-muted">Error loading ${config.resourcePlural.toLowerCase()}.</td></tr>`;
                paginationContainer.innerHTML = "";
            }
        }

        function clearErrors() {
            document.getElementById(`${moduleKey}Field`)?.classList.remove("is-invalid");
            const fieldError = document.getElementById(`${moduleKey}FieldError`);
            if (fieldError) fieldError.textContent = "";
            if (config.hasDescription) {
                document.getElementById(`${moduleKey}Description`)?.classList.remove("is-invalid");
                document.getElementById(`${moduleKey}DescriptionError`).textContent = "";
            }
            if (config.hasImage) {
                document.getElementById(`${moduleKey}Image`)?.classList.remove("is-invalid");
                document.getElementById(`${moduleKey}ImageError`).textContent = "";
            }
        }

        function showErrors(errors) {
            Object.entries(errors || {}).forEach(([field, messages]) => {
                let input = null;
                let errorDiv = null;

                if (field === config.fieldName) {
                    input = document.getElementById(`${moduleKey}Field`);
                    errorDiv = document.getElementById(`${moduleKey}FieldError`);
                } else if (field === "description" && config.hasDescription) {
                    input = document.getElementById(`${moduleKey}Description`);
                    errorDiv = document.getElementById(`${moduleKey}DescriptionError`);
                } else if (field === "image" && config.hasImage) {
                    input = document.getElementById(`${moduleKey}Image`);
                    errorDiv = document.getElementById(`${moduleKey}ImageError`);
                }

                if (input) input.classList.add("is-invalid");
                if (errorDiv) errorDiv.textContent = messages[0];
            });
        }

        function resetForm() {
            form.reset();
            form.action = config.storeUrl;
            form.dataset.submitMethod = "POST";
            document.getElementById(`${moduleKey}ModalTitle`).textContent = `Add ${config.resourceTitle}`;
            document.getElementById(`${moduleKey}SubmitBtn`).textContent = "Save";
            document.getElementById(`${moduleKey}FormMethod`).value = "";
            clearErrors();

            if (config.hasImage) {
                const previewWrap = document.getElementById(`${moduleKey}ImagePreviewWrap`);
                const preview = document.getElementById(`${moduleKey}ImagePreview`);
                if (preview && dummyImageUrl) {
                    preview.src = dummyImageUrl;
                    preview.dataset.defaultSrc = dummyImageUrl;
                    preview.onerror = function () {
                        this.onerror = null;
                        this.src = dummyImageUrl;
                    };
                } else if (preview) {
                    preview.src = "";
                    preview.removeAttribute("data-default-src");
                    preview.onerror = null;
                }

                if (dummyImageUrl) {
                    previewWrap?.classList.remove("d-none");
                } else {
                    previewWrap?.classList.add("d-none");
                }
            }
        }

        function openModal() {
            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            resetForm();
            modal.show();
            document.getElementById(`${moduleKey}Field`)?.focus();
        }

        async function editRecord(id) {
            try {
                const response = await api(buildUrl(config.showUrlTemplate, id));
                const item = response?.data;
                modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                resetForm();
                document.getElementById(`${moduleKey}ModalTitle`).textContent = `Edit ${config.resourceTitle}`;
                document.getElementById(`${moduleKey}SubmitBtn`).textContent = "Update";
                document.getElementById(`${moduleKey}Field`).value = item?.[config.fieldName] || "";
                form.action = buildUrl(config.updateUrlTemplate, id);
                form.dataset.submitMethod = "PUT";
                document.getElementById(`${moduleKey}FormMethod`).value = "PUT";

                if (config.hasDescription) document.getElementById(`${moduleKey}Description`).value = item?.description || "";
                if (config.hasImage) {
                    const previewWrap = document.getElementById(`${moduleKey}ImagePreviewWrap`);
                    const preview = document.getElementById(`${moduleKey}ImagePreview`);
                    const imageUrl = item?.image_url || dummyImageUrl;
                    if (preview && imageUrl) {
                        preview.src = imageUrl;
                        preview.dataset.defaultSrc = imageUrl;
                        preview.onerror = dummyImageUrl
                            ? function () {
                                this.onerror = null;
                                this.src = dummyImageUrl;
                            }
                            : null;
                        previewWrap?.classList.remove("d-none");
                    }
                }

                modal.show();
            } catch (error) {
                notify(error.payload?.message || `Unable to load ${config.resourceTitle.toLowerCase()}.`, "error");
            }
        }

        addBtn.addEventListener("click", (event) => {
            event.preventDefault();
            openModal();
        });

        modalEl.addEventListener("hidden.bs.modal", resetForm);

        document.addEventListener("click", async (event) => {
            const editBtn = event.target.closest(".editRecord");
            const deleteBtn = event.target.closest(".deleteRecord");

            if (editBtn) {
                event.preventDefault();
                editRecord(editBtn.dataset.id);
                return;
            }

            if (deleteBtn) {
                event.preventDefault();
                const result = await window.showDeleteConfirm(`Delete this ${config.resourceTitle.toLowerCase()}?`);
                if (!result.isConfirmed) return;

                const formData = new FormData();
                formData.append("_method", "DELETE");

                try {
                    const response = await api(buildUrl(config.destroyUrlTemplate, deleteBtn.dataset.id), {
                        method: "POST",
                        body: formData,
                    });
                    notify(response?.message || `${config.resourceTitle} deleted successfully.`, "success");
                    fetchRecords(1, searchInput.value.trim());
                } catch (error) {
                    notify(error.payload?.message || `Unable to delete ${config.resourceTitle.toLowerCase()}.`, "error");
                }
            }
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            clearErrors();

            const formData = new FormData(form);
            const isEdit = form.dataset.submitMethod === "PUT";
            formData.delete("_method");
            if (isEdit) formData.append("_method", "PUT");

            try {
                const response = await api(form.action || config.storeUrl, {
                    method: "POST",
                    body: formData,
                });
                notify(response?.message || `${config.resourceTitle} saved successfully.`, "success");
                modal?.hide();
                fetchRecords(1, searchInput.value.trim());
            } catch (error) {
                if (error.payload?.errors) {
                    showErrors(error.payload.errors);
                    return;
                }
                notify(error.payload?.message || `Unable to save ${config.resourceTitle.toLowerCase()}.`, "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchRecords(1, searchInput.value.trim()), 300);
        });

        if (config.hasImage) {
            document.getElementById(`${moduleKey}Image`)?.addEventListener("change", function () {
                const file = this.files && this.files[0];
                const previewWrap = document.getElementById(`${moduleKey}ImagePreviewWrap`);
                const preview = document.getElementById(`${moduleKey}ImagePreview`);
                if (!previewWrap || !preview) return;
                if (!file) {
                    if (preview.dataset.defaultSrc || dummyImageUrl) {
                        preview.src = preview.dataset.defaultSrc || dummyImageUrl;
                        previewWrap.classList.remove("d-none");
                    }
                    return;
                }
                const objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                previewWrap.classList.remove("d-none");
                preview.onload = () => URL.revokeObjectURL(objectUrl);
            });
        }

        document.addEventListener("input", (event) => {
            if (event.target.id === `${moduleKey}Field`) {
                event.target.classList.remove("is-invalid");
                document.getElementById(`${moduleKey}FieldError`).textContent = "";
            }
        });

        fetchRecords();
    }
})();
