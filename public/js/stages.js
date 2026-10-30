(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const API_BASE_URL = "/api/masters/stages";
        const permissions = window.crmUserPermissions?.stages || {};
        const tableBody = document.querySelector("#stagesTable tbody");
        const searchInput = document.getElementById("stageSearch");
        const paginationContainer = document.getElementById("stagesPaginationContainer");
        const modalEl = document.getElementById("stageModal");
        const form = document.getElementById("stageForm");
        let searchTimer = null;
        let modal = null;

        if (!tableBody || !searchInput || !modalEl || !form) {
            return;
        }

        function showToast(message, type = "info") {
            const mappedType = {
                success: "success",
                error: "error",
                warning: "warning",
                info: "info",
            }[type] || "info";

            if (typeof window.showAlert === "function") {
                window.showAlert(mappedType, message);
                return;
            }

            alert(message);
        }

        async function api(url, options = {}) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const headers = {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...options.headers,
            };

            if (csrf) {
                headers["X-CSRF-TOKEN"] = csrf;
            }

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

        function formatStatus(status) {
            return status === "inactive"
                ? { label: "Inactive", className: "bg-secondary-subtle text-secondary" }
                : { label: "Active", className: "bg-success-subtle text-success" };
        }

        function formatDate(value) {
            if (!value) {
                return "-";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "-";
            }

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return "";
            }

            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renderRows(stages, meta) {
            if (!stages.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No stages found in the directory.</p>
                            ${permissions.create ? '<button class="btn btn-dark-blue btn-sm rounded-pill px-4 addStageBtn">Create Your First Stage</button>' : ''}
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = stages
                .map((stage, index) => {
                    const status = formatStatus(stage.status);
                    const rawStageName = stage.name ?? "-";
                    const stageName = escapeHtml(rawStageName);
                    const createdAt = escapeHtml(formatDate(stage.created_at));
                    const rowNumber = meta && meta.from ? meta.from + index : index + 1;

                    return `
                        <tr>
                            <td class="ps-4">
                                <span class="text-muted small fw-medium">${rowNumber}</span>
                            </td>
                            <td class="ps-4">
                                <div class="fw-bold small">${stageName}</div>
                            </td>
                            <td class="text-center d-none d-md-table-cell">
                                <span class="badge crm-status-pill rounded-pill ${status.className}">${status.label}</span>
                            </td>
                            <td class="text-muted d-none d-md-table-cell">${createdAt}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center gap-2">
                                    ${permissions.edit ? `
                                    <button
                                        type="button"
                                        class="btn crm-action-btn btn-sm editStage"
                                        data-id="${stage.id}"
                                        data-name="${escapeHtml(stage.name ?? "")}"
                                        data-status="${stage.status ?? "active"}"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>` : ''}
                                    ${permissions.delete ? `
                                    <button
                                        type="button"
                                        class="btn crm-action-btn btn-sm text-danger deleteStage"
                                        data-id="${stage.id}"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>` : ''}
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-stage-id="${stage.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${stage.id}" style="display: none;">
                            <td colspan="6" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                            <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                            <div class="expand-value text-end">
                                                <span class="badge crm-status-pill rounded-pill ${status.className}">${status.label}</span>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                            <div class="expand-value text-end">${createdAt}</div>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `
                                            <button
                                                type="button"
                                                class="btn crm-action-btn btn-sm editStage"
                                                data-id="${stage.id}"
                                                data-name="${escapeHtml(stage.name ?? "")}"
                                                data-status="${stage.status ?? "active"}"
                                                title="Edit"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>` : ""}
                                            ${permissions.delete ? `
                                            <button
                                                type="button"
                                                class="btn crm-action-btn btn-sm text-danger deleteStage"
                                                data-id="${stage.id}"
                                                title="Delete"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>` : ""}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                })
                .join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.stageId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector("i");

                    if (!detailsRow) {
                        return;
                    }

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
            if (!paginationContainer) {
                return;
            }

            if (!data || data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
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
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += "</ul></div>";
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchStages(Number(this.dataset.page), searchInput.value.trim());
                });
            });
        }

        async function fetchStages(page = 1, query = "") {
            const params = new URLSearchParams({ include_inactive: "1" });
            params.set("page", page);

            if (query) {
                params.set("search", query);
            }

            const url = `${API_BASE_URL}?${params.toString()}`;

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </td>
                </tr>
            `;

            try {
                const response = await api(url, { method: "GET" });
                renderRows(response?.data?.data || [], response?.data);
                renderPagination(response?.data);
            } catch (_) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Error loading stages. Please try again.
                        </td>
                    </tr>
                `;
                if (paginationContainer) {
                    paginationContainer.innerHTML = "";
                }
            }
        }

        function clearErrors() {
            const nameInput = document.getElementById("stageName");
            const nameError = document.getElementById("name-error");
            const statusInput = document.getElementById("stageStatus");
            const statusError = document.getElementById("status-error");

            if (nameInput) {
                nameInput.classList.remove("is-invalid");
            }
            if (nameError) {
                nameError.textContent = "";
            }
            if (statusInput) {
                statusInput.classList.remove("is-invalid");
            }
            if (statusError) {
                statusError.textContent = "";
            }
        }

        function showErrors(errors) {
            Object.keys(errors || {}).forEach((field) => {
                const input = field === "name"
                    ? document.getElementById("stageName")
                    : field === "status"
                        ? document.getElementById("stageStatus")
                        : null;
                const errorDiv = field === "name"
                    ? document.getElementById("name-error")
                    : field === "status"
                        ? document.getElementById("status-error")
                        : null;

                if (input) {
                    input.classList.add("is-invalid");
                }
                if (errorDiv) {
                    errorDiv.textContent = errors[field][0];
                }
            });
        }

        function resetStageForm() {
            const titleEl = document.getElementById("stageModalTitle");
            const methodInput = document.getElementById("stageFormMethod");
            const nameInput = document.getElementById("stageName");
            const statusInput = document.getElementById("stageStatus");
            const submitBtn = document.getElementById("stageSubmitBtn");

            form.reset();
            form.setAttribute("action", API_BASE_URL);
            form.dataset.submitMethod = "POST";

            if (methodInput) {
                methodInput.value = "";
                methodInput.disabled = true;
            }
            if (titleEl) {
                titleEl.textContent = "Add Stage";
            }
            if (submitBtn) {
                submitBtn.textContent = "Save";
            }
            if (nameInput) {
                nameInput.value = "";
            }
            if (statusInput) {
                statusInput.value = "active";
            }

            clearErrors();
        }

        function openStageModal(config = {}) {
            const titleEl = document.getElementById("stageModalTitle");
            const methodInput = document.getElementById("stageFormMethod");
            const nameInput = document.getElementById("stageName");
            const statusInput = document.getElementById("stageStatus");
            const submitBtn = document.getElementById("stageSubmitBtn");

            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            resetStageForm();

            if (titleEl) {
                titleEl.textContent = config.title || "Add Stage";
            }
            form.setAttribute("action", config.action || API_BASE_URL);
            form.dataset.submitMethod = config.method || "POST";

            if (methodInput) {
                methodInput.value = config.method === "PUT" ? "PUT" : "";
                methodInput.disabled = config.method !== "PUT";
            }
            if (nameInput) {
                nameInput.value = config.name || "";
            }
            if (statusInput) {
                statusInput.value = config.status || "active";
            }
            if (submitBtn) {
                submitBtn.textContent = config.submitText || "Save";
            }

            modal.show();
            if (nameInput) {
                nameInput.focus();
            }
        }

        modalEl.addEventListener("hidden.bs.modal", resetStageForm);

        document.addEventListener("click", async (event) => {
            const addBtn = event.target.closest(".addStageBtn");
            const editBtn = event.target.closest(".editStage");
            const deleteBtn = event.target.closest(".deleteStage");

            if (addBtn) {
                event.preventDefault();
                openStageModal({
                    title: "Add Stage",
                    action: API_BASE_URL,
                    method: "POST",
                    status: "active",
                    submitText: "Save",
                });
                return;
            }

            if (editBtn) {
                event.preventDefault();
                openStageModal({
                    title: "Edit Stage",
                    action: `${API_BASE_URL}/${editBtn.dataset.id}`,
                    method: "PUT",
                    name: editBtn.dataset.name || "",
                    status: editBtn.dataset.status || "active",
                    submitText: "Update",
                });
                return;
            }

            if (deleteBtn) {
                event.preventDefault();
                const result = await window.showDeleteConfirm("Delete this stage?");
                if (!result.isConfirmed) {
                    return;
                }

                const body = new FormData();
                body.append("_method", "DELETE");

                try {
                    const response = await api(`${API_BASE_URL}/${deleteBtn.dataset.id}`, {
                        method: "POST",
                        body,
                    });
                    showToast(response?.message || "Stage deleted successfully.", "success");
                    fetchStages(1, searchInput.value.trim());
                } catch (error) {
                    showToast(error.payload?.message || "Unable to delete stage.", "error");
                }
            }
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            clearErrors();

            const body = new FormData(form);
            const action = form.getAttribute("action") || API_BASE_URL;
            const submitMethod = form.dataset.submitMethod || "POST";
            const isEdit = submitMethod === "PUT";
            const submittedStatus = body.get("status");

            body.delete("_method");
            if (isEdit) {
                body.append("_method", "PUT");
            }

            try {
                const response = await api(action, {
                    method: "POST",
                    body,
                });

                if (isEdit) {
                    const statusLabel = formatStatus(submittedStatus).label;
                    showToast(response?.message || `Stage updated successfully. Status set to ${statusLabel}.`, "success");
                } else {
                    showToast(response?.message || "Stage created successfully.", "success");
                }

                if (modal) {
                    modal.hide();
                }

                fetchStages(1, searchInput.value.trim());
            } catch (error) {
                if (error.payload?.errors) {
                    showErrors(error.payload.errors);
                    return;
                }

                showToast(error.payload?.message || "Unable to save stage.", "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                fetchStages(1, searchInput.value.trim());
            }, 300);
        });

        document.addEventListener("input", (event) => {
            const input = event.target.closest("#stageName");
            if (!input) {
                return;
            }

            input.classList.remove("is-invalid");
            const errorDiv = document.getElementById("name-error");
            if (errorDiv) {
                errorDiv.textContent = "";
            }
        });

        document.addEventListener("change", (event) => {
            const input = event.target.closest("#stageStatus");
            if (!input) {
                return;
            }

            input.classList.remove("is-invalid");
            const errorDiv = document.getElementById("status-error");
            if (errorDiv) {
                errorDiv.textContent = "";
            }
        });

        resetStageForm();
        fetchStages();
    }
})();
