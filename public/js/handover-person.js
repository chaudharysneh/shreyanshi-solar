(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const config = window.handoverPersonConfig || {};
        const tableBody = document.querySelector("#handoverPersonTable tbody");
        const searchInput = document.getElementById("handoverPersonSearch");
        const paginationContainer = document.getElementById("handoverPersonPagination");
        const modalEl = document.getElementById("handoverPersonModal");
        const form = document.getElementById("handoverPersonForm");
        const addBtn = document.getElementById("handoverPersonAddBtn");
        const permissions = config.permissions || {
            view: true,
            create: true,
            edit: true,
            delete: true
        };
        const nameInput = document.getElementById("handoverPersonName");
        const phoneInput = document.getElementById("handoverPersonPhone");
        const addressInput = document.getElementById("handoverPersonAddress");
        let modal = null;
        let searchTimer = null;

        if (!tableBody || !searchInput || !paginationContainer || !modalEl || !form || !addBtn) return;

        function notify(message, type) {
            if (typeof window.showAlert === "function") {
                window.showAlert(type || "info", message);
                return;
            }

            alert(message);
        }

        function buildUrl(template, id) {
            return String(template || "").replace("__ID__", id);
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
            if (value === null || value === undefined || value === "") return "-";

            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renderRows(items, meta) {
            if (!items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No handover persons found.</p>
                            ${permissions.create ? `<button class="btn btn-dark-blue btn-sm rounded-pill px-4" id="handoverPersonEmptyAddBtn">Add Handover Person</button>` : ''}
                        </td>
                    </tr>
                `;

                document.getElementById("handoverPersonEmptyAddBtn")?.addEventListener("click", openModal);
                return;
            }

            tableBody.innerHTML = items.map((item, index) => {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const name = escapeHtml(item.name);
                const phone = escapeHtml(item.phone);
                const address = escapeHtml(item.address);
                const createdAt = escapeHtml(formatDate(item.created_at));

                return `
                    <tr>
                        <td class="ps-4"><span class="text-muted small fw-medium">${srNo}</span></td>
                        <td><div class="fw-bold small">${name}</div></td>
                        <td class="d-none d-md-table-cell">${phone}</td>
                        <td class="d-none d-md-table-cell">${address}</td>
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-center d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
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
                    <tr class="details-row d-md-none border" id="handoverPerson-details-${item.id}" style="display:none;">
                        <td colspan="7" class="p-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                        <div class="expand-value text-end">${phone}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-location-dot"></i> Address :</div>
                                        <div class="expand-value text-end">${address}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gears"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<button type="button" class="btn crm-action-btn btn-sm editRecord" data-id="${item.id}" title="Edit"><i class="bi bi-pencil"></i></button>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteRecord" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
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
                    const detailsRow = document.getElementById(`handoverPerson-details-${this.dataset.id}`);
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

            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

            try {
                const response = await api(`${config.indexUrl}?${params.toString()}`);
                renderRows(response?.data?.data || [], response?.data);
                renderPagination(response?.data);
            } catch (_) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Error loading handover persons.</td></tr>';
                paginationContainer.innerHTML = "";
            }
        }

        function clearErrors() {
            [nameInput, phoneInput, addressInput].forEach((input) => input?.classList.remove("is-invalid"));
            document.getElementById("handoverPersonNameError").textContent = "";
            document.getElementById("handoverPersonPhoneError").textContent = "";
            document.getElementById("handoverPersonAddressError").textContent = "";
        }

        function showErrors(errors) {
            Object.entries(errors || {}).forEach(([field, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : messages;

                if (field === "name") {
                    nameInput.classList.add("is-invalid");
                    document.getElementById("handoverPersonNameError").textContent = message;
                }

                if (field === "phone") {
                    phoneInput.classList.add("is-invalid");
                    document.getElementById("handoverPersonPhoneError").textContent = message;
                }

                if (field === "address") {
                    addressInput.classList.add("is-invalid");
                    document.getElementById("handoverPersonAddressError").textContent = message;
                }
            });
        }

        function resetForm() {
            form.reset();
            form.action = config.storeUrl;
            form.dataset.submitMethod = "POST";
            document.getElementById("handoverPersonModalTitle").textContent = "Add Handover Person";
            document.getElementById("handoverPersonSubmitBtn").textContent = "Save";
            document.getElementById("handoverPersonFormMethod").value = "";
            clearErrors();
        }

        function openModal() {
            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            resetForm();
            modal.show();
            nameInput?.focus();
        }

        async function editRecord(id) {
            try {
                const response = await api(buildUrl(config.showUrlTemplate, id));
                const item = response?.data || {};

                modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                resetForm();
                document.getElementById("handoverPersonModalTitle").textContent = "Edit Handover Person";
                document.getElementById("handoverPersonSubmitBtn").textContent = "Update";
                form.action = buildUrl(config.updateUrlTemplate, id);
                form.dataset.submitMethod = "PUT";
                document.getElementById("handoverPersonFormMethod").value = "PUT";
                nameInput.value = item.name || "";
                phoneInput.value = item.phone || "";
                addressInput.value = item.address || "";
                modal.show();
            } catch (error) {
                notify(error.payload?.message || "Unable to load handover person.", "error");
            }
        }

        addBtn.addEventListener("click", function (event) {
            event.preventDefault();
            openModal();
        });

        modalEl.addEventListener("hidden.bs.modal", resetForm);

        document.addEventListener("click", async function (event) {
            const editBtn = event.target.closest(".editRecord");
            const deleteBtn = event.target.closest(".deleteRecord");

            if (editBtn) {
                event.preventDefault();
                editRecord(editBtn.dataset.id);
                return;
            }

            if (deleteBtn) {
                event.preventDefault();
                const result = await window.showDeleteConfirm("Delete this handover person?");
                if (!result.isConfirmed) return;

                const formData = new FormData();
                formData.append("_method", "DELETE");

                try {
                    const response = await api(buildUrl(config.destroyUrlTemplate, deleteBtn.dataset.id), {
                        method: "POST",
                        body: formData,
                    });
                    notify(response?.message || "Handover person deleted successfully.", "success");
                    fetchRecords(1, searchInput.value.trim());
                } catch (error) {
                    notify(error.payload?.message || "Unable to delete handover person.", "error");
                }
            }
        });

        form.addEventListener("submit", async function (event) {
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
                notify(response?.message || "Handover person saved successfully.", "success");
                modal?.hide();
                fetchRecords(1, searchInput.value.trim());
            } catch (error) {
                if (error.payload?.errors) {
                    showErrors(error.payload.errors);
                    return;
                }

                notify(error.payload?.message || "Unable to save handover person.", "error");
            }
        });

        [nameInput, phoneInput, addressInput].forEach((input) => {
            input?.addEventListener("input", function () {
                this.classList.remove("is-invalid");

                if (this === nameInput) document.getElementById("handoverPersonNameError").textContent = "";
                if (this === phoneInput) document.getElementById("handoverPersonPhoneError").textContent = "";
                if (this === addressInput) document.getElementById("handoverPersonAddressError").textContent = "";
            });
        });

        phoneInput?.addEventListener("input", function () {
            const digits = this.value.replace(/\D/g, "").slice(0, 10);
            if (this.value !== digits) {
                this.value = digits;
            }
        });

        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchRecords(1, searchInput.value.trim()), 300);
        });

        fetchRecords();
    }
})();
