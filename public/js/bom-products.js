(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        initIndex();
    }

    function notify(message, type = "info", redirectUrl = null) {
        if (typeof window.showAlert === "function") {
            window.showAlert(type, message, "", redirectUrl);
            return;
        }

        alert(message);
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

    function initIndex() {
        const config = window.bomProductsConfig || {};
        const tableBody = document.querySelector("#bomProductsTable tbody");
        const searchInput = document.getElementById("bomProductsSearch");
        const paginationContainer = document.getElementById("bomProductsPagination");
        const permissions = window.crmUserPermissions?.bom || {};

        let searchTimer = null;

        initQuickBom();

        if (!config.indexUrl || !tableBody || !searchInput || !paginationContainer) return;

        function buildUrl(template, id) {
            return (template || "").replace("__ID__", id);
        }

        function initQuickBom() {
            const modalElement = document.getElementById("quickBomModal");
            const form = document.getElementById("quickBomForm");
            const submitButton = document.getElementById("saveQuickBomBtn");
            if (!modalElement || !form || !submitButton || !config.storeUrl) return;

            const $ = window.jQuery;
            if ($?.fn?.select2) {
                form.querySelectorAll(".quick-bom-select").forEach((select) => {
                    const options = {
                        theme: "bootstrap-5",
                        width: "100%",
                        allowClear: !select.multiple,
                        placeholder: select.dataset.placeholder || "Select",
                        dropdownParent: $(modalElement),
                    };
                    if (select.classList.contains("quick-bom-creatable")) {
                        options.tags = true;
                        options.createTag = function (params) {
                            const term = params.term.trim();
                            return term ? { id: `__new__:${term}`, text: term, newTag: true } : null;
                        };
                        options.templateResult = function (item) {
                            if (item.newTag) return $(`<span><i class="bi bi-plus-circle me-1"></i>Create “${escapeHtml(item.text)}”</span>`);
                            return item.text;
                        };
                    }
                    $(select).select2(options);
                });
            }

            async function createMaster(url, field, value) {
                const body = new FormData();
                body.append(field, value);
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    body,
                    credentials: "same-origin",
                });
                const payload = await response.json();
                if (!response.ok) {
                    const firstError = Object.values(payload?.errors || {})[0]?.[0];
                    throw new Error(firstError || payload?.message || `Unable to create ${value}.`);
                }
                return payload.data;
            }

            async function resolveNewOptions(select, url, field, labelField) {
                const selected = Array.from(select.selectedOptions);
                for (const option of selected) {
                    if (!option.value.startsWith("__new__:")) continue;
                    const title = option.value.slice(8).trim();
                    const created = await createMaster(url, field, title);
                    option.value = String(created.id);
                    option.textContent = created[labelField] || title;
                    option.dataset.select2Tag = "false";
                }
                if ($?.fn?.select2) $(select).trigger("change.select2");
            }

            function clearErrors() {
                form.querySelectorAll(".is-invalid").forEach((field) => field.classList.remove("is-invalid"));
                form.querySelectorAll(".select2-selection.is-invalid").forEach((field) => field.classList.remove("is-invalid"));
                form.querySelectorAll("[data-error-for]").forEach((field) => {
                    field.textContent = "";
                    field.style.display = "";
                });
            }

            function showError(name, message) {
                const normalizedName = name.replace(/\.\d+$/, "");
                const input = form.querySelector(`[name="${normalizedName}"], [name="${normalizedName}[]"]`);
                if (input) {
                    input.classList.add("is-invalid");
                    input.nextElementSibling?.querySelector?.(".select2-selection")?.classList.add("is-invalid");
                }
                const feedback = form.querySelector(`[data-error-for="${normalizedName}"]`);
                if (feedback) {
                    feedback.textContent = message;
                    feedback.style.display = "block";
                }
            }

            form.addEventListener("submit", async (event) => {
                event.preventDefault();
                clearErrors();

                let valid = true;
                if (!form.elements.product_name.value.trim()) { showError("product_name", "Please enter the BOM name."); valid = false; }
                if (!valid) return;

                submitButton.disabled = true;
                submitButton.querySelector(".spinner-border")?.classList.remove("d-none");
                submitButton.querySelector(".button-text").textContent = "Adding...";

                try {
                    await resolveNewOptions(form.elements["category_id[]"], config.makeStoreUrl, "name", "name");
                    await resolveNewOptions(form.elements.technology_id, config.technologyStoreUrl, "title", "title");
                    await resolveNewOptions(form.elements.warranty_id, config.warrantyStoreUrl, "title", "title");

                    const formData = new FormData(form);
                    formData.append("quick_bom", "1");

                    const response = await fetch(config.storeUrl, {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                        },
                        body: formData,
                        credentials: "same-origin",
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        Object.entries(payload?.errors || {}).forEach(([name, messages]) => showError(name, messages[0]));
                        throw new Error(payload?.message || (response.status === 422 ? "Please correct the highlighted fields." : "Unable to add BOM."));
                    }

                    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    form.reset();
                    if ($?.fn?.select2) $(form).find(".quick-bom-select").val(null).trigger("change");
                    notify(payload?.message || "BOM product created successfully.", "success");
                    if (searchInput) {
                        searchInput.value = "";
                        fetchProducts(1);
                    }
                } catch (error) {
                    notify(error.message || "Unable to add BOM.", "error");
                } finally {
                    submitButton.disabled = false;
                    submitButton.querySelector(".spinner-border")?.classList.add("d-none");
                    submitButton.querySelector(".button-text").textContent = "Add BOM";
                }
            });

            modalElement.addEventListener("hidden.bs.modal", clearErrors);

            const pageUrl = new URL(window.location.href);
            if (pageUrl.searchParams.get("quick_bom") === "1") {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
                pageUrl.searchParams.delete("quick_bom");
                window.history.replaceState({}, document.title, pageUrl.toString());
            }
        }

        function renderRows(items, meta) {
            if (!items?.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No BOM records found.</p>
                            ${permissions.create ? '<a href="/add-product" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add BOM</a>' : ''}
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map((item, index) => {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const name = escapeHtml(item.product_name || "-");
                const hsnSac = escapeHtml(item.hsn_sac ?? "") || "-";
                const makes = item.categories?.map(c => escapeHtml(c.name)).join(", ") || "-";
                const technology = escapeHtml(item.technology?.title || "-");
                const warranty = escapeHtml(item.warranty?.title || "-");
                const createdAt = escapeHtml(formatDate(item.created_at));

                return `
                    <tr>
                        <td class="ps-4"><span class="text-muted small fw-medium">${srNo}</span></td>
                        <td><div class="fw-bold small">${name}</div></td>
                        <td class="d-none d-md-table-cell">${hsnSac}</td>
                        <td class="d-none d-md-table-cell">${makes}</td>
                        <td class="d-none d-md-table-cell">${technology}</td>
                        <td class="d-none d-md-table-cell">${warranty}</td>
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-center d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                ${permissions.edit ? `<a href="${buildUrl(config.editUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="${buildUrl(config.showUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteBom" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-id="${item.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border" id="bom-details-${item.id}" style="display:none;">
                        <td colspan="9" class="p-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-hashtag"></i> HSN/SAC :</div>
                                        <div class="expand-value text-end">${hsnSac}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> MAKE :</div>
                                        <div class="expand-value text-end">${makes}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-microchip"></i> TECHNOLOGY :</div>
                                        <div class="expand-value text-end">${technology}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> WARRANTY :</div>
                                        <div class="expand-value text-end">${warranty}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> CREATED :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gears"></i> ACTIONS :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<a href="${buildUrl(config.editUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                            ${permissions.view ? `<a href="${buildUrl(config.showUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteBom" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const detailsRow = document.getElementById(`bom-details-${this.dataset.id}`);
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
                    fetchProducts(Number(link.dataset.page));
                });
            });
        }

        async function fetchProducts(page = 1) {
            if (window.moduleListFilters && !window.moduleListFilters.bound) { window.moduleListFilters.bound = true; window.moduleListFilters.bind(fetchProducts, 'bomProductsSearch'); }
            const params = new URLSearchParams({ page, ...(window.moduleListFilters?.values() || {}) });
            if (searchInput.value.trim()) params.set("search", searchInput.value.trim());

            tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;

            try {
                const response = await fetch(`${config.indexUrl}?${params.toString()}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload?.message || "Request failed");

                renderRows(payload?.data?.data || [], payload?.data);
                renderPagination(payload?.data);
            } catch (_) {
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted">Error loading BOM records.</td></tr>`;
                paginationContainer.innerHTML = "";
            }
        }

        document.addEventListener("click", async (event) => {
            const deleteBtn = event.target.closest(".deleteBom");
            if (!deleteBtn) return;

            event.preventDefault();
            const result = await window.showDeleteConfirm("This BOM record will be deleted!");
            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append("_method", "DELETE");

            try {
                const response = await fetch(buildUrl(config.destroyUrlTemplate, deleteBtn.dataset.id), {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    body: formData,
                    credentials: "same-origin",
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload?.message || "Request failed");

                notify(payload?.message || "BOM product deleted successfully.", "success");
                fetchProducts(1);
            } catch (error) {
                notify(error.message || "Unable to delete BOM product.", "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchProducts(1), 300);
        });

        fetchProducts(1);
    }
})();
