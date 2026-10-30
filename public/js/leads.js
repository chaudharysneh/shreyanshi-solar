(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const pageUrl = new URL(window.location.href);
        if (pageUrl.searchParams.has('filter')) {
            const filter = pageUrl.searchParams.get('filter');
            try { sessionStorage.setItem('crm.leads.filter', filter); } catch (_) {}
            pageUrl.searchParams.delete('filter');
            window.history.replaceState(window.history.state, '', pageUrl.pathname + pageUrl.search + pageUrl.hash);
        }
        const permissions = window.crmUserPermissions?.leads || {};
        const tableBody = document.querySelector("#leadsTable tbody");
        if (!tableBody) {
            return;
        }

        const paginationContainer = document.getElementById("leadsPagination");
        const searchInput = document.getElementById("leadsSearch");
        const leadFilters = {
            status: document.getElementById('leadStatusFilter'),
            lead_source_id: document.getElementById('leadSourceFilter'),
            created_by: document.getElementById('leadCreatorFilter'),
            per_page: document.getElementById('leadPerPage'),
        };
        const dateRange = window.flatpickr('#leadDateRange', {
            mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
            onChange: function (dates) { if (dates.length === 2 || dates.length === 0) fetchLeads(1); }
        });
        Object.values(leadFilters).forEach(field => field.addEventListener('change', () => fetchLeads(1)));
        document.getElementById('clearLeadFilters').addEventListener('click', function () {
            ['status', 'lead_source_id', 'created_by'].forEach(key => leadFilters[key].value = '');
            searchInput.value = ''; dateRange.clear(false); fetchLeads(1);
        });
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        
        // Accept existing filter links, then keep the selection in history state.
        const urlParams = new URLSearchParams(window.location.search);
        let savedFilter = '';
        try { savedFilter = sessionStorage.getItem('crm.leads.filter') || ''; } catch (_) {}
        let currentFilter = urlParams.get('filter') || window.history.state?.leadsFilter || savedFilter || 'created_by_me';

        function rememberFilter() {
            try { sessionStorage.setItem('crm.leads.filter', currentFilter); } catch (_) {}
            const newUrl = new URL(window.location);
            newUrl.searchParams.delete('filter');
            window.history.replaceState(
                { ...window.history.state, leadsFilter: currentFilter }, '', newUrl
            );
        }
        rememberFilter();

        // Activate the selected tab.
        if (currentFilter) {
            document.querySelectorAll('#leadFilterTabs button[data-filter]').forEach(function(tab) {
                if (tab.dataset.filter === currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        // Tab click handlers
        document.querySelectorAll('#leadFilterTabs button[data-filter]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                
                rememberFilter();
                
                fetchLeads(1);
            });
        });

        function statusBadge(status) {
            const classes = {
                new: "bg-info",
                qualified: "bg-primary",
                working: "bg-warning",
                ready_to_close: "bg-dark",
                won: "bg-success",
                lost: "bg-danger",
            };

            return classes[status] || "bg-secondary";
        }

        function formatStatus(status) {
            if (!status) return "-";

            const labels = {
                ready_to_close: "Ready to Close",
                won: "Closed Won",
                lost: "Closed Lost",
            };

            return labels[status] || status.replace(/_/g, " ").replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        }

        function formatDate(dateValue) {
            if (!dateValue) return "Not Set";

            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return "Not Set";

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        function deleteLead(id, button) {
            window.showDeleteConfirm("This lead will be deleted!").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/leads/${id}`,
                        type: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function (res) {
                            if (res.success) {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("success", res.message || "Lead deleted successfully.");
                                }
                                fetchLeads();
                            } else {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("error", res.message || "Delete failed");
                                }
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            }
                        },
                        error: function (xhr) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", xhr?.responseJSON?.message || "Something went wrong");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        function convertLead(id, button) {
            Swal.fire({
                title: "Are you sure?",
                text: "This lead will be converted to customer!",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, convert it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/leads/${id}/convert`,
                        type: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                        },
                        success: function () {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("success", "Lead converted successfully.");
                            }
                            fetchLeads();
                        },
                        error: function (xhr) {
                            if (typeof window.showAlert === "function") {
                                const errors = xhr.responseJSON?.errors;
                                const message = errors ? Object.values(errors).flat()[0] : xhr.responseJSON?.message;
                                window.showAlert("error", message || "Unable to convert lead. Please try again.");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No leads found in the directory.</p>
                            ${permissions.create ? '<a href="/leads/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Lead</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (lead, index) {
                const isConverted = Boolean(lead.is_converted);
                const createdBy = lead.creator?.name || "-";
                const phone = String(lead.phone || '').trim();
                const phoneText = $('<span>').text(phone).html();
                const phoneDial = phone.replace(/[^\d+]/g, '');
                const phoneHtml = phone ? (phoneDial ? `<a href="tel:${phoneDial}" class="text-nowrap">${phoneText}</a>` : phoneText) : '-';
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const statusHtml = `<span class="badge ${statusBadge(lead.status)} rounded-pill lead-status-pill">${$("<span>").text(lead.status_label || formatStatus(lead.status)).html()}</span>`;

                return `
                    <tr>
                        <td class="ps-4">${srNo}</td>
                        <td>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <span>${lead.name ?? "-"}</span>
                                ${isConverted ? '<span class="badge lead-converted-badge"><i class="bi bi-person-check me-1"></i>Converted</span>' : ""}
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell text-nowrap">${phoneHtml}</td>
                        <td class="d-none d-md-table-cell">${createdBy}</td>
                        <td class="d-none d-md-table-cell">${formatDate(lead.created_at)}</td>
                        <td class="d-none d-md-table-cell">${statusHtml}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${(!isConverted && permissions.edit) ? `<button type="button" class="btn crm-action-btn btn-sm text-success convert-btn" data-id="${lead.id}" title="Convert to Customer"><i class="bi bi-person-plus"></i></button>` : ""}
                                ${permissions.edit ? `<a href="/leads/${lead.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ""}
                                ${permissions.view ? `<a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${lead.id}" title="Remove Lead">
                                    <i class="bi bi-trash"></i>
                                </button>` : ""}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-lead-id="${lead.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${lead.id}" style="display: none;">
                        <td colspan="8" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone No. :</div>
                                        <div class="expand-value">${phoneHtml}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                        <div class="expand-value">${statusHtml}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div>
                                        <div class="expand-value">${createdBy}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created At :</div>
                                        <div class="expand-value">${formatDate(lead.created_at)}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${(!isConverted && permissions.edit) ? `<button type="button" class="btn crm-action-btn btn-sm text-success convert-btn" data-id="${lead.id}" title="Convert to Customer"><i class="bi bi-person-plus"></i></button>` : ""}
                                            ${permissions.edit ? `<a href="/leads/${lead.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ""}
                                            ${permissions.view ? `<a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${lead.id}" title="Remove Lead">
                                                <i class="bi bi-trash"></i>
                                            </button>` : ""}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join("");

            document.querySelectorAll(".delete-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    deleteLead(this.dataset.id, this);
                });
            });

            document.querySelectorAll(".convert-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    convertLead(this.dataset.id, this);
                });
            });

            // attach expand
            document.querySelectorAll(".btn-user-expand").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.dataset.leadId;
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
        }

        function renderPagination(data) {
            if (!paginationContainer) return;
            if (data.total === 0) {
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
                    <div class="text-muted small">
                        Showing ${from} to ${to} of ${total} results
                    </div>
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

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('.page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    fetchLeads(this.dataset.page);
                });
            });
        }

        function fetchLeads(page = 1) {
            let url = `/api/leads?page=${page}`;
            Object.entries(leadFilters).forEach(([key, field]) => {
                if (field.value !== '') url += `&${key}=${encodeURIComponent(field.value)}`;
            });
            if (dateRange.selectedDates.length === 2) {
                url += `&date_from=${dateRange.formatDate(dateRange.selectedDates[0], 'Y-m-d')}&date_to=${dateRange.formatDate(dateRange.selectedDates[1], 'Y-m-d')}`;
            }

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            // Add filter parameter for staff users
            if (currentFilter) {
                url += `&filter=${currentFilter}`;
            }

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || [], res.data);
                        renderPagination(res.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">Error loading leads</td>
                        </tr>`;
                },
            });
        }

        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchLeads(1);
                }, 400);
            });
        }

        fetchLeads();
    }
})();

$(document).ready(function () {
    window.previewImage = function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const icon = document.getElementById('leadImageIcon');

        if (!preview) {
            return;
        }

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (icon) {
                    icon.classList.add('d-none');
                }
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.classList.add('d-none');
            if (icon) {
                icon.classList.remove('d-none');
            }
        }
    };

    function handleValidationErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            const fieldEl = $form.find('[name="' + field + '"]');
            if (fieldEl.length) {
                fieldEl.addClass('is-invalid');
                if (fieldEl.is('select')) {
                    fieldEl.next('.ts-wrapper').addClass('is-invalid');
                }
                const container = fieldEl.closest('.col-md-6, .col-12, .col-md-12');
                let feedback = container.find('#' + field + '-error');

                if (feedback.length) {
                    feedback.html(errors[field][0]);
                } else {
                    fieldEl.after(`<div class="invalid-feedback ajax-error">${errors[field][0]}</div>`);
                }
            }
        });
    }

    $('body').on('submit', '.ajax-lead-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const btn = $form.find('button[type="submit"]');
        const originalText = btn.html();
        const method = 'POST';
        const isEdit = /\/api\/leads\/\d+$/i.test(url);


        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $form.find('.ajax-alert').remove();
        $form.find('.invalid-feedback').html('');

        const formData = new FormData(this);
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json',
            },
            success: function (response) {
                if (isEdit && response.history_entry && window.crmStatusHistory) {
                    showAlert('success', response.message, 'success');
                    $form.find('input[name="status_comment"]').val('');
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                let redirect = response.redirect || '/leads';
                
                setTimeout(function () {
                    if (redirect) {
                        showAlert('success', response.message, 'success');
                        window.location.href = redirect;
                    }
                }, 300);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    const response = xhr.responseJSON || {};
                    const message = response.message || 'Something went wrong while submitting the form. Please try again.';
                    const alert = `<div class="alert alert-danger ajax-alert" role="alert">${message}</div>`;
                    $form.prepend(alert);
                }
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            },
        });
    });

    $('body').on('input change', '.ajax-lead-form input, .ajax-lead-form select, .ajax-lead-form textarea', function () {
        const $field = $(this);
        $field.removeClass('is-invalid');
        if ($field.is('select')) {
            $field.next('.ts-wrapper').removeClass('is-invalid');
        }
        const id = $field.attr('id');
        if (id) {
            $('#' + id + '-error').html('');
        }
        $field.siblings('.invalid-feedback.ajax-error').remove();
    });
});
