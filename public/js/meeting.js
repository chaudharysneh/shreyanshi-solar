const API_CONFIG = {
    meetings: "/api/meetings",
    customers: "/api/meetings/customers",
    users: "/api/meetings/users",
    userSearch: "/api/users/search",
    googleConnect: "/auth/google",
    googleAuthStatus: "/api/meetings/google/auth-status",
    googleAuthUrl: "/api/meetings/google/auth-url",
    googleDisconnect: "/api/meetings/google/disconnect",
    googleEvents: "/api/meetings/google/events",
};
const MEETING_PERMISSIONS = window.crmUserPermissions?.meetings || {};
const GOOGLE_INTEGRATION_ENABLED = window.googleIntegrationEnabled === true;

// ==================== GLOBAL UTILITIES ====================
function escapeHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDate(date) {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function formatMeetingDateParts(date) {
    if (!date) return { date: "-", time: "" };

    const parsedDate = new Date(date);
    if (Number.isNaN(parsedDate.getTime())) {
        return { date: "-", time: "" };
    }

    return {
        date: parsedDate.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        }),
        time: parsedDate.toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
        }),
    };
}

function buttonLoader(btn, text = "", show = true) {
    const button = $(btn);
    if (show) {
        if (!button.data("original-text")) {
            button.data("original-text", button.html());
        }
        button.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm me-2"></span>
            ${text}...
        `);
    } else {
        button.prop("disabled", false).html(button.data("original-text"));
    }
}

function showAlert(type, message, title = "", redirectUrl = null) {
    Swal.fire({
        icon: type,
        title: title || (type === "success" ? "Success!" : "Error!"),
        text: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: { popup: "rounded-4 shadow" },
    }).then(() => {
        if (redirectUrl) window.location.href = redirectUrl;
    });
}

function displayErrors(errors) {
    // First, clear all existing errors
    $(".is-invalid").removeClass("is-invalid");
    $(".ts-wrapper.is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").html("").hide();

    // Then display new errors
    $.each(errors, function (field, messages) {
        let $input = $(`#${field}`);
        $input.addClass("is-invalid");
        if ($input.is("select")) {
            $input.next(".ts-wrapper").addClass("is-invalid");
        }

        // Try to find error element by specific ID first
        let $errorElement = $(`#${field}-error`);

        if ($errorElement.length) {
            $errorElement.html(messages[0]).show();
        } else {
            // Fallback: find the invalid-feedback within the same column
            $input
                .closest(".col-md-6, .col-12")
                .find(".invalid-feedback")
                .html(messages[0])
                .show();
        }
    });
}

function getMeetingIdFromUrl() {
    let pathArray = window.location.pathname.split("/");
    return pathArray[pathArray.length - 2]; // Gets ID from /meetings/{id}/edit
}

// ==================== MEETINGS TABLE ====================
const MeetingTable = {
    currentFilter: null,

    init() {
        // Accept existing filter links, then keep the selection in history state.
        const urlParams = new URLSearchParams(window.location.search);
        this.currentFilter = urlParams.get('filter') || window.history.state?.meetingsFilter || 'created_by_me';

        this.rememberFilter();

        // Activate the selected tab.
        if (this.currentFilter) {
            document.querySelectorAll('#meetingFilterTabs button[data-filter]').forEach((tab) => {
                if (tab.dataset.filter === this.currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        this.initTabs();
        this.load();
        this.initSearch();
        this.initPagination();
    },

    rememberFilter() {
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('filter');
        window.history.replaceState(
            { ...window.history.state, meetingsFilter: this.currentFilter }, '', newUrl
        );
    },

    initTabs() {
        // Tab click handlers
        document.querySelectorAll('#meetingFilterTabs button[data-filter]').forEach((tab) => {
            tab.addEventListener('click', () => {
                this.currentFilter = tab.dataset.filter;
                
                this.rememberFilter();
                
                this.load(1);
            });
        });
    },

    initSearch() {
        let searchTimer;
        $("#meetingsSearch").on("keyup", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.load(), 500);
        });
    },

    initPagination() {
        $(document).on("click", ".pagination .page-link[data-page]", (e) => {
            e.preventDefault();
            let page = $(e.currentTarget).data("page");
            if (page) this.load(parseInt(page));
        });
    },

    load(page = 1) {
        if (window.moduleListFilters && !window.moduleListFilters.bound) { window.moduleListFilters.bound = true; window.moduleListFilters.bind(page => this.load(page), 'meetingsSearch'); }
        let search = $("#meetingsSearch").val();

        // Show loading state
        $("tbody").html(this.getLoadingTemplate());

        $.ajax({
            url: API_CONFIG.meetings,
            type: "GET",
            data: { 
                page, 
                search,
                ...(window.moduleListFilters?.values() || {}),
                filter: this.currentFilter // Add filter parameter
            },
            dataType: "json",
            xhrFields: {
                withCredentials: true,
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                if (response.success) {
                    this.renderRows(response.data.data, response.data);
                    this.renderPagination(response.data);
                } else {
                    showAlert("error", "Failed to load meetings");
                }
            },
            error: (xhr) => this.handleError(xhr),
        });
    },

    getLoadingTemplate() {
        return `
            <tr>
                <td colspan="${GOOGLE_INTEGRATION_ENABLED ? 9 : 8}" class="text-center py-5">
                    <div class="text-muted">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading meetings...</p>
                    </div>
                </td>
            </tr>
        `;
    },

    handleError(xhr) {
        if (xhr.status === 401 || xhr.status === 419) {
            window.location.href = "/login";
            return;
        }

        if (xhr.responseText?.includes("<!doctype html>")) {
            showAlert("error", "Session expired. Please refresh.");
            return;
        }

        $("tbody").html(`
            <tr>
                <td colspan="${GOOGLE_INTEGRATION_ENABLED ? 9 : 8}" class="text-center py-5">
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle display-6 d-block mb-3"></i>
                        <p>Failed to load meetings. Please try again.</p>
                        <button class="btn btn-dark-blue btn-sm rounded-pill px-4" onclick="location.reload()">
                            Retry
                        </button>
                    </div>
                </td>
            </tr>
        `);
    },

    renderRows(meetings, meta = {}) {
        let tbody = $("tbody").empty();

        if (!meetings?.length) {
            tbody.html(`
                <tr>
                    <td colspan="${GOOGLE_INTEGRATION_ENABLED ? 9 : 8}" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-calendar-x display-6 d-block mb-3"></i>
                            <p>No meetings found.</p>
                            ${MEETING_PERMISSIONS.create ? `<a href="/meetings/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                 Schedule Your First Meeting
                            </a>` : ""}
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = "";
        meetings.forEach((meeting, index) => {
            let customerName = meeting.customer
                ? escapeHtml(meeting.customer.name)
                : "--";
            let srNo = meta.from ? meta.from + index : index + 1;
            
            const assignedUserData = meeting.assignedUser || meeting.assigned_user;
            let assignedUser = assignedUserData
                ? escapeHtml(assignedUserData.name)
                : "Unassigned";

            let statusBadge = this.getStatusBadge(meeting.status);
            const customerPhone = String(meeting.customer?.phone || '').trim();
            const phoneDial = customerPhone.replace(/[^\d+]/g, '');
            const phoneHtml = customerPhone && phoneDial
                ? `<div class="small mt-1"><a class="text-nowrap" href="tel:${phoneDial}">${escapeHtml(customerPhone)}</a></div>`
                : '';
            const scheduledAt = formatMeetingDateParts(meeting.scheduled_at);
            const meetingTypeLabel = this.getMeetingTypeLabel(meeting.meeting_type);
            
            // Google Calendar sync indicator
            let googleSyncBadge = meeting.is_synced 
                ? '<span class="badge bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center rounded-pill" style="min-width: 118px;"><i class="bi bi-check-circle me-1"></i>Synced</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary d-inline-flex align-items-center justify-content-center rounded-pill" style="min-width: 118px;"><i class="bi bi-cloud-arrow-up me-1"></i>Not Synced</span>';
            
            let googleSyncButton = meeting.is_synced 
                ? `<button type="button" class="btn crm-action-btn btn-sm text-danger" onclick="removeMeetingFromGoogle(${meeting.id}, this)" title="Remove from Google Calendar">
                       <i class="bi bi-calendar-x"></i>
                   </button>`
                : `<button type="button" class="btn crm-action-btn btn-sm" onclick="syncMeetingToGoogle(${meeting.id}, this)" title="Sync to Google Calendar">
                       <i class="bi bi-calendar-plus"></i>
                   </button>`;
            
            // Format location with icon
            let locationDisplay = meeting.address 
                ? `<i class="bi bi-geo-alt text-muted me-1"></i>${escapeHtml(meeting.address)}`
                : '<span class="text-muted">—</span>';

            // Title & Customer column
            let titleDisplay = `
                <div class="fw-semibold">${escapeHtml(meeting.title)}</div>
                <small class="text-muted">${customerName}</small>
            `;

            // Scheduled At column with type
            let scheduledDisplay = `
                <div class="fw-semibold">${formatDate(meeting.scheduled_at)}</div>
                ${meeting.meeting_type ? `<small class="text-muted">${escapeHtml(meeting.meeting_type)}</small>` : ''}
            `;

            html += `
                <tr>
                    <td class="ps-4 fw-semibold">${srNo}</td>
                    <td><div>${customerName}</div>${phoneHtml}</td>
                    <td class="text-center d-none d-md-table-cell">${assignedUser}</td>
                    <td class="text-center d-none d-md-table-cell">
                        <div class="small fw-semibold">${scheduledAt.date}</div>
                        <div class="text-muted small">${scheduledAt.time}</div>
                    </td>
                    <td class="text-center d-none d-md-table-cell">${meetingTypeLabel}</td>
                    ${GOOGLE_INTEGRATION_ENABLED ? `<td class="text-center d-none d-md-table-cell">${googleSyncBadge}</td>` : ""}
                    <td class="text-center d-none d-md-table-cell">${statusBadge}</td>
                    <td class="text-center d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center justify-content-center gap-2">
                            ${GOOGLE_INTEGRATION_ENABLED && MEETING_PERMISSIONS.edit ? googleSyncButton : ""}
                            ${MEETING_PERMISSIONS.view ? `<a href="/meetings/${meeting.id}" class="btn crm-action-btn btn-sm" title="View">
                                 <i class="bi bi-eye"></i>
                            </a>` : ""}
                            ${MEETING_PERMISSIONS.edit ? `<a href="/meetings/${meeting.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                 <i class="bi bi-pencil"></i>
                            </a>` : ""}
                            ${MEETING_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-meeting" 
                                    data-id="${meeting.id}" title="Delete">
                                 <i class="bi bi-trash"></i>
                            </button>` : ""}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-meeting-id="${meeting.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${meeting.id}" style="display: none;">
                    <td colspan="${GOOGLE_INTEGRATION_ENABLED ? 9 : 8}" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Customer :</div>
                                    <div class="expand-value">${customerName}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Staff :</div>
                                    <div class="expand-value">${assignedUser}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Scheduled On :</div>
                                    <div class="expand-value text-end">${scheduledAt.date} ${scheduledAt.time}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-video"></i> Meeting Type :</div>
                                    <div class="expand-value">${meetingTypeLabel}</div>
                                </div>
                                ${GOOGLE_INTEGRATION_ENABLED ? `<div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar"></i> Calendar :</div>
                                    <div class="expand-value">${googleSyncBadge}</div>
                                </div>` : ""}
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                    <div class="expand-value">${statusBadge}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${GOOGLE_INTEGRATION_ENABLED && MEETING_PERMISSIONS.edit ? googleSyncButton : ""}
                                        ${MEETING_PERMISSIONS.view ? `<a href="/meetings/${meeting.id}" class="btn crm-action-btn btn-sm" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>` : ""}
                                        ${MEETING_PERMISSIONS.edit ? `<a href="/meetings/${meeting.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>` : ""}
                                        ${MEETING_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-meeting" 
                                                data-id="${meeting.id}" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>` : ""}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);

        // Attach expand events
        $(document).off("click", ".btn-user-expand").on("click", ".btn-user-expand", function () {
            const id = $(this).data("meeting-id");
            const detailsRow = $(`#details-${id}`);
            const icon = $(this).find("i");
            
            if (detailsRow.is(":visible")) {
                detailsRow.hide();
                icon.removeClass("fa-minus").addClass("fa-plus");
                $(this).removeClass("active");
            } else {
                detailsRow.show();
                icon.removeClass("fa-plus").addClass("fa-minus");
                $(this).addClass("active");
            }
        });
    },

    getStatusBadge(status) {
        if (!status) return '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">—</span>';
        
        const badges = {
            scheduled: { class: "bg-warning text-dark", label: "Scheduled" },
            completed: { class: "bg-success", label: "Completed" },
            cancelled: { class: "bg-danger", label: "Cancelled" },
        };
        
        const badge = badges[status] || { class: "bg-secondary", label: status.charAt(0).toUpperCase() + status.slice(1) };
        return `<span class="badge ${badge.class} px-3 py-2 rounded-pill d-inline-flex align-items-center justify-content-center" style="min-width: 118px;">${badge.label}</span>`;
    },

    getMeetingTypeLabel(type) {
        const labels = {
            online: "Virtual",
            video: "Virtual",
            offline: "In Person",
            phone: "Phone Call",
        };

        return labels[type] || "--";
    },

    renderPagination(data) {
        let $container = $("#meetingPaginationContainer");
        if (!$container.length) return;

        if (!data || data.total === 0) {
            $container.empty();
            return;
        }

        const {
            from = 0,
            to = 0,
            total = 0,
            current_page = 1,
            last_page = 1,
            prev_page_url,
            next_page_url,
        } = data;

        let html = `
            <div class="crm-pagination-container">
                <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                <ul class="pagination crm-pagination mb-0">
        `;

        // Previous
        if (prev_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current_page - 1}">Previous</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }

        // Pages
        for (let i = 1; i <= last_page; i++) {
            if (i === 1 || i === last_page || (i >= current_page - 2 && i <= current_page + 2)) {
                html += i === current_page
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else if (i === current_page - 3 || i === current_page + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next
        if (next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current_page + 1}">Next</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }

        html += "</ul></div>";
        $container.html(html);
    },
};

// ==================== MEETING FORM (Create) ====================
const MeetingForm = {
    init() {
        if ($("#meetingForm").length) {
            $("#meetingForm").on("submit", (e) => this.handleSubmit(e));
        }
    },

    handleSubmit(e) {
        e.preventDefault();
        let form = $(e.currentTarget);
        let btn = $("#submitBtn");

        // Clear validation errors
        $(".invalid-feedback").empty().hide();
        $(".form-control, .form-select").removeClass("is-invalid");

        buttonLoader(btn, "Scheduling...");

        $.ajax({
            url: API_CONFIG.meetings,
            type: "POST",
            data: form.serialize(),
            xhrFields: {
                withCredentials: true,
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                buttonLoader(btn, "", false);
                if (response.success) {
                    showAlert("success", response.message, "", "/meetings");
                    form[0].reset();
                }
            },
            error: (xhr) => {
                buttonLoader(btn, "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                } else {
                    showAlert(
                        "error",
                        xhr.responseJSON?.message || "Failed to save meeting",
                    );
                }
            },
        });
    },
};

// ==================== MEETING UPDATE ====================
const MeetingUpdate = {
    init() {
        if ($("#meetingupdate").length) {
            $("#meetingupdate").on("submit", (e) => {
                e.preventDefault();
                this.handleUpdate();
            });
        }
    },

    handleUpdate() {
        // Clear previous errors
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").html("").hide();

        buttonLoader("#submitBtn", "Updating", true);

        let formData = {
            customer_id: $("#customer_id").val(),
            title: $("#title").val(),
            scheduled_at: $("#scheduled_at").val(),
            meeting_type: $("#meeting_type").val(),
            assigned_user_id: $("#assigned_user_id").val(),
            status: $("#status").val(),
            agenda: $("#agenda").val(),
            address: $("#address").val(),
            _token: $('meta[name="csrf-token"]').attr("content"),
            _method: "PUT",
        };

        $.ajax({
            url: `${API_CONFIG.meetings}/${getMeetingIdFromUrl()}`,
            type: "POST",
            data: formData,
            dataType: "json",
            xhrFields: {
                withCredentials: true,
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                buttonLoader("#submitBtn", "", false);
                if (response.history_entry && window.crmStatusHistory) {
                    showAlert(
                        "success",
                        response.message || "Meeting updated successfully",
                        "Success!",
                    );
                    $("#meetingupdate").find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                showAlert(
                    "success",
                    response.message || "Meeting updated successfully",
                    "Success!",
                    response.redirect || "/meetings",
                );
            },
            error: (xhr) => {
                buttonLoader("#submitBtn", "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                } else {
                    showAlert(
                        "error",
                        xhr.responseJSON?.message || "Failed to update meeting",
                    );
                }
            },
        });
    },
};

// ==================== DELETE MEETING ====================
function deleteMeeting(id) {
    window.showDeleteConfirm("You won't be able to revert this!").then((result) => {
        if (!result.isConfirmed) return;

        let deleteButton = $(`.delete-meeting[data-id="${id}"]`);
        buttonLoader(deleteButton, "Deleting", true);

        $.ajax({
            url: `${API_CONFIG.meetings}/${id}`,
            type: "DELETE",
            xhrFields: {
                withCredentials: true,
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                buttonLoader(deleteButton, "", false);
                if (response.success) {
                    showAlert("success", "Meeting deleted successfully");
                    let currentPage =
                        new URL(window.location.href).searchParams.get(
                            "page",
                        ) || 1;
                    MeetingTable.load(currentPage);
                } else {
                    showAlert("error", "Failed to delete meeting");
                }
            },
            error: (xhr) => {
                buttonLoader(deleteButton, "", false);
                showAlert("error", "Failed to delete meeting");
            },
        });
    });
}

// ==================== INITIALIZATION ====================
$(document).ready(function () {
    // Initialize based on current page
    if ($("#meetingsSearch").length) {
        MeetingTable.init();
    }

    if ($("#meetingForm").length) {
        MeetingForm.init();
    }

    if ($("#meetingupdate").length) {
        MeetingUpdate.init();
    }

    $(document).on("input change", "#meetingForm input, #meetingForm select, #meetingForm textarea, #meetingupdate input, #meetingupdate select, #meetingupdate textarea", function () {
        const $field = $(this);
        $field.removeClass("is-invalid");
        if ($field.is("select")) {
            $field.next(".ts-wrapper").removeClass("is-invalid");
        }
        const id = $field.attr("id");
        if (id) {
            $("#" + id + "-error").html("").hide();
        }
    });

    // Global delete handler
    $(document).on("click", ".delete-meeting", function () {
        deleteMeeting($(this).data("id"));
    });

    // Google Calendar initialization
    if (GOOGLE_INTEGRATION_ENABLED && $("#googleCalendarSection").length) {
        GoogleCalendar.init();
    }
});

// ==================== GOOGLE CALENDAR ====================
const GoogleCalendar = {
    isAuthenticated: false,

    init() {
        this.checkAuthStatus();
    },

    checkAuthStatus() {
        $.ajax({
            url: API_CONFIG.googleAuthStatus,
            type: "GET",
            success: (response) => {
                if (response.success) {
                    this.isAuthenticated = response.data.is_authenticated;
                    this.updateUI();
                }
            },
            error: () => {
                this.updateUI();
            },
        });
    },

    updateUI() {
        const connectBtn = $("#connectGoogleBtn");
        const disconnectBtn = $("#disconnectGoogleBtn");
        const statusBadge = $("#googleStatusBadge");

        if (this.isAuthenticated) {
            connectBtn.hide();
            disconnectBtn.show();
            statusBadge.html('<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Connected</span>');
        } else {
            connectBtn.show();
            disconnectBtn.hide();
            statusBadge.html('<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Not Connected</span>');
        }
    },

    connect() {
        window.location.href = API_CONFIG.googleConnect;
    },

    disconnect() {
        Swal.fire({
            title: "Disconnect Google Calendar?",
            text: "This will stop syncing meetings to your Google Calendar.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            confirmButtonText: "Disconnect",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API_CONFIG.googleDisconnect,
                    type: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: (response) => {
                        if (response.success) {
                            this.isAuthenticated = false;
                            this.updateUI();
                            showAlert("success", response.message);
                        } else {
                            showAlert("error", response.message);
                        }
                    },
                    error: () => {
                        showAlert("error", "Failed to disconnect Google Calendar");
                    },
                });
            }
        });
    },

    syncMeeting(meetingId, buttonElement) {
        const button = $(buttonElement);
        buttonLoader(button, "Syncing", true);

        $.ajax({
            url: `${API_CONFIG.meetings}/${meetingId}/sync-to-google`,
            type: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                if (response.success) {
                    showAlert("success", response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert("error", response.message);
                    buttonLoader(button, "", false);
                }
            },
            error: (xhr) => {
                const message = xhr.responseJSON?.message || "Failed to sync meeting";
                showAlert("error", message);
                buttonLoader(button, "", false);
            },
        });
    },

    removeFromGoogle(meetingId, buttonElement) {
        const button = $(buttonElement);
        
        Swal.fire({
            title: "Remove from Google Calendar?",
            text: "This will remove the meeting from your Google Calendar.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            confirmButtonText: "Remove",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (result.isConfirmed) {
                buttonLoader(button, "Removing", true);

                $.ajax({
                    url: `${API_CONFIG.meetings}/${meetingId}/remove-from-google`,
                    type: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: (response) => {
                        if (response.success) {
                            showAlert("success", response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert("error", response.message);
                            buttonLoader(button, "", false);
                        }
                    },
                    error: (xhr) => {
                        const message = xhr.responseJSON?.message || "Failed to remove meeting";
                        showAlert("error", message);
                        buttonLoader(button, "", false);
                    },
                });
            }
        });
    },
};

// Global functions for inline event handlers
window.syncMeetingToGoogle = function(meetingId, btn) {
    GoogleCalendar.syncMeeting(meetingId, btn);
};

window.removeMeetingFromGoogle = function(meetingId, btn) {
    GoogleCalendar.removeFromGoogle(meetingId, btn);
};
