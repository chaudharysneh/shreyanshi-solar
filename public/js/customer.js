// A stable stage ID keeps badge and map colors consistent after a stage move.
function customerStageColors(stageId) {
    if (!stageId) return { color: '#64748b', background: '#f1f5f9', border: '#cbd5e1' };
    const hue = (Math.abs(Number(stageId) || 0) * 137.508) % 360;
    return {
        color: `hsl(${hue}, 70%, 32%)`,
        background: `hsl(${hue}, 75%, 94%)`,
        border: `hsl(${hue}, 55%, 76%)`
    };
}

(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.customers || {};
        const tableBody = document.getElementById("customersTable");

        if (!tableBody) {
            // Probably on create/edit page
            return;
        }

        const paginationContainer = document.getElementById("customerPaginationContainer");
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        // ✅ DELETE FUNCTION
        function deleteCustomer(id, button) {
            if (window.showDeleteConfirm) {
                window.showDeleteConfirm("This customer and all associated data will be deleted!").then((result) => {
                    if (result.isConfirmed) {
                        performDelete(id, button);
                    }
                });
            } else {
                if (confirm("Are you sure you want to delete this customer?")) {
                    performDelete(id, button);
                }
            }
        }

        function performDelete(id, button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            $.ajax({
                url: `/api/customers/${id}`,
                type: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                success: function (res) {
                    if (res.success) {
                        if (window.showAlert) {
                            window.showAlert('success', res.message || "Customer deleted successfully", 'Success');
                        }
                        fetchCustomers();
                    } else {
                        alert(res.message || "Delete failed");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || "Something went wrong";
                    alert(message);
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                },
            });
        }

        // ✅ RENDER ROWS
        function renderRows(items) {
            if (!items || items.length === 0) {
                const createButtonHtml = permissions.create
                    ? `<a href="/masters/customers/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                Add Your First Customer
                            </a>`
                    : '';

                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-people display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No customers found.</p>
                            ${createButtonHtml}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((customer, index) => {
                const date = customer.created_at ? new Date(customer.created_at).toLocaleDateString("en-GB", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                }) : "-";
                
                // Get flags from API response
                const isCreator = customer.is_creator === true;
                const canUpdate = customer.can_update === true;
                const canDelete = customer.can_delete === true;
                
                // View button - always available
                const viewButton = permissions.view
                    ? `<a href="/masters/customers/${customer.id}" class="btn crm-action-btn btn-sm" title="View">
                                <i class="bi bi-eye"></i>
                            </a>`
                    : "";
                
                // Edit button - all staff can edit any customer
                let editButton = "";
                if (permissions.edit && canUpdate) {
                    editButton = `<a href="/masters/customers/${customer.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>`;
                }
                
                // Delete button - all staff can delete any customer
                let deleteButton = "";
                if (permissions.delete && canDelete) {
                    deleteButton = `<button class="btn crm-action-btn btn-sm text-danger delete-btn"
                                data-id="${customer.id}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>`;
                }

                // Pipeline button - open stage flow modal
                let pipelineButton = "";
                if (permissions.edit && canUpdate) {
                    const escName = (customer.name || "").replace(/"/g, '&quot;');
                    pipelineButton = `<button type="button" class="btn crm-action-btn btn-sm stage-flow-trigger"
                                data-customer-id="${customer.id}" data-customer-name="${escName}"
                                data-stage-id="${customer.pipeline_stage_id || ""}" data-stage-name="${(customer.pipeline_stage_name || "").replace(/"/g, '&quot;')}"
                                data-can-edit="1"
                                title="Pipeline Stage Map">
                                <i class="fa-solid fa-code-branch"></i>
                            </button>`;
                }

                const documentsButton = permissions.edit && canUpdate
                    ? `<button type="button" class="btn crm-action-btn btn-sm customer-documents-upload" data-id="${customer.id}" title="Customer Documents" aria-label="Upload customer documents"><i class="bi bi-upload"></i></button>` : '';
                const actionButtons = `
                    <div class="d-inline-flex align-items-center justify-content-end gap-2 w-100">
                        ${viewButton}
                        ${editButton}
                        ${documentsButton}
                        ${deleteButton}
                        ${pipelineButton}
                    </div>
                `;

                return `
                <tr>
                    <td class="ps-4" data-label="Sr.No">${index + 1}</td>
                    <td class="text-start" data-label="Customer Name">
                        <div class="fw-bold small text-start">${$("<span>").text(customer.name || '-').html()}</div>
                        <div class="small text-start mt-1">${customer.phone ? `<a href="tel:${encodeURIComponent(customer.phone)}">${$("<span>").text(customer.phone).html()}</a>` : '-'}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Email">
                        <div class="small">${customer.email ? `<a href="mailto:${customer.email}">${customer.email}</a>` : '-'}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Pipeline Stage">
                        <span id="pipeline-badge-${customer.id}" class="badge border" style="color:${customerStageColors(customer.pipeline_stage_id).color};background-color:${customerStageColors(customer.pipeline_stage_id).background};border-color:${customerStageColors(customer.pipeline_stage_id).border} !important;">${customer.pipeline_stage_name || '-'}</span>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Created By">
                        <div class="small fw-semibold">${$("<span>").text(customer.created_by_name || '-').html()}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Created At">
                        <div class="small fw-semibold">${date}</div>
                    </td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        ${actionButtons}
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-id="${customer.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${customer.id}" style="display: none;">
                    <td colspan="3" class="p-0 border-0">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-envelope"></i> Email :</div>
                                    <div class="expand-value">${customer.email ? `<a href="mailto:${customer.email}">${customer.email}</a>` : '-'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                    <div class="expand-value">${customer.phone ? `<a href="tel:${customer.phone}">${customer.phone}</a>` : '-'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div>
                                    <div class="expand-value">${$("<span>").text(customer.created_by_name || '-').html()}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Created At :</div>
                                    <div class="expand-value">${date}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${viewButton}
                                        ${editButton}
                                        ${documentsButton}
                                        ${deleteButton}
                                        ${pipelineButton}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // attach delete
            document.querySelectorAll(".delete-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    deleteCustomer(this.dataset.id, this);
                });
            });

            // attach stage flow popover
            document.querySelectorAll(".stage-flow-trigger").forEach(btn => {
                btn.addEventListener("click", function (e) {
                    e.stopPropagation();
                    openStageFlowPopup(this);
                });
            });

            // attach expand
            document.querySelectorAll(".btn-user-expand").forEach(btn => {
                btn.addEventListener("click", function () {
                    const id = this.dataset.id;
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

        let pipelineStageFilter = new URLSearchParams(window.location.search).get('pipeline_stage_id') || '';
        let timer;
        const searchInput = document.getElementById("customerSearch");
        const statusFilter = document.getElementById('customerStatus');
        const typeFilter = document.getElementById('customerType');
        const perPage = document.getElementById('customerPerPage');
        const dateRange = window.flatpickr('#customerDateRange', {
            mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
            onChange: function (dates) { if (dates.length === 2 || dates.length === 0) fetchCustomers(1); }
        });
        [statusFilter, typeFilter, perPage].forEach(field => field.addEventListener('change', () => fetchCustomers(1)));
        document.getElementById('clearCustomerFilters').addEventListener('click', function () {
            pipelineStageFilter = '';
            document.getElementById('customerPipelineFilterNotice')?.remove();
            const cleanUrl = new URL(window.location.href); cleanUrl.searchParams.delete('pipeline_stage_id'); history.replaceState(null, '', cleanUrl);
            statusFilter.value = ''; typeFilter.value = ''; searchInput.value = '';
            clearTimeout(timer); dateRange.clear(false); fetchCustomers(1);
        });
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchCustomers(1), 400);
            });
        }

        // ✅ FETCH API
        function fetchCustomers(page = 1) {
            let url = `/api/customers?page=${page}`;
            if (pipelineStageFilter) url += `&pipeline_stage_id=${encodeURIComponent(pipelineStageFilter)}`;
            url += `&per_page=${perPage.value}&is_active=${statusFilter.value}&type=${encodeURIComponent(typeFilter.value)}`;
            if (dateRange.selectedDates.length === 2) {
                url += `&date_from=${dateRange.formatDate(dateRange.selectedDates[0], 'Y-m-d')}&date_to=${dateRange.formatDate(dateRange.selectedDates[1], 'Y-m-d')}`;
            }

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        // Handle both paginated and non-paginated responses
                        const items = res.data.data || res.data;
                        renderRows(items);
                        if (res.data.current_page) {
                            renderPagination(res.data);
                        } else {
                            paginationContainer.innerHTML = "";
                        }
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            Error loading customers
                        </td>
                    </tr>`;
                },
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
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            // Previous
            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            // Pages (limited to 5 for simplicity, similar to followup.js logic)
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += "</ul></div>";

            paginationContainer.innerHTML = html;

            // Click events
            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchCustomers(this.dataset.page);
                });
            });
        }

        // ✅ INITIAL LOAD
        fetchCustomers();
    }
})();

function showImportDialog() {
    Swal.fire({
        html: `
            <div class="mt-4 mb-3">
                <i class="fa-solid fa-file-csv fa-4x text-dark-blue"></i>
            </div>
            <h2 class="fw-bold mb-3" style="font-size: 1.75rem; color: #333;">Import Customers</h2>
            <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.5;">
                Would you like to import a CSV or download the demo template?
            </p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload me-1"></i> Import CSV',
        denyButtonText: '<i class="fa-solid fa-download me-1"></i> Download Demo',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-outline-dark-blue me-2',
            denyButton: 'btn btn-dark-blue me-2',
            cancelButton: 'btn btn-outline-dark-blue'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('customersImportFile').click();
        } else if (result.isDenied) {
            downloadCustomerCsvDemo();
        }
    });
}

$(document).ready(function () {

    // Wizard
    var $form = $('#customerForm');
    if (!$form.length) {
        return;
    }
    
    var $steps     = $form.find('.customer-form-step');
    var $stepBtns  = $('#customerFormSteps button[data-step]');
    var $prevBtn   = $form.find('.prev-step');
    var $nextBtn   = $form.find('.next-step');
    var $cancelBtn = $form.find('.cancel-step');
    var $submitBtn = $form.find('button[type="submit"]');
    var totalSteps = $steps.length;
    var currentStep = 1;
    var currentCustomerId = $form.data('id') || null;
    var freeStepNavigation = $form.data('free-step-navigation') === true;
    var alwaysShowSubmit = $form.data('always-show-submit') === true;

        function showStep(step) {
            currentStep = step;
            $steps.addClass('d-none').filter('[data-step="' + step + '"]').removeClass('d-none');
            $stepBtns.removeClass('active').filter('[data-step="' + step + '"]').addClass('active');
            $prevBtn.toggleClass('d-none', step === 1);
            $cancelBtn.toggleClass('d-none', step !== 1);
            $nextBtn.toggleClass('d-none', step === totalSteps);
            $submitBtn.toggleClass('d-none', !alwaysShowSubmit && step !== totalSteps);
        }

        function scrollToFirstInvalid() {
            var $firstInvalid = $form.find('.is-invalid:visible').first();
            if ($firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
        }

        function validateCurrentStep() {
            var $currentStep = $steps.filter('[data-step="' + currentStep + '"]');
            var originalIgnore = validator.settings.ignore;
            validator.settings.ignore = ':hidden';
            var valid = validator.form();
            validator.settings.ignore = originalIgnore;

            if ($currentStep.find('.is-invalid:visible').length) {
                valid = false;
            }

            return valid;
        }

        function checkDuplicateEmailPhone(email, phone, callback) {
            if (!email && !phone) {
                return callback(false);
            }

            var $emailField = $form.find('[name="email"]');
            var $phoneField = $form.find('[name="phone"]');
            
            // Remove ALL invalid feedback divs (not just the next one)
            $emailField.removeClass('is-invalid').nextAll('.invalid-feedback').remove();
            $phoneField.removeClass('is-invalid').nextAll('.invalid-feedback').remove();

            function applyErrors(customers) {
                var filteredCustomers = (customers || []).filter(function (customer) {
                    return !(currentCustomerId && customer.id == currentCustomerId);
                });

                var duplicateEmails = filteredCustomers.filter(function (customer) {
                    return email && customer.email && customer.email.toLowerCase() === email.toLowerCase();
                });
                var duplicatePhones = filteredCustomers.filter(function (customer) {
                    return phone && customer.phone && customer.phone === phone;
                });

                if (duplicateEmails.length) {
                    $emailField.addClass('is-invalid');
                    $emailField.after('<div class="invalid-feedback d-block">The email has already been taken.</div>');
                }
                if (duplicatePhones.length) {
                    $phoneField.addClass('is-invalid');
                    $phoneField.after('<div class="invalid-feedback d-block">The phone has already been taken.</div>');
                }

                return duplicateEmails.length || duplicatePhones.length;
            }

            function searchQuery(query, done) {
                $.ajax({
                    url: '/api/customers/search',
                    type: 'GET',
                    cache: false,
                    data: {
                        q: query,
                        exclude_id: currentCustomerId
                    },
                    success: function (res) {
                        done(res || []);
                    },
                    error: function () {
                        done([]);
                    }
                });
            }

            if (email) {
                searchQuery(email, function (customers) {
                    if (applyErrors(customers)) {
                        return callback(true);
                    }
                    if (phone) {
                        searchQuery(phone, function (phoneCustomers) {
                            callback(applyErrors(phoneCustomers));
                        });
                        return;
                    }
                    callback(false);
                });
                return;
            }

            searchQuery(phone, function (customers) {
                callback(applyErrors(customers));
            });
        }

        $.validator.addMethod('strictEmail', function(value, element) {
            return this.optional(element) || /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i.test(value);
        }, 'Please enter a valid email address.');

        $.validator.addMethod('validImage', function(value, element) {
            if (element.files.length === 0) {
                return true; // Image is optional
            }
            var file = element.files[0];
            var validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            return validImageTypes.includes(file.type);
        }, 'Invalid image format');

        $.validator.addMethod('imageSize', function(value, element) {
            if (element.files.length === 0) {
                return true; // Image is optional
            }
            var file = element.files[0];
            var maxSize = 2048 * 1024; // 2MB in bytes
            return file.size <= maxSize;
        }, 'Image size must not exceed 2MB');

        var validator = $form.validate({
            ignore: ':hidden',
            onkeyup: false,
            onfocusout: false,
            onclick: false,
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorElement: 'div',
            rules: {
                name: { required: true, maxlength: 255 },
                email: { strictEmail: true, maxlength: 255 },
                phone: { required: true, digits: true, minlength: 10, maxlength: 10 },
                whatsapp: { maxlength: 50 },
                image: { validImage: true, imageSize: true },
                company_name: { maxlength: 255 },
                website: { maxlength: 255 },
                tax_number: { maxlength: 100 },
                type: { maxlength: 100 },
                country_id: { digits: true },
                city_id: { digits: true },
                dob: { date: true },
                anniversary_date: { date: true }
            },
            messages: {
                name: { required: 'Please enter customer name.' },
                phone: {
                    required: 'Please enter phone number.',
                    minlength: 'Phone number must be at least 10 digits.',
                    maxlength: 'Phone number must not be greater than 10 digits.'
                },
                email: { strictEmail: 'Please enter a valid email address.' }
            },
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback d-block');
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        $nextBtn.on('click', function (e) {
            e.preventDefault();
            if (freeStepNavigation) {
                showStep(Math.min(currentStep + 1, totalSteps));
                return;
            }
            if (!validateCurrentStep()) {
                scrollToFirstInvalid();
                return;
            }

            if (currentStep === 1) {
                var email = $form.find('[name="email"]').val().trim();
                var phone = $form.find('[name="phone"]').val().trim();

                if (email || phone) {
                    checkDuplicateEmailPhone(email, phone, function (hasDuplicate) {
                        if (!hasDuplicate) {
                            showStep(Math.min(currentStep + 1, totalSteps));
                        } else {
                            scrollToFirstInvalid();
                        }
                    });
                    return;
                }
            }

            showStep(Math.min(currentStep + 1, totalSteps));
        });

        $prevBtn.on('click', function () {
            showStep(Math.max(currentStep - 1, 1));
        });

        $stepBtns.on('click', function () {
            var targetStep = Number($(this).data('step'));
            if (freeStepNavigation) {
                showStep(targetStep);
                return;
            }
            if (targetStep > currentStep) {
                if (!validateCurrentStep()) {
                    scrollToFirstInvalid();
                    return;
                }

                if (currentStep === 1) {
                    var email = $form.find('[name="email"]').val().trim();
                    var phone = $form.find('[name="phone"]').val().trim();
                    if (email || phone) {
                        checkDuplicateEmailPhone(email, phone, function (hasDuplicate) {
                            if (!hasDuplicate) {
                                showStep(targetStep);
                            } else {
                                scrollToFirstInvalid();
                            }
                        });
                        return;
                    }
                }
            }
            showStep(targetStep);
        });

        showStep(1);

    // Submit
    $('body').on('submit', '#customerForm', function (e) {
        e.preventDefault();
        var $form  = $(this);

        var submitValidator = $form.validate();
        var originalIgnore = submitValidator.settings.ignore;
        submitValidator.settings.ignore = [];
        var formIsValid = $form.valid();
        submitValidator.settings.ignore = originalIgnore;

        if (!formIsValid) {
            var $firstInvalid = $form.find('.is-invalid').first();
            if ($firstInvalid.length) {
                var invalidStep = Number($firstInvalid.closest('.customer-form-step').data('step'));
                if (invalidStep) {
                    showStep(invalidStep);
                }
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
            return;
        }

        var id     = $form.data('id');
        var apiUrl = id ? '/api/customers/' + id : '/api/customers';
        var $btn   = $form.find('button[type="submit"]');
        var orig   = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        var formData = new FormData(this);
        if (id) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function (res) {
                if (window.showAlert) window.showAlert('success', res.message || 'Customer saved successfully.');
                setTimeout(function () { window.location.href = '/masters/customers'; }, 300);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(orig);
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Show validation errors on each field
                    $.each(xhr.responseJSON.errors, function (fieldName, msgs) {
                        var $field = $form.find('[name="' + fieldName + '"]');
                        if ($field.length) {
                            $field.addClass('is-invalid');
                            $field.after('<div class="invalid-feedback d-block">' + msgs[0] + '</div>');
                        }
                    });
                    
                    // Scroll to first error field
                    var $firstError = $form.find('.is-invalid').first();
                    if ($firstError.length) {
                        var errorStep = Number($firstError.closest('.customer-form-step').data('step'));
                        if (errorStep) {
                            showStep(errorStep);
                        }
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 300);
                    }
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.';
                    if (window.showAlert) {
                        window.showAlert('error', msg);
                    } else {
                        alert(msg);
                    }
                }
            }
        });
    });

    // clear inline errors on input (but NOT on address textarea to prevent TomSelect flickering)
    let errorClearTimeout;
    $('body').on('input', '#customerForm input:not([type="file"]), #customerForm textarea[name!="address"]', function () {
        clearTimeout(errorClearTimeout);
        errorClearTimeout = setTimeout(() => {
            $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
        }, 100);
    });
    
    // For address textarea, only clear errors on blur to prevent dropdown flickering
    $('body').on('blur', '#customerForm textarea[name="address"]', function () {
        $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
    });
});


let _stagesCache = null;

function openStageFlowPopup(trigger) {
    const currentStageId = parseInt(trigger.dataset.stageId) || null;
    const currentStageName = trigger.dataset.stageName || '';
    const customerName = trigger.dataset.customerName || 'Customer';
    const customerInitial = customerName.charAt(0).toUpperCase();
    const customerId = trigger.dataset.customerId || null;
    const canEdit = trigger.dataset.canEdit === '1';

    // ── build modal shell once ──────────────────────────────────────────
    let modal = document.getElementById('stageFlowModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'stageFlowModal';
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('aria-labelledby', 'stageFlowModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered" style="max-width:760px;">
                <div class="modal-content border-0 shadow-xl rounded-4 overflow-hidden">
                    <div class="modal-header py-3 px-4" style="background:#0a2540;">
                        <div>
                            <p class="mb-0" style="color:rgba(255,255,255,.55);font-size:.68rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;">
                                <i class="bi bi-signpost-split-fill me-1"></i>Customer Journey
                            </p>
                            <h5 class="modal-title fw-bold text-white mb-0" id="stageFlowModalLabel">Pipeline Stage Map</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3 p-md-4" id="stageFlowModalBody" style="max-height:calc(85vh - 85px);overflow-y:auto;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    const bsModal = bootstrap.Modal.getOrCreateInstance(modal);

    // ── render helper ────────────────────────────────────────────────────
    function renderFlow(stages) {
        const body = document.getElementById('stageFlowModalBody');
        if (!body) return;

        let stagesHtml = '';
        stages.forEach((stage, index) => {
            const isCurrent = stage.id === currentStageId;
            const stageColors = customerStageColors(stage.id);
            const stageColor = stageColors.color;
            stagesHtml += `
                <div class="s-pipeline-node${isCurrent ? ' is-current' : ''}"
                     data-stage-id="${stage.id}"
                     data-stage-name="${(stage.name || '').replace(/"/g, '&quot;')}"
                     data-stage-color="${stageColor}"
                     style="--stage-color: ${stageColor}; --stage-color-light: ${stageColors.background};"
                     role="button" tabindex="${canEdit ? '0' : '-1'}"
                     aria-label="Move customer to ${(stage.name || '').replace(/"/g, '&quot;')}">
                    <span class="s-pipeline-node__marker">${index + 1}</span>
                    <span class="s-pipeline-node__name">${stage.name}</span>
                </div>`;
        });

        body.innerHTML = `
            <div class="customer-pipeline-toolbar mb-3">
                <div class="customer-pipeline-person" draggable="${canEdit ? 'true' : 'false'}" id="popupPipelinePerson" style="cursor:${canEdit ? 'grab' : 'default'};">
                    <span class="customer-pipeline-person__avatar">${customerInitial}</span>
                    <span class="customer-pipeline-person__details">
                        <strong>${customerName}</strong>
                        <small><i class="bi bi-${canEdit ? 'grip-vertical' : 'cursor-fill'} me-1 text-primary"></i>${canEdit ? 'Drag this customer onto any stage' : "View this customer's pipeline position"}</small>
                    </span>
                </div>
                <small class="text-muted d-none d-md-flex align-items-center gap-1">
                    <i class="bi bi-cursor-fill text-primary"></i>
                    ${canEdit ? 'You can also click a stage' : 'Current stage: <strong class="text-dark ms-1">' + (currentStageName || 'Not assigned') + '</strong>'}
                </small>
            </div>

            <div class="customer-pipeline-flow rounded-4" style="display:block;">
                <div class="s-pipeline-canvas" id="popupPipelineCanvas" style="width:100%; min-height:200px;">
                    <svg class="s-pipeline-connectors" aria-hidden="true"></svg>
                    ${stagesHtml}
                </div>
            </div>

            <div id="popupPipelineStatus" class="small mt-2 text-center" style="min-height:1.2em;"></div>
            <p class="text-muted small mt-2 mb-0 text-center">
                <i class="bi bi-info-circle me-1"></i>
                ${canEdit ? 'Drag the card above or <strong>click a stage</strong> to move this customer.' : 'Use the <strong>pipeline</strong> button in the Actions column to change the stage.'}
            </p>`;

        setTimeout(() => {
            const canvas = document.getElementById('popupPipelineCanvas');
            if (!canvas) return;
            const stageEls = Array.from(canvas.querySelectorAll('.s-pipeline-node'));

            function layoutSType() {
                const canvasWidth = canvas.clientWidth;
                const nodeWidth = 120;
                const nodeHeight = 70;

                let cols = Math.floor((canvasWidth + 20) / (nodeWidth + 20));
                if (cols < 2) cols = 2;
                if (cols > 4) cols = 4;
                if (cols > stages.length) cols = stages.length;

                let gapX = 40;
                if (cols > 1) {
                    gapX = (canvasWidth - (cols * nodeWidth)) / (cols - 1);
                    if (gapX > 120) gapX = 120;
                    if (gapX < 10) gapX = 10;
                }

                const gapY = 32;
                const contentWidth = (cols * nodeWidth) + ((cols - 1) * gapX);
                const offsetX = Math.max(0, (canvasWidth - contentWidth) / 2);

                let positions = [];
                if (stages.length === 10 && cols === 4) {
                    const customLayout = [
                        { row: 0, col: 0, isLeftToRight: true }, // Customer
                        { row: 0, col: 1, isLeftToRight: true }, // Registration
                        { row: 0, col: 2, isLeftToRight: true }, // Feasibility
                        { row: 0, col: 3, isLeftToRight: true }, // Finance
                        { row: 1, col: 3, isLeftToRight: false }, // 5: Waiting Floor
                        { row: 1, col: 1.5, isLeftToRight: false }, // 6: Dispatch
                        { row: 1, col: 0, isLeftToRight: false }, // 7: Installation
                        { row: 2, col: 0, isLeftToRight: true }, // 8: Intimation
                        { row: 2, col: 1.5, isLeftToRight: true }, // 9: Net Metering
                        { row: 2, col: 3, isLeftToRight: true }  // 10: Subsidy
                    ];

                    positions = customLayout.map(function (pos) {
                        return { left: offsetX + pos.col * (nodeWidth + gapX), top: pos.row * (nodeHeight + gapY), row: pos.row, isLeftToRight: pos.isLeftToRight };
                    });

                    const rows = 3;
                    const totalHeight = (rows * nodeHeight) + ((rows - 1) * gapY) + 20;
                    canvas.style.height = totalHeight + 'px';
                    canvas.style.minHeight = totalHeight + 'px';
                } else {
                    const rows = Math.ceil(stages.length / cols);
                    const totalHeight = (rows * nodeHeight) + ((rows - 1) * gapY) + 20;
                    canvas.style.height = totalHeight + 'px';
                    canvas.style.minHeight = totalHeight + 'px';

                    positions = stages.map(function (stage, index) {
                        const row = Math.floor(index / cols);
                        const isLeftToRight = row % 2 === 0;
                        const colIndex = index % cols;
                        const visualCol = isLeftToRight ? colIndex : (cols - 1 - colIndex);
                        const left = offsetX + visualCol * (nodeWidth + gapX);
                        const top = row * (nodeHeight + gapY);
                        return { left, top, row, isLeftToRight };
                    });
                }

                stageEls.forEach(function (el, index) {
                    el.style.left = positions[index].left + 'px';
                    el.style.top = positions[index].top + 'px';
                });
                stageEls.positions = positions;
            }

            function drawConnections() {
                const svg = canvas.querySelector('.s-pipeline-connectors');
                if (!svg || stageEls.length < 2) return;

                const cRect = canvas.getBoundingClientRect();
                const width = canvas.offsetWidth;
                const height = canvas.offsetHeight;
                svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
                svg.innerHTML = '';

                for (let index = 0; index < stageEls.length - 1; index++) {
                    const fromMarker = stageEls[index].querySelector('.s-pipeline-node__marker').getBoundingClientRect();
                    const toMarker = stageEls[index + 1].querySelector('.s-pipeline-node__marker').getBoundingClientRect();

                    const posA = stageEls.positions ? stageEls.positions[index] : { row: 0, isLeftToRight: true };
                    const posB = stageEls.positions ? stageEls.positions[index + 1] : { row: 0, isLeftToRight: true };

                    let pathData = '';
                    const fromX = fromMarker.left + (fromMarker.width / 2) - cRect.left;
                    const toX = toMarker.left + (toMarker.width / 2) - cRect.left;
                    const fromY = fromMarker.top + (fromMarker.height / 2) - cRect.top;
                    const toY = toMarker.top + (toMarker.height / 2) - cRect.top;

                    if (posA.row === posB.row) {
                        pathData = `M ${fromX} ${fromY} L ${toX} ${toY}`;
                    } else {
                        const arcRadius = Math.max(30, Math.abs(toY - fromY) / 2);
                        if (posA.isLeftToRight) {
                            const rx = Math.max(fromX, toX) + arcRadius;
                            pathData = `M ${fromX} ${fromY} C ${rx} ${fromY}, ${rx} ${toY}, ${toX} ${toY}`;
                        } else {
                            const rx = Math.min(fromX, toX) - arcRadius;
                            pathData = `M ${fromX} ${fromY} C ${rx} ${fromY}, ${rx} ${toY}, ${toX} ${toY}`;
                        }
                    }

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    const toNode = stageEls[index + 1];
                    const isCompleted = toNode.classList.contains('is-completed');
                    const isCurrent = toNode.classList.contains('is-current');

                    const stageColor = toNode.getAttribute('data-stage-color') || '#22c55e';

                    if (isCompleted || isCurrent) {
                        path.setAttribute('stroke', stageColor);
                    } else {
                        path.setAttribute('stroke', '#cbd5e1');
                    }
                    path.setAttribute('class', 's-pipeline-connector');
                    path.setAttribute('d', pathData);
                    svg.appendChild(path);
                }
            }

            function updateStageStates() {
                const currentStageNode = stageEls.find(s => s.classList.contains('is-current'));
                const currentIndex = currentStageNode ? stageEls.indexOf(currentStageNode) : -1;

                stageEls.forEach((stage, index) => {
                    const marker = stage.querySelector('.s-pipeline-node__marker');
                    const num = index + 1;

                    if (currentIndex !== -1 && index < currentIndex) {
                        stage.classList.add('is-completed');
                        stage.classList.remove('is-pending');
                        marker.innerHTML = '<i class="bi bi-check-lg" style="font-size: 1.2rem;"></i>';
                    } else if (currentIndex !== -1 && index > currentIndex) {
                        stage.classList.add('is-pending');
                        stage.classList.remove('is-completed');
                        marker.innerHTML = num;
                    } else if (currentIndex === index) {
                        stage.classList.remove('is-completed', 'is-pending');
                        marker.innerHTML = num;
                    } else {
                        stage.classList.add('is-pending');
                        stage.classList.remove('is-completed');
                        marker.innerHTML = num;
                    }
                });

                const paths = canvas.querySelectorAll('.s-pipeline-connector');
                paths.forEach((path, index) => {
                    const targetNode = stageEls[index + 1];
                    const stageColor = targetNode ? (targetNode.getAttribute('data-stage-color') || '#22c55e') : '#22c55e';

                    if (currentIndex !== -1 && index < currentIndex) {
                        path.classList.add('is-completed');
                        path.setAttribute('stroke', stageColor);
                    } else {
                        path.classList.remove('is-completed');
                        path.setAttribute('stroke', '#cbd5e1');
                    }
                });
            }

            setTimeout(() => {
                layoutSType();
                window.requestAnimationFrame(() => {
                    drawConnections();
                    updateStageStates();
                });

                // ── Drag-and-drop + click interaction (mirrors show.blade.php) ──
                if (!canEdit || !customerId) return;

                const personEl = document.getElementById('popupPipelinePerson');
                const statusEl = document.getElementById('popupPipelineStatus');
                let isSaving = false;

                function setStatus(msg, type) {
                    if (!statusEl) return;
                    statusEl.className = 'small mt-2 text-center ' + (type === 'error' ? 'text-danger' : 'text-success');
                    statusEl.textContent = msg;
                    if (type !== 'error') setTimeout(() => { statusEl.textContent = ''; }, 3000);
                }

                async function moveToStage(stageEl) {
                    if (!stageEl || stageEl.classList.contains('is-current') || isSaving) return;
                    const stageId = stageEl.dataset.stageId;
                    const stageName = stageEl.dataset.stageName;
                    isSaving = true;
                    setStatus('Moving to ' + stageName + '…', 'success');

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    let headers = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
                    if (typeof window.crmApplyAuthHeaders === 'function') headers = window.crmApplyAuthHeaders(headers);

                    try {
                        const res = await fetch(`/masters/customers/${customerId}/pipeline-stage`, { method: 'PATCH', headers, body: JSON.stringify({ stage_id: stageId }) });
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok || payload.success === false) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to update stage.');

                        // Update canvas
                        stageEls.forEach(el => {
                            el.classList.remove('is-current', 'is-drag-over');
                        });
                        stageEl.classList.add('is-current');
                        updateStageStates();

                        // Update the "Current stage" text in the toolbar
                        const hint = document.querySelector('#stageFlowModal .customer-pipeline-toolbar small');
                        if (hint) hint.innerHTML = '<i class="bi bi-cursor-fill text-primary"></i> You can also click a stage';

                        setStatus(payload.message || 'Pipeline stage updated!', 'success');
                        if (typeof window.showAlert === 'function') window.showAlert('success', payload.message || 'Pipeline stage updated.');

                        // Refresh the badge in the table row so it shows the new stage
                        const pipelineBadge = document.getElementById(`pipeline-badge-${customerId}`);
                        if (pipelineBadge) {
                            pipelineBadge.textContent = stageName;
                            const colors = customerStageColors(stageId);
                            pipelineBadge.style.color = colors.color;
                            pipelineBadge.style.backgroundColor = colors.background;
                            pipelineBadge.style.setProperty('border-color', colors.border, 'important');
                        }

                        // Update the data attributes on the action button itself
                        const rowBadge = document.querySelector(`.stage-flow-trigger[data-customer-id="${customerId}"]`);
                        if (rowBadge) {
                            rowBadge.dataset.stageId = stageId;
                            rowBadge.dataset.stageName = stageName;
                        }

                        // Also update the pipeline button data attributes in case they click the action button
                        const actionBtn = document.querySelector(`.pipeline-btn[data-id="${customerId}"]`);
                        if (actionBtn) {
                            actionBtn.dataset.pipelineStageId = stageId;
                            actionBtn.dataset.pipelineStageName = stageName;
                        }
                    } catch (err) {
                        setStatus(err.message || 'Unable to update stage.', 'error');
                        if (typeof window.showAlert === 'function') window.showAlert('error', err.message || 'Unable to update pipeline stage.');
                    } finally {
                        isSaving = false;
                    }
                }

                // Drag events on the customer card
                if (personEl) {
                    personEl.addEventListener('dragstart', e => {
                        personEl.classList.add('is-dragging');
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', 'customer');
                    });
                    personEl.addEventListener('dragend', () => {
                        personEl.classList.remove('is-dragging');
                        stageEls.forEach(el => el.classList.remove('is-drag-over'));
                    });
                }

                // Drag + click events on each stage node
                stageEls.forEach(stageEl => {
                    stageEl.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; stageEl.classList.add('is-drag-over'); });
                    stageEl.addEventListener('dragleave', () => stageEl.classList.remove('is-drag-over'));
                    stageEl.addEventListener('drop', e => { e.preventDefault(); stageEl.classList.remove('is-drag-over'); moveToStage(stageEl); });
                    stageEl.addEventListener('click', e => { if (!e.target.closest('.customer-pipeline-person')) moveToStage(stageEl); });
                    stageEl.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); moveToStage(stageEl); } });
                });
            }, 80);
        }, 0);
    }

    // ── open / re-open logic ─────────────────────────────────────────────
    // Always reset body to spinner first so old canvas is cleared
    const bodyEl = document.getElementById('stageFlowModalBody');
    if (bodyEl) {
        bodyEl.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>`;
    }

    // Shared render trigger — called only AFTER the modal is fully visible
    // (i.e., after Bootstrap's fade-in animation finishes, so canvas.clientWidth is real)
    function triggerRender() {
        if (_stagesCache) {
            renderFlow(_stagesCache);
            return;
        }

        fetch('/api/masters/stages', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                let stagesArray = [];
                if (data.data && Array.isArray(data.data.data)) {
                    stagesArray = data.data.data;
                } else if (Array.isArray(data.data)) {
                    stagesArray = data.data;
                } else if (Array.isArray(data.stages)) {
                    stagesArray = data.stages;
                } else if (Array.isArray(data)) {
                    stagesArray = data;
                }
                const stages = stagesArray
                    .filter(s => s.is_active !== false)
                    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
                _stagesCache = stages;
                renderFlow(stages);
            })
            .catch(() => {
                const b = document.getElementById('stageFlowModalBody');
                if (b) b.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>
                    <p class="mb-0">Failed to load pipeline stages.</p>
                    <small class="text-muted">Please refresh the page and try again.</small>
                </div>`;
            });
    }

    const isAlreadyShown = modal.classList.contains('show');

    if (isAlreadyShown) {
        // Modal is already open (e.g. user switched customer without closing).
        // Canvas already has real dimensions — render immediately.
        triggerRender();
    } else {
        // Modal is hidden — wait for Bootstrap's animation to complete before
        // running layoutSpiral(), otherwise canvas.clientWidth === 0 and all
        // nodes collapse to the top-left corner.
        modal.addEventListener('shown.bs.modal', triggerRender, { once: true });
        bsModal.show();
    }
}

