(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            initTaskTable();
            // Initialize Tom Select for remote searches (assigned_user_id, etc.)
            if (window.initRemoteSelects) {
                window.initRemoteSelects();
            }
        });
    } else {
        initTaskTable();
        // Initialize Tom Select for remote searches (assigned_user_id, etc.)
        if (window.initRemoteSelects) {
            window.initRemoteSelects();
        }
    }

    function initTaskTable() {
        const permissions = window.crmUserPermissions?.tasks || {};
        const tableBody = document.querySelector("#tasksTable tbody");
        const searchInput = document.getElementById("tasksSearch");
        const paginationContainer = document.getElementById("tasksPagination");
        const taskActionModalEl = document.getElementById("taskActionModal");
        const taskActionForm = document.getElementById("taskActionForm");
        const taskActionTaskId = document.getElementById("taskActionTaskId");
        const taskActionNextStatus = document.getElementById("taskActionNextStatus");
        const taskActionComment = document.getElementById("taskActionComment");
        const taskActionFormAlert = document.getElementById("taskActionFormAlert");
        const taskActionStartFields = document.getElementById("taskActionStartFields");
        const taskActionEndFields = document.getElementById("taskActionEndFields");
        const taskActionImages = document.getElementById("taskActionImages");
        const taskActionLightBill = document.getElementById("taskActionLightBill");
        const taskActionMeasurements = document.getElementById("taskActionMeasurements");
        const taskActionSitePhoto = document.getElementById("taskActionSitePhoto");
        const taskActionSubmitBtn = document.getElementById("taskActionSubmitBtn");
        const taskActionSpinner = document.getElementById("taskActionSpinner");
        const taskActionSubmitText = document.getElementById("taskActionSubmitText");
        const taskActionModal = taskActionModalEl && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(taskActionModalEl)
            : null;

        // Accept existing filter links, then keep the selection in history state.
        const urlParams = new URLSearchParams(window.location.search);
        let currentFilter = urlParams.get('filter') || window.history.state?.tasksFilter || 'created_by_me';

        function rememberFilter() {
            const newUrl = new URL(window.location);
            newUrl.searchParams.delete('filter');
            window.history.replaceState(
                { ...window.history.state, tasksFilter: currentFilter }, '', newUrl
            );
        }
        rememberFilter();

        // Activate the selected tab.
        if (currentFilter) {
            document.querySelectorAll('#taskFilterTabs button[data-filter]').forEach(function(tab) {
                if (tab.dataset.filter === currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        // Tab click handlers
        document.querySelectorAll('#taskFilterTabs button[data-filter]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                
                rememberFilter();
                
                fetchTasks(1);
            });
        });

        if (!tableBody || !searchInput || !paginationContainer) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        function priorityClass(priority) {
            return {
                high: "text-danger",
                medium: "text-warning",
                low: "text-info",
            }[priority] || "text-muted";
        }

        function statusClass(status) {
            const normalizedStatus = normalizeTaskStatus(status);

            return {
                completed: "bg-success",
                in_progress: "bg-primary",
                pending: "bg-warning text-dark",
            }[normalizedStatus] || "bg-secondary";
        }

        function normalizeTaskStatus(status) {
            if (!status) {
                return "";
            }

            return String(status)
                .trim()
                .toLowerCase()
                .replace(/[\s-]+/g, "_")
                .replace(/^inprogress$/, "in_progress");
        }

        function formatDate(value) {
            if (!value) {
                return "-";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "-";
            }

            return date.toLocaleDateString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
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

        function formatLabel(value) {
            if (!value) {
                return "-";
            }

            return String(value)
                .replace(/_/g, " ")
                .replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });
        }

        function deleteTask(taskId, button) {
            window.showDeleteConfirm("Delete this task?").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: `/api/tasks/${taskId}`,
                    type: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    success: function (response) {
                        if (response.success) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("success", response.message || "Task deleted successfully.");
                            }
                            fetchTasks();
                        } else {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", response.message || "Failed to delete task.");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        if (typeof window.showAlert === "function") {
                            window.showAlert("error", "Something went wrong. Please try again.");
                        }
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
                });
        }

        function clearTaskActionErrors() {
            if (taskActionFormAlert) {
                taskActionFormAlert.textContent = "";
                taskActionFormAlert.style.display = "none";
            }
        }

        function showTaskActionErrors(errors) {
            if (!taskActionFormAlert) {
                return;
            }

            let message = "Please check the required fields.";
            const fieldMessages = [];
            const firstFieldMessage = function (field) {
                const value = errors[field];
                return Array.isArray(value) && value[0] ? value[0] : null;
            };

            if (errors.images?.length) {
                fieldMessages.push(firstFieldMessage("images"));
            } else if (errors["images.0"]?.length) {
                fieldMessages.push(firstFieldMessage("images.0"));
            }

            if (errors.light_bill?.length) {
                fieldMessages.push(firstFieldMessage("light_bill"));
            }

            if (errors.measurements?.length) {
                fieldMessages.push(firstFieldMessage("measurements"));
            }

            if (errors.site_photo?.length) {
                fieldMessages.push(firstFieldMessage("site_photo"));
            }

            if (errors.location_latitude?.length || errors.location_longitude?.length) {
                fieldMessages.push(firstFieldMessage("location_latitude") || firstFieldMessage("location_longitude"));
            }

            const normalizedMessages = fieldMessages.filter(Boolean);

            if (normalizedMessages.length) {
                message = normalizedMessages.join(" ");
            } else {
                const firstError = Object.values(errors || {})[0];
                if (Array.isArray(firstError) && firstError[0]) {
                    message = firstError[0];
                }
            }

            taskActionFormAlert.textContent = message;
            taskActionFormAlert.style.display = "block";
        }

        function getTaskActionConfig(task) {
            if (task.task_type === "solar_workflow") return null;
            const actionMode = task.task_action_mode || "";
            const normalizedStatus = normalizeTaskStatus(task.status);

            if (actionMode === "start" || normalizedStatus === "pending") {
                return {
                    label: "START",
                    nextStatus: "in_progress",
                    buttonClass: "btn btn-success btn-sm rounded-pill crm-status-pill quick-task-status-btn",
                };
            }

            if (actionMode === "end" || normalizedStatus === "in_progress") {
                return {
                    label: "END",
                    nextStatus: "completed",
                    buttonClass: "btn btn-danger btn-sm rounded-pill crm-status-pill quick-task-status-btn",
                };
            }

            return null;
        }

        function resetTaskActionForm() {
            if (!taskActionForm) {
                return;
            }

            taskActionForm.reset();
            taskActionTaskId.value = "";
            taskActionNextStatus.value = "";
            clearTaskActionErrors();
            taskActionStartFields?.classList.add("d-none");
            taskActionEndFields?.classList.add("d-none");
            taskActionSpinner?.classList.add("d-none");
            if (taskActionSubmitBtn) {
                taskActionSubmitBtn.disabled = false;
            }
            if (taskActionSubmitText) {
                taskActionSubmitText.textContent = "Submit Task";
            }
        }

        function openTaskActionModal(taskId, nextStatus, taskType) {
            if (!taskActionModal || !taskActionForm) {
                return;
            }

            resetTaskActionForm();
            taskActionTaskId.value = taskId;
            taskActionNextStatus.value = nextStatus;

            if (nextStatus === "in_progress") {
                taskActionStartFields?.classList.remove("d-none");
                if (taskActionSubmitText) {
                    taskActionSubmitText.textContent = "Start Task";
                }
            }

            if (nextStatus === "completed") {
                if (taskType === "Site visit") {
                    taskActionEndFields?.classList.remove("d-none");
                } else {
                    taskActionStartFields?.classList.remove("d-none");
                }
                if (taskActionSubmitText) {
                    taskActionSubmitText.textContent = "End Task";
                }
            }

            taskActionModal.show();
        }

        function resolveTaskLocation() {
            return new Promise(function (resolve, reject) {
                if (!navigator.geolocation) {
                    reject(new Error("Location access is required to continue."));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const latitude = position.coords?.latitude;
                        const longitude = position.coords?.longitude;

                        if (typeof latitude !== "number" || typeof longitude !== "number") {
                            reject(new Error("Unable to capture your current location."));
                            return;
                        }

                        const fallbackAddress = `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}`;
                        const endpoint = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`;

                        fetch(endpoint, {
                            headers: {
                                Accept: "application/json",
                            },
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error("Reverse geocoding failed.");
                                }
                                return response.json();
                            })
                            .then(function (payload) {
                                resolve({
                                    latitude: latitude,
                                    longitude: longitude,
                                    address: payload?.display_name || fallbackAddress,
                                });
                            })
                            .catch(function () {
                                resolve({
                                    latitude: latitude,
                                    longitude: longitude,
                                    address: fallbackAddress,
                                });
                            });
                    },
                    function () {
                        reject(new Error("Location access is required to continue."));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            });
        }

        async function quickStatusUpdate() {
            const taskId = taskActionTaskId?.value;
            if (!taskId || !taskActionForm) {
                return;
            }

            let taskLocation;

            try {
                taskLocation = await resolveTaskLocation();
            } catch (error) {
                showTaskActionErrors({
                    location_latitude: [error.message || "Location access is required to continue."],
                });
                return;
            }

            const formData = new FormData();
            const comment = taskActionComment?.value?.trim() || "";
            if (comment) {
                formData.append("comment", comment);
            }
            formData.append("location_latitude", taskLocation.latitude);
            formData.append("location_longitude", taskLocation.longitude);
            formData.append("location_address", taskLocation.address || "");
            formData.append("next_status", taskActionNextStatus?.value || "");
            formData.append("_method", "PATCH");

            Array.from(taskActionImages?.files || []).forEach(function (file) {
                formData.append("images[]", file);
            });

            if (taskActionLightBill?.files?.[0]) {
                formData.append("light_bill", taskActionLightBill.files[0]);
            }

            if (taskActionMeasurements?.files?.[0]) {
                formData.append("measurements", taskActionMeasurements.files[0]);
            }

            if (taskActionSitePhoto?.files?.[0]) {
                formData.append("site_photo", taskActionSitePhoto.files[0]);
            }

            clearTaskActionErrors();
            taskActionSpinner?.classList.remove("d-none");
            if (taskActionSubmitBtn) {
                taskActionSubmitBtn.disabled = true;
            }

            $.ajax({
                url: `/api/tasks/${taskId}/quick-status`,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                success: function (response) {
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", response.message || "Task status updated successfully.");
                    }
                    taskActionModal?.hide();
                    fetchTasks();
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showTaskActionErrors(xhr.responseJSON.errors);
                        return;
                    }
                    if (typeof window.showAlert === "function") {
                        window.showAlert("error", xhr.responseJSON?.message || "Failed to update task status.");
                    }
                },
                complete: function () {
                    taskActionSpinner?.classList.add("d-none");
                    if (taskActionSubmitBtn) {
                        taskActionSubmitBtn.disabled = false;
                    }
                },
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No tasks added yet.</p>
                            ${permissions.create ? '<a href="/tasks/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Task</a>' : ""}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (task, index) {
                const customerName = escapeHtml(task.customer?.name || task.project?.customer?.name || "-");
                const staffName = escapeHtml(task.assignedUser?.name || task.owner?.name || "-");
                const invoiceName = escapeHtml(task.invoice?.invoice_name || task.invoice?.invoice_no || "No invoice linked");
                const taskTitle = escapeHtml(task.task_type === "solar_workflow" ? "Solar installation" : (task.title ?? "-"));
                const taskTypeLabel = escapeHtml(task.task_type === "solar_workflow" ? "Solar workflow" : (task.task_type || "-"));
                const statusLabel = escapeHtml(formatLabel(normalizeTaskStatus(task.status)));
                const dueDate = escapeHtml(formatDate(task.due_date));
                const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                const actionConfig = getTaskActionConfig(task);

                return `
                    <tr>
                        <td class="ps-4 text-center">
                            <span class="text-muted small fw-medium">${rowNumber}</span>
                        </td>
                        <td class="text-center">${customerName}</td>
                        <td class="text-center d-none d-md-table-cell">${staffName}</td>
                        <td class="text-center d-none d-md-table-cell">${invoiceName}</td>
                        <td class="text-center">
                            <div class="fw-bold small">${taskTitle}</div>
                        </td>
                        <td class="text-center d-none d-md-table-cell text-nowrap">
                            <span class="fw-semibold text-muted" style="font-size:0.8125rem">
                                ${taskTypeLabel}
                            </span>
                        </td>
                        <td class="text-center d-none d-md-table-cell text-nowrap">
                            <span class="badge crm-status-pill rounded-pill ${statusClass(task.status)}">
                                ${statusLabel}
                            </span>
                        </td>
                        <td class="text-center d-none d-md-table-cell text-nowrap">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${actionConfig ? `<button type="button" class="${actionConfig.buttonClass}" data-task-id="${task.id}" data-next-status="${actionConfig.nextStatus}" data-task-type="${escapeHtml(task.task_type || '')}">${actionConfig.label}</button>` : ''}
                                ${permissions.edit ? `<a href="/tasks/${task.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/tasks/${task.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-task-btn" data-task-id="${task.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-task-id="${task.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${task.id}" style="display: none;">
                        <td colspan="9" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Customer :</div>
                                        <div class="expand-value text-end">${customerName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-user-gear"></i> Staff :</div>
                                        <div class="expand-value text-end">${staffName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-file-lines"></i> Invoice :</div>
                                        <div class="expand-value text-end">${invoiceName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-list-check"></i> Task :</div>
                                        <div class="expand-value text-end">${taskTitle}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-tags"></i> Task Type :</div>
                                        <div class="expand-value">
                                            <span class="fw-semibold text-muted" style="font-size:0.8125rem">${taskTypeLabel}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                        <div class="expand-value text-end">
                                            <span class="badge crm-status-pill rounded-pill ${statusClass(task.status)}">${statusLabel}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Due Date :</div>
                                        <div class="expand-value text-end">${dueDate}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${actionConfig ? `<button type="button" class="${actionConfig.buttonClass}" data-task-id="${task.id}" data-next-status="${actionConfig.nextStatus}" data-task-type="${escapeHtml(task.task_type || '')}">${actionConfig.label}</button>` : ''}
                                        ${permissions.edit ? `<a href="/tasks/${task.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                        ${permissions.view ? `<a href="/tasks/${task.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-task-btn" data-task-id="${task.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ""}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join("");

            document.querySelectorAll(".delete-task-btn").forEach(function (button) {
                button.addEventListener("click", function () {
                    deleteTask(this.dataset.taskId, this);
                });
            });

            document.querySelectorAll(".quick-task-status-btn").forEach(function (button) {
                button.addEventListener("click", function () {
                    openTaskActionModal(this.dataset.taskId, this.dataset.nextStatus, this.dataset.taskType);
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.dataset.taskId;
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

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchTasks(this.dataset.page);
                });
            });
        }

        function fetchTasks(page = 1) {
            if (window.moduleListFilters && !window.moduleListFilters.bound) {
                window.moduleListFilters.bound = true;
                window.moduleListFilters.bind(fetchTasks, 'tasksSearch');
            }
            let url = `/api/tasks?page=${page}`;
            url += '&' + new URLSearchParams(window.moduleListFilters?.values() || {}).toString();

            if (searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            // Add filter parameter for staff users
            if (currentFilter) {
                url += `&filter=${currentFilter}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center py-5">Error loading tasks</td>
                        </tr>`;
                },
            });
        }

        let timer;
        searchInput.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                fetchTasks(1);
            }, 300);
        });

        taskActionForm?.addEventListener("submit", function (event) {
            event.preventDefault();
            quickStatusUpdate();
        });

        taskActionModalEl?.addEventListener("hidden.bs.modal", function () {
            resetTaskActionForm();
        });

        [taskActionComment, taskActionImages, taskActionLightBill, taskActionMeasurements, taskActionSitePhoto]
            .forEach(function (field) {
                field?.addEventListener("input", clearTaskActionErrors);
                field?.addEventListener("change", clearTaskActionErrors);
            });

        fetchTasks();
    }
})();

$(document).ready(function () {
    function getRemoteItems(url, query) {
        const requestUrl = `${url}?q=${encodeURIComponent(query || "")}`;

        return fetch(requestUrl, {
            method: "GET",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        }).then((response) => response.json());
    }

    function initRemoteTomSelect(selector, config) {
        const element = document.querySelector(selector);
        if (!element || element.tomselect) {
            return;
        }

        const searchUrl = element.dataset.searchUrl;
        if (!searchUrl) {
            return;
        }

        new TomSelect(selector, {
            valueField: "id",
            labelField: "name",
            searchField: config.searchField,
            preload: true,
            load: function (query, callback) {
                getRemoteItems(searchUrl, query)
                    .then((json) => callback(Array.isArray(json) ? json : []))
                    .catch(() => callback());
            },
            render: config.render,
            placeholder: config.placeholder,
            allowEmptyOption: true,
            copyAttributesToOptions: true,
        });
    }

    function initTomSelect() {
        return;
    }

    function clearErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
    }

    function setFieldInvalid($input) {
        if (!$input.length) {
            return;
        }

        $input.addClass("is-invalid");

        if ($input.is("select")) {
            $input.next(".ts-wrapper").addClass("is-invalid");
        }

        const flatpickr = $input[0]?._flatpickr;
        if (flatpickr?.altInput) {
            $(flatpickr.altInput).addClass("is-invalid");
        }
    }

    function clearFieldInvalid($input) {
        if (!$input.length) {
            return;
        }

        $input.removeClass("is-invalid");

        if ($input.is("select")) {
            $input.next(".ts-wrapper").removeClass("is-invalid");
        }

        const flatpickr = $input[0]?._flatpickr;
        if (flatpickr?.altInput) {
            $(flatpickr.altInput).removeClass("is-invalid");
        }
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            const input = $form.find(`#${field}`);
            const errorDiv = $form.find(`#${field}-error`);

            setFieldInvalid(input);

            if (errorDiv.length) {
                errorDiv.html(messages[0]);
            }
        });
    }

    initTomSelect();

    $("body").on("submit", ".ajax-task-form", function (e) {
        e.preventDefault();

        const $form = $(this);
        const btn = $form.find("#submitBtn");
        const btnText = $form.find("#btnText");
        const spinner = $form.find("#btnSpinner");
        const originalText = btnText.text();

        clearErrors($form);
        spinner.removeClass("d-none");
        btnText.text($form.find('input[name="_method"]').length ? "Updating..." : "Saving...");
        btn.prop("disabled", true);

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            },
            success: function (response) {
                spinner.addClass("d-none");
                btnText.text(originalText);
                btn.prop("disabled", false);

                if (typeof window.showAlert === "function") {
                    window.showAlert("success", response.message || "Task saved successfully.");
                }

                if (response.history_entry && window.crmStatusHistory) {
                    $form.find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                }

                setTimeout(function () {
                    window.location.href = response.redirect || "/tasks";
                }, 300);
            },
            error: function (xhr) {
                spinner.addClass("d-none");
                btnText.text(originalText);
                btn.prop("disabled", false);

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "An error occurred. Please try again.");
                }
            },
        });
    });

    $("body").on("input change", "input, select, textarea", function () {
        const $field = $(this);
        clearFieldInvalid($field);
        $(`#${$(this).attr("id")}-error`).html("");
    });
});
