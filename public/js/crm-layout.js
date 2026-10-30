(function () {
    // Resolve both fields within the clicked button's form, including modal forms.
    document.addEventListener("click", function (event) {
        const button = event.target.closest(".crm-phone-copy-button");
        if (!button) {
            return;
        }

        const form = button.closest("form");
        const phone = form && form.querySelector('input[name="phone"]');
        const whatsapp = button.closest(".crm-phone-copy").querySelector('input[name="whatsapp"]');
        if (!phone || !whatsapp || whatsapp.disabled || whatsapp.readOnly) {
            return;
        }

        whatsapp.value = phone.value;
        whatsapp.dispatchEvent(new Event("input", { bubbles: true }));
        whatsapp.dispatchEvent(new Event("change", { bubbles: true }));
    });

    function showToast(message, type = "info", duration = 5000) {
        const toastId = "toast-" + Date.now();
        const bgColor =
            {
                success: "bg-success",
                error: "bg-danger",
                warning: "bg-warning",
                info: "bg-info",
            }[type] || "bg-info";

        const icon =
            {
                success: "bi-check-circle-fill",
                error: "bi-exclamation-triangle-fill",
                warning: "bi-exclamation-circle-fill",
                info: "bi-info-circle-fill",
            }[type] || "bi-info-circle-fill";

        const toast = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="${duration}">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi ${icon} me-2"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        $("#toastContainer").append(toast);
        const toastElement = new bootstrap.Toast(document.getElementById(toastId));
        toastElement.show();

        $(`#${toastId}`).on("hidden.bs.toast", function () {
            $(this).remove();
        });
    }

    function enhanceFormLabels() {
        if (!document.body.classList.contains("crm-form-page")) {
            return;
        }

        const iconMatchers = [
            { match: /(customer|client|assigned to|assigned staff|assigned for|lead name|lead|staff|user|contact)/, icon: "bi-person-fill", family: "bi" },
            { match: /(email|mail)/, icon: "bi-envelope-fill", family: "bi" },
            { match: /(phone|mobile|whatsapp|call)/, icon: "bi-telephone-fill", family: "bi" },
            { match: /(address|location|city|country)/, icon: "bi-geo-alt-fill", family: "bi" },
            { match: /(status)/, icon: "bi-info-circle-fill", family: "bi" },
            { match: /(priority)/, icon: "bi-exclamation-circle-fill", family: "bi" },
            { match: /(date|time|due|follow up|scheduled|start|end|deadline)/, icon: "bi-calendar-event-fill", family: "bi" },
            { match: /(comment|description|agenda|note|remarks|message|details)/, icon: "bi-text-paragraph", family: "bi" },
            { match: /(meeting type|type|category|stage|source)/, icon: "bi-diagram-3-fill", family: "bi" },
            { match: /(amount|value|price|cost|budget|revenue|subsidy|discount)/, icon: "fa-money-bill", family: "fa-solid" },
            { match: /(probability|percent|percentage)/, icon: "bi-percent", family: "bi" },
            { match: /(image|photo|logo|avatar|icon)/, icon: "bi-image-fill", family: "bi" },
            { match: /(company|organization|business)/, icon: "bi-building", family: "bi" },
            { match: /(project)/, icon: "bi-folder-fill", family: "bi" },
            { match: /(task)/, icon: "bi-list-check", family: "bi" },
            { match: /(deal|opportunity)/, icon: "bi-briefcase-fill", family: "bi" },
            { match: /(ticket|support)/, icon: "bi-life-preserver", family: "bi" },
            { match: /(service)/, icon: "bi-tools", family: "bi" },
            { match: /(meeting)/, icon: "bi-camera-video-fill", family: "bi" },
        ];

        const getLabelText = function (label) {
            return Array.from(label.childNodes)
                .filter(function (node) {
                    return node.nodeType === Node.TEXT_NODE || (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains("text-danger"));
                })
                .map(function (node) {
                    return node.textContent || "";
                })
                .join(" ")
                .replace(/\s+/g, " ")
                .trim()
                .toLowerCase();
        };

        const findAssociatedField = function (label) {
            if (label.htmlFor) {
                return document.getElementById(label.htmlFor);
            }

            const fieldContainer = label.closest(".col, .col-md-6, .col-md-4, .col-md-3, .col-lg-6, .col-lg-4, .col-lg-3, .col-xl-6, .col-xl-4, .col-xl-3") || label.parentElement;
            return fieldContainer ? fieldContainer.querySelector("input, select, textarea") : null;
        };

        const resolveIcon = function (label, field) {
            const parts = [getLabelText(label)];
            if (field) {
                parts.push((field.name || "").replaceAll("_", " "));
                parts.push((field.id || "").replaceAll("_", " "));
            }

            const haystack = parts.join(" ").toLowerCase();
            const match = iconMatchers.find(function (entry) {
                return entry.match.test(haystack);
            });

            return match || { icon: "bi-tag-fill", family: "bi" };
        };

        document.querySelectorAll(".crm-form-page form label.form-label").forEach(function (label) {
            if (label.closest("#quickEstimateModal")) {
                return;
            }

            if (label.dataset.iconEnhanced === "true" || label.querySelector(".crm-label-icon, .fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands, .bi")) {
                return;
            }

            const field = findAssociatedField(label);
            const iconSpec = resolveIcon(label, field);
            const icon = document.createElement("i");
            icon.className = iconSpec.family === "fa-solid"
                ? "fa-solid " + iconSpec.icon + " crm-label-icon"
                : "bi " + iconSpec.icon + " crm-label-icon";
            icon.setAttribute("aria-hidden", "true");

            label.classList.add("crm-label-with-icon");
            label.prepend(icon);
            label.dataset.iconEnhanced = "true";
        });
    }

    function initCrmRemoteSelect(selector, options) {
        if (!window.TomSelect) {
            return null;
        }

        const element = document.querySelector(selector);
        if (!element || element.tomselect) {
            return null;
        }

        const searchUrl = element.dataset.searchUrl;
        if (!searchUrl) {
            return null;
        }

        const inferredSearchType = options.searchType
            || element.dataset.searchType
            || (searchUrl.includes("/users/search") ? "user" : null)
            || (searchUrl.includes("/customers/search") ? "customer" : null)
            || ((element.name || element.id || "").includes("user") ? "user" : null)
            || ((element.name || element.id || "").includes("customer") ? "customer" : null)
            || "default";
        const placeholder = element.dataset.searchPlaceholder
            || options.placeholder
            || (inferredSearchType === "user" ? "-- Search User --" : null)
            || (inferredSearchType === "customer" ? "-- Search Customer --" : null)
            || "-- Search --";

        const configByType = {
            user: {
                searchField: ["name", "email"],
                render: {
                    option: function (item, escape) {
                        const name = item.name || item.text || "";
                        const email = item.email || item.data_email || "";
                        return '<div class="py-2 px-3"><div class="fw-bold">' + escape(name) + "</div>" +
                            (email ? '<div class="text-muted small">' + escape(email) + "</div>" : "") +
                            "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            },
            customer: {
                searchField: ["name", "email", "phone"],
                render: {
                    option: function (item, escape) {
                        const name = item.name || item.text || "";
                        const email = item.email || item.data_email || "";
                        const phone = item.phone || item.data_phone || "";
                        const details = [email, phone].filter(Boolean).join(" | ");
                        return '<div class="py-2 px-3"><div class="fw-bold">' + escape(name) + "</div>" +
                            (details ? '<div class="text-muted small">' + escape(details) + "</div>" : "") +
                            "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            },
            default: {
                searchField: ["name"],
                render: {
                    option: function (item, escape) {
                        return '<div class="py-2 px-3">' + escape(item.name || item.text || "") + "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            }
        };

        const config = configByType[inferredSearchType] || configByType.default;
        const initialOptions = Array.from(element.options)
            .filter(function (option) {
                return option.value !== "";
            })
            .map(function (option) {
                return {
                    id: option.value,
                    name: option.textContent.trim(),
                    email: option.dataset.email || "",
                    phone: option.dataset.phone || "",
                };
            });
        const initialItems = Array.from(element.selectedOptions)
            .filter(function (option) {
                return option.value !== "";
            })
            .map(function (option) {
                return option.value;
            });

        return new TomSelect(selector, {
            valueField: "id",
            labelField: "name",
            searchField: config.searchField,
            options: initialOptions,
            items: initialItems,
            preload: true,
            load: function (query, callback) {
                const requestUrl = searchUrl + "?q=" + encodeURIComponent(query || "");
                fetch(requestUrl, {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (json) {
                        const fetchedItems = Array.isArray(json) ? json : [];
                        const selectedItems = Array.from(element.options)
                            .filter(function (option) {
                                return option.selected && option.value !== "";
                            })
                            .map(function (option) {
                                return {
                                    id: option.value,
                                    name: option.textContent.trim(),
                                    email: option.dataset.email || "",
                                    phone: option.dataset.phone || "",
                                };
                            });

                        const mergedItems = [...selectedItems, ...initialOptions, ...fetchedItems].filter(function (item, index, items) {
                            return item && item.id && items.findIndex(function (candidate) {
                                return String(candidate.id) === String(item.id);
                            }) === index;
                        });

                        callback(mergedItems);
                    })
                    .catch(function () {
                        callback();
                    });
            },
            render: config.render,
            placeholder: placeholder,
            allowEmptyOption: true,
            copyAttributesToOptions: true,
        });
    }

    function initPageChrome() {
        const forms = document.querySelectorAll(".js-validate");
        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            }, false);
        });

        const sidebarToggle = document.getElementById("sidebarToggle");
        const sidebar = document.querySelector(".crm-sidebar");
        const sidebarBackdrop = document.getElementById("crmSidebarBackdrop");

        if (sidebarToggle && sidebar) {
            const syncToggleState = function () {
                const expanded = window.innerWidth < 992
                    ? sidebar.classList.contains("open")
                    : !sidebar.classList.contains("collapsed");
                sidebarToggle.setAttribute("aria-expanded", String(expanded));
            };

            sidebarToggle.addEventListener("click", function () {
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle("open");
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.toggle("show");
                    }
                } else {
                    sidebar.classList.toggle("collapsed");
                }
                syncToggleState();
            });

            window.addEventListener("resize", function () {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove("open");
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.remove("show");
                    }
                }
                syncToggleState();
            });

            syncToggleState();
        }

        if (sidebarBackdrop && sidebar) {
            sidebarBackdrop.addEventListener("click", function () {
                sidebar.classList.remove("open");
                sidebarBackdrop.classList.remove("show");
            });
        }

        if (window.flatpickr) {
            flatpickr(".js-date", {
                allowInput: true,
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
            });

            flatpickr(".js-datetime", {
                allowInput: true,
                enableTime: true,
                time_24hr: false,
                dateFormat: "Y-m-d H:i",
                altInput: true,
                altFormat: "d/m/Y h:i K",
            });
        }

        enhanceFormLabels();
    }

    function initRemoteSelects() {
        if (!window.TomSelect) {
            return;
        }

        document.querySelectorAll("select[data-search-url]").forEach(function (element) {
            if (!element.id || element.tomselect) {
                return;
            }

            initCrmRemoteSelect("#" + element.id, {
                searchType: element.dataset.searchType,
                placeholder: element.dataset.searchPlaceholder,
            });
        });

        document.querySelectorAll("select.searchable-select:not([data-search-url])").forEach(function (element) {
            if (element.tomselect) {
                return;
            }
            new TomSelect(element, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        });
    }

    function capitalizeIndexNames() {
        if (!document.body.classList.contains('crm-index-page')) return;

        const isNameLabel = function (value) {
            const label = value.replace(/[:*]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
            return /\b(name|title|purpose)\b/.test(label)
                || /^(customer|staff|lead|product|bom|make|assigned to|assigned user|assigned staff|created by|updated by)$/.test(label);
        };
        document.querySelectorAll('table').forEach(function (table) {
            if (!table.tHead || table.closest('.modal, form') || table.parentElement.closest('table')) return;
            const apply = function () {
                const headers = Array.from(table.tHead.rows[table.tHead.rows.length - 1].cells);
                const indices = headers.flatMap(function (header, index) { return isNameLabel(header.textContent) ? [index] : []; });
                Array.from(table.tBodies).forEach(function (body) {
                    Array.from(body.rows).forEach(function (row) {
                        // Loading messages and mobile details use spanning cells.
                        if (Array.from(row.cells).some(function (cell) { return cell.colSpan > 1; })) return;
                        indices.forEach(function (index) { row.cells[index]?.classList.add('crm-index-name'); });
                    });
                });
                table.querySelectorAll('.expand-label').forEach(function (label) {
                    if (!isNameLabel(label.textContent)) return;
                    const value = label.parentElement.querySelector('.expand-value');
                    if (value) value.classList.add('crm-index-name');
                });
            };
            apply();
            // AJAX pagination/filtering replaces rows; keep display formatting consistent.
            let pending = false;
            new MutationObserver(function () {
                if (pending) return;
                pending = true;
                requestAnimationFrame(function () { pending = false; apply(); });
            }).observe(table, { childList: true, subtree: true });
        });
    }

    window.showToast = showToast;
    window.initCrmRemoteSelect = initCrmRemoteSelect;

    document.addEventListener("DOMContentLoaded", function () {
        initPageChrome();
        initRemoteSelects();
        capitalizeIndexNames();
    });
})();
