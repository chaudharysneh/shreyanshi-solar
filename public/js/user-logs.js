(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initUserLogs);
    } else {
        initUserLogs();
    }

    function initUserLogs() {
        const config = window.userLogsConfig || {};
        const tableBody = document.querySelector("#userLogsTable tbody");
        const searchInput = document.getElementById("userLogsSearch");
        const perPageInput = document.getElementById("per_page");
        const pagination = document.getElementById("userLogsPagination");
        const summary = document.getElementById("userLogsSummary");
        const refreshButton = document.getElementById("userLogsRefreshBtn");
        const deleteAllButton = document.getElementById("userLogsDeleteAllBtn");
        const detailModalElement = document.getElementById("userLogDetailModal");
        const detailModal = detailModalElement && window.bootstrap ? new window.bootstrap.Modal(detailModalElement) : null;

        if (!config.indexUrl || !tableBody || !searchInput || !perPageInput || !pagination) {
            return;
        }

        let state = {
            q: searchInput.value || "",
            per_page: perPageInput.value || "10",
            page: 1,
        };
        let searchTimer;

        function loadLogs() {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Loading user logs...</td></tr>';

            const params = new URLSearchParams(state);

            fetch(config.indexUrl + "?" + params.toString(), {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                credentials: "same-origin",
            })
                .then(parseJson)
                .then(function (payload) {
                    renderRows(payload.data || []);
                    renderPagination(payload.meta || {});
                })
                .catch(function (error) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5">' + escapeHtml(error.message || "Failed to load user logs.") + '</td></tr>';
                });
        }

        function renderRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">No user logs found.</td></tr>';
                return;
            }

            tableBody.innerHTML = rows.map(function (row) {
                const actionClass = String(row.taken_action || "").toLowerCase();
                const viewBtn = '<button type="button" class="btn btn-outline-primary btn-sm user-logs-view-btn" data-view-id="' + escapeHtml(row.id) + '">View</button>';
                const clearBtn = '<button type="button" class="btn btn-danger btn-sm user-logs-clear-btn" data-id="' + escapeHtml(row.id) + '">Clear</button>';

                return '<tr>'
                    + '<td class="ps-4" data-label="Actioned By">' + escapeHtml(row.actioned_by || "--") + '</td>'
                    + '<td data-label="Module"><span class="user-log-module">' + escapeHtml(row.module || "Activity") + '</span></td>'
                    + '<td class="d-none d-md-table-cell" data-label="Taken Action"><span class="user-log-action ' + escapeHtml(actionClass) + '">' + escapeHtml(row.taken_action || "-") + '</span></td>'
                    + '<td class="d-none d-md-table-cell" data-label="Message"><div class="user-log-message">' + escapeHtml(row.message || "-") + '</div><div class="user-log-summary-text">' + escapeHtml(row.summary || "") + '</div></td>'
                    + '<td class="text-nowrap d-none d-md-table-cell" data-label="Created At">' + escapeHtml(row.created_at || "-") + '</td>'
                    + '<td class="text-center d-none d-md-table-cell"><div class="user-log-actions">'
                    + viewBtn
                    + clearBtn
                    + '</div></td>'
                    + '<td class="text-center d-md-none">'
                    + '<button type="button" class="btn-user-expand" data-log-id="' + escapeHtml(row.id) + '">'
                    + '<i class="fa-solid fa-plus"></i></button></td>'
                    + '</tr>'
                    + '<tr class="details-row d-md-none border" id="details-' + escapeHtml(row.id) + '" style="display: none;">'
                    + '<td colspan="3" class="p-0"><div class="details-content"><div class="row g-3">'
                    + '<div class="col-12 d-flex justify-content-between align-items-center">'
                    + '<div class="expand-label"><i class="fa-solid fa-bolt"></i> Taken Action :</div>'
                    + '<div class="expand-value"><span class="user-log-action ' + escapeHtml(actionClass) + '">' + escapeHtml(row.taken_action || "-") + '</span></div></div>'
                    + '<div class="col-12 d-flex justify-content-between align-items-start">'
                    + '<div class="expand-label mt-1"><i class="fa-solid fa-message"></i> Message :</div>'
                    + '<div class="expand-value text-end"><div class="user-log-message">' + escapeHtml(row.message || "-") + '</div><div class="user-log-summary-text text-end">' + escapeHtml(row.summary || "") + '</div></div></div>'
                    + '<div class="col-12 d-flex justify-content-between align-items-center">'
                    + '<div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Created At :</div>'
                    + '<div class="expand-value">' + escapeHtml(row.created_at || "-") + '</div></div>'
                    + '<div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">'
                    + '<div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>'
                    + '<div class="d-flex flex-wrap gap-2 justify-content-end">'
                    + viewBtn
                    + clearBtn
                    + '</div></div>'
                    + '</div></div></td></tr>';
            }).join("");

            tableBody.querySelectorAll("[data-id]").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.getAttribute("data-id");
                    if (id) {
                        clearLog(id);
                    }
                });
            });

            tableBody.querySelectorAll("[data-view-id]").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.getAttribute("data-view-id");
                    if (id) {
                        openDetail(id);
                    }
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    const id = this.dataset.logId;
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


        function renderPagination(meta) {
            const current = Number(meta.current_page || 1);
            const last = Number(meta.last_page || 1);
            const from = meta.from || 0;
            const to = meta.to || 0;
            const total = meta.total || 0;

            if (total === 0) {
                pagination.innerHTML = "";
                return;
            }

            let html = '<div class="crm-pagination-container">';
            html += '<div class="text-muted small text-center">Showing ' + from + ' to ' + to + ' of ' + total + ' results</div>';
            html += '<ul class="pagination crm-pagination mb-0 flex-wrap justify-content-center gap-2">';

            html += pageItem(current - 1, 'Previous', current <= 1, false);

            const pages = buildPages(current, last);

            pages.forEach(function (p) {
                if (p === "...") {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    return;
                }

                html += pageItem(p, String(p), false, p === current);
            });

            html += pageItem(current + 1, 'Next', current >= last, false);
            html += '</ul></div>';
            pagination.innerHTML = html;

            pagination.querySelectorAll("[data-page]").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    const page = Number(this.getAttribute("data-page"));
                    if (page > 0 && page <= last) {
                        state.page = page;
                        loadLogs();
                    }
                });
            });
        }

        function pageItem(page, label, disabled, active) {
            if (disabled || active) {
                return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">'
                    + '<span class="page-link">' + label + '</span></li>';
            }
            return '<li class="page-item">'
                + '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';
        }

        function confirmUserLogAction(options) {
            if (typeof Swal !== "undefined") {
                return Swal.fire({
                    title: options.title || "Are you sure?",
                    text: options.text || "",
                    icon: options.icon || "warning",
                    showCancelButton: true,
                    confirmButtonText: options.confirmButtonText || "Yes",
                    cancelButtonText: options.cancelButtonText || "Cancel",
                    confirmButtonColor: options.confirmButtonColor || "#dc3545",
                    cancelButtonColor: options.cancelButtonColor || "#6c757d",
                    customClass: {
                        popup: "rounded-4 shadow",
                    },
                });
            }

            return Promise.resolve({
                isConfirmed: window.confirm(options.text || options.title || "Are you sure?"),
            });
        }

        function clearLog(id) {
            confirmUserLogAction({
                title: "Clear this user log?",
                text: "This log entry will be removed permanently.",
                confirmButtonText: "Yes, clear it",
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                fetch(config.destroyBaseUrl + "/" + encodeURIComponent(id), {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                    credentials: "same-origin",
                })
                    .then(parseJson)
                    .then(function (payload) {
                        notify(payload.message || "User log cleared successfully.", "success");
                        loadLogs();
                    })
                    .catch(function (error) {
                        notify(error.message || "Failed to clear user log.", "error");
                    });
            });
        }

        function clearAllLogs() {
            if (!config.destroyAllUrl) {
                return;
            }

            confirmUserLogAction({
                title: "Delete all user logs?",
                text: "This action cannot be undone.",
                confirmButtonText: "Yes, delete all",
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                fetch(config.destroyAllUrl, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                    credentials: "same-origin",
                })
                    .then(parseJson)
                    .then(function (payload) {
                        notify(payload.message || "All user logs cleared successfully.", "success");
                        state.page = 1;
                        loadLogs();
                    })
                    .catch(function (error) {
                        notify(error.message || "Failed to clear user logs.", "error");
                    });
            });
        }

        function openDetail(id) {
            if (!config.showBaseUrl || !detailModal) {
                return;
            }

            fetch(config.showBaseUrl + "/" + encodeURIComponent(id), {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                credentials: "same-origin",
            })
                .then(parseJson)
                .then(function (payload) {
                    renderDetail(payload.data || {});
                    detailModal.show();
                })
                .catch(function (error) {
                    notify(error.message || "Failed to load log details.", "error");
                });
        }

        function renderDetail(data) {
            setText("userLogDetailModule", data.module || "Activity");
            setText("userLogDetailTitle", data.record_name ? data.record_name : "Activity details");
            setText("userLogDetailMeta", (data.actioned_by || "--") + " | " + (data.created_at || "-"));
            setText("userLogDetailMessage", data.message || "-");
            setText("userLogDetailSummary", data.summary || "No summary available.");

            const actionBadge = document.getElementById("userLogDetailAction");
            if (actionBadge) {
                actionBadge.textContent = data.taken_action || "UPDATE";
                actionBadge.className = "badge rounded-pill user-log-action-pill " + String(data.taken_action || "").toLowerCase();
            }

            const groupsWrap = document.getElementById("userLogDetailGroups");
            const emptyState = document.getElementById("userLogDetailEmpty");

            if (!groupsWrap || !emptyState) {
                return;
            }

            const groups = data.groups || {};
            const sections = [
                { key: "added", title: "Added", tone: "success" },
                { key: "updated", title: "Updated", tone: "primary" },
                { key: "deleted", title: "Deleted", tone: "danger" },
            ].map(function (section) {
                const originalItems = Array.isArray(groups[section.key]) ? groups[section.key] : [];
                const displayItems = originalItems
                    .map(normalizeChangeItem)
                    .filter(Boolean);

                return Object.assign({}, section, {
                    originalCount: originalItems.length,
                    displayItems: displayItems.slice(0, 6),
                    hiddenCount: Math.max(displayItems.length - 6, 0),
                });
            }).filter(function (section) {
                return section.displayItems.length > 0;
            });

            if (!sections.length) {
                groupsWrap.innerHTML = "";
                emptyState.classList.remove("d-none");
                return;
            }

            emptyState.classList.add("d-none");
            groupsWrap.innerHTML = sections.map(function (section) {
                return '<div class="col-12">'
                    + '<div class="user-log-detail-card h-100">'
                    + '<div class="user-log-section-title">'
                    + '<span>' + section.title + '</span>'
                    + '<span class="user-log-section-count">' + section.originalCount + '</span>'
                    + '</div>'
                    + '<div class="user-log-change-grid">'
                    + section.displayItems.map(function (item) {
                        return '<div class="user-log-change-item">'
                            + '<div class="user-log-change-label">' + escapeHtml(item.label) + '</div>'
                            + '<div class="user-log-change-value">' + escapeHtml(item.value) + '</div>'
                            + '</div>';
                    }).join("")
                    + '</div>'
                    + (section.hiddenCount > 0
                        ? '<div class="user-log-change-more">+' + section.hiddenCount + ' more tracked fields</div>'
                        : '')
                    + '</div></div>';
            }).join("");
        }

        function normalizeChangeItem(item) {
            const rawField = String(item.field || item.label || "").trim();
            const field = rawField.toLowerCase().replace(/\s+/g, "_");
            const value = item.value == null || item.value === "" ? "Not available" : String(item.value);

            if (isInternalField(field)) {
                return null;
            }

            if (field === "product_name" || field === "products") {
                return {
                    label: "BOM Items",
                    value: summarizeProducts(value),
                };
            }

            return {
                label: item.label || humanizeField(rawField || "Field"),
                value: summarizeValue(value),
            };
        }

        function isInternalField(field) {
            return [
                "id",
                "user_id",
                "customer_id",
                "estimate_id",
                "invoice_id",
                "created_by",
                "updated_by",
                "deleted_by",
            ].includes(field) || /(^|_)id$/.test(field);
        }

        function humanizeField(field) {
            return String(field || "Field")
                .replace(/_/g, " ")
                .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
        }

        function summarizeValue(value) {
            const compact = String(value || "").replace(/\s+/g, " ").trim();
            if (compact.length <= 120) {
                return compact || "Not available";
            }

            return compact.slice(0, 117) + "...";
        }

        function summarizeProducts(value) {
            try {
                const parsed = JSON.parse(value);
                if (!Array.isArray(parsed) || parsed.length === 0) {
                    return summarizeValue(value);
                }

                const items = parsed.slice(0, 3).map(function (product) {
                    const name = product.name || product.product_name || product.product_id || "BOM";
                    const qty = product.quantity || product.qty || product.product_qty;
                    return qty ? String(name) + " x" + qty : String(name);
                });

                const remaining = parsed.length - items.length;
                return items.join(", ") + (remaining > 0 ? " +" + remaining + " more" : "");
            } catch (error) {
                return summarizeValue(value);
            }
        }

        function setText(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        function buildPages(current, last) {
            if (last <= 7) {
                return range(1, last);
            }

            if (current <= 4) {
                return [1, 2, 3, 4, 5, "...", last];
            }

            if (current >= last - 3) {
                return [1, "..."].concat(range(last - 4, last));
            }

            return [1, "...", current - 1, current, current + 1, "...", last];
        }

        function range(start, end) {
            const values = [];
            for (let p = start; p <= end; p += 1) {
                values.push(p);
            }
            return values;
        }

        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                state.q = searchInput.value || "";
                state.page = 1;
                loadLogs();
            }, 350);
        });

        perPageInput.addEventListener("change", function () {
            state.per_page = perPageInput.value || "10";
            state.page = 1;
            loadLogs();
        });

        if (refreshButton) {
            refreshButton.addEventListener("click", loadLogs);
        }

        if (deleteAllButton) {
            deleteAllButton.addEventListener("click", clearAllLogs);
        }

        loadLogs();
    }

    function parseJson(response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || "Request failed.");
            }
            return payload;
        });
    }

    function notify(message, type) {
        if (typeof window.toastr !== "undefined" && typeof window.toastr[type] === "function") {
            window.toastr[type](message);
            return;
        }
        console[type === "error" ? "error" : "log"](message);
    }

    function csrf() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
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
})();