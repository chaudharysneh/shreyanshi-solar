(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard);
    } else {
        initDashboard();
    }

    const endpoints = {
        stats: "/api/dashboard/stats",
        leadBoard: "/api/dashboard/lead-board",
        tasks: "/api/dashboard/tasks-widget",
        customerReport: "/api/dashboard/customer-report",
        dealsWidget: "/api/dashboard/deals-widget",
    };

    let dailyActivityChart = null;
    let leadBoardSliderBound = false;
    function initDashboard() {
        if (!document.getElementById("dashboardStats")) {
            return;
        }

        if (document.getElementById("metricCustomers")) loadStats();
        loadLeadBoard();
        if (document.getElementById("dashboardTasksTable")) loadTasks();
        loadDailyActivity();
        loadDealsWidget();

        const yearSelect = document.getElementById("dailyActivityDays");
        if (yearSelect) {
            yearSelect.addEventListener("change", function () {
                loadDailyActivity(this.value);
            });
        }
    }

    function notifyError(message) {
        if (typeof window.showAlert === "function") {
            window.showAlert("error", message || "Something went wrong.");
            return;
        }

        console.error(message);
    }

    function getJson(url) {
        const separator = url.indexOf("?") === -1 ? "?" : "&";
        const freshUrl = url + separator + "_ts=" + Date.now();

        return fetch(freshUrl, {
            method: "GET",
            cache: "no-store",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
            },
            credentials: "same-origin",
        }).then(async function (response) {
            const payload = await response.json().catch(function () { return null; });
            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : "Request failed");
            }
            return payload;
        });
    }

    function loadStats() {
        getJson(endpoints.stats)
            .then(function (data) {
                setText("metricCustomers", formatNumber(data.customers));
                setText("metricFollowUps", formatNumber(data.follow_ups || data.pending_followups));
                setText("metricLeads", formatNumber(data.leads || data.active_leads));
                setText("metricDeals", formatNumber(data.deals));
            })
            .catch(function () {
                notifyError("Failed to load dashboard stats.");
            });
    }

    function loadLeadBoard() {
        const container = document.getElementById("leadBoardContainer");
        if (!container) {
            return;
        }

        getJson(endpoints.leadBoard)
            .then(function (response) {
                const stages = response && response.data ? response.data : [];
                const themes = ["indigo", "slate", "green", "orange"];

                const wrapper = document.getElementById("leadBoardWrapper");
                const hasData = Array.isArray(stages) && stages.some(function (stage) {
                    return (stage.count || 0) > 0;
                });

                if (!hasData) {
                    if (wrapper) wrapper.style.display = "none";
                    return;
                }

                if (wrapper) wrapper.style.display = "";

                container.innerHTML = stages.map(function (stage, index) {
                    const theme = themes[index % themes.length];
                    const lead = Array.isArray(stage.leads) && stage.leads.length ? stage.leads[0] : null;

                    if (!lead) {
                        return '<div class="status-column status-column--' + theme + '">'
                            + '<div class="status-column__head status-column__head--' + theme + '">'
                            + '<span>' + escapeHtml(stage.name || "Stage") + '</span>'
                            + '<span class="status-column__count">' + formatNumber(stage.count || 0) + '</span>'
                            + '</div>'
                            + '<div class="status-column__body status-column__body--empty">'
                            + '<div class="status-column__empty">No leads available.</div>'
                            + '</div>'
                            + '</div>';
                    }

                    return '<div class="status-column status-column--' + theme + '">'
                        + '<div class="status-column__head status-column__head--' + theme + '">'
                        + '<span>' + escapeHtml(stage.name || "Stage") + '</span>'
                        + '<span class="status-column__count">' + formatNumber(stage.count || 0) + '</span>'
                        + '</div>'
                        + '<div class="status-column__body status-column__body--filled">'
                        + '<div class="status-lead-card">'
                        + '<div class="status-lead-card__body">'
                        + '<div class="status-lead-name">' + escapeHtml(lead.name || "-") + '</div>'
                        + '<div class="status-lead-row"><i class="bi bi-envelope-fill"></i><span>' + (lead.email ? '<a href="mailto:' + escapeHtml(lead.email) + '" class="text-decoration-none">' + escapeHtml(lead.email) + '</a>' : '-') + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-telephone-fill"></i><span>' + (lead.phone ? '<a href="tel:' + escapeHtml(lead.phone) + '" class="text-decoration-none">' + escapeHtml(lead.phone) + '</a>' : '-') + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-person-plus-fill"></i><span>' + escapeHtml(lead.assigned_to || "Unassigned") + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-calendar-event-fill"></i><span>' + escapeHtml(formatDate(lead.created_at)) + '</span></div>'
                        + '</div>'
                        + '<div class="status-lead-card__footer">'
                        + '<div class="status-lead-actions">'
                        + '<a href="/leads/' + encodeURIComponent(lead.id) + '" class="status-lead-btn" title="View"><i class="bi bi-eye"></i></a>'
                        + '<a href="/leads/' + encodeURIComponent(lead.id) + '/edit" class="status-lead-btn" title="Edit"><i class="bi bi-pencil-square"></i></a>'
                        + '<a href="https://wa.me/' + (lead.phone || '').replace(/\D/g, '') + '" class="status-lead-btn" title="Follow Up"><i class="bi bi-whatsapp"></i></a>'
                        + '</div>'
                        + '<button type="button" class="status-lead-more" title="More"><i class="bi bi-three-dots-vertical"></i></button>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</div>';
                }).join("");
            })
            .catch(function () {
                container.innerHTML = '<div class="card border-0 shadow-sm w-100"><div class="card-body text-danger small">Failed to load lead board.</div></div>';
            })
            .finally(function () {
                initLeadBoardSlider();
            });
    }

    function initLeadBoardSlider() {
        var container = document.getElementById("leadBoardContainer");
        var btnLeft = document.getElementById("leadBoardLeft");
        var btnRight = document.getElementById("leadBoardRight");

        if (!container || !btnLeft || !btnRight) {
            return;
        }

        function updateArrows() {
            var scrollLeft = Math.round(container.scrollLeft);
            var maxScroll = container.scrollWidth - container.clientWidth;
            btnLeft.disabled = scrollLeft <= 0;
            btnRight.disabled = scrollLeft >= maxScroll - 1;
        }

        function getScrollAmount() {
            var firstCol = container.querySelector(".status-column");
            if (firstCol) {
                return firstCol.offsetWidth + 14;
            }
            return container.clientWidth * 0.85;
        }

        if (!leadBoardSliderBound) {
            btnLeft.addEventListener("click", function () {
                container.scrollBy({ left: -getScrollAmount(), behavior: "smooth" });
            });

            btnRight.addEventListener("click", function () {
                container.scrollBy({ left: getScrollAmount(), behavior: "smooth" });
            });

            container.addEventListener("scroll", updateArrows);
            window.addEventListener("resize", updateArrows);
            leadBoardSliderBound = true;
        }

        updateArrows();
    }

    function loadTasks() {
        const tbody = document.querySelector("#dashboardTasksTable tbody");
        if (!tbody) {
            return;
        }

        getJson(endpoints.tasks)
            .then(function (response) {
                const tasks = response && response.data ? response.data : [];

                if (!Array.isArray(tasks) || tasks.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No tasks found.</td></tr>';
                    return;
                }

                tbody.innerHTML = tasks.map(function (task) {
                    const taskType = task.task_type || "-";
                    const status = (task.status || "-").toString().toLowerCase();
                    const taskAssignedTo = task.assigned_to || task.assigned_user || "-";
                    const taskCustomerName = task.customer || task.customer_name || "-";
                    const taskDueDate = task.due_date || "-";

                    return '<tr class="task-main-row" data-task-id="' + task.id + '">'
                        + '<td>' + escapeHtml(task.title || "-") + '</td>'
                        + '<td class="d-none d-md-table-cell">' + escapeHtml(taskAssignedTo) + '</td>'
                        + '<td class="text-center"><span class="text-muted">' + escapeHtml(taskType) + '</span></td>'
                        + '<td class="text-center d-none d-md-table-cell"><span class="badge-status ' + escapeHtml(status) + '">' + escapeHtml((task.status || "-").toString().replace(/_/g, "-").toUpperCase()) + '</span></td>'
                        + '<td class="text-center d-md-none">'
                        + '<button type="button" class="btn-task-expand" data-target="' + task.id + '">'
                        + '<i class="fa-solid fa-plus"></i>'
                        + '</button>'
                        + '</td>'
                        + '</tr>'
                        + '<tr class="task-expand-row d-md-none" id="task-expand-' + task.id + '" style="display: none;">'
                        + '<td colspan="5">'
                        + '<div class="task-expand-content">'
                        + '<div class="row g-3">'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-user-tie"></i> Assigned To :</div>'
                        + '<div class="expand-value">' + escapeHtml(taskAssignedTo) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-building"></i> Customer Name :</div>'
                        + '<div class="expand-value">' + escapeHtml(taskCustomerName) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>'
                        + '<div><span class="badge-status ' + escapeHtml(status) + '">' + escapeHtml((task.status || "-").toString().replace(/_/g, "-").toUpperCase()) + '</span></div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Due Date :</div>'
                        + '<div class="expand-value">' + escapeHtml(taskDueDate) + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</td>'
                        + '</tr>';
                }).join("");

                // Handle main row click (navigation)
                tbody.querySelectorAll(".task-main-row").forEach(function (row) {
                    row.style.cursor = "pointer";
                    row.addEventListener("click", function (e) {
                        if (e.target.closest(".btn-task-expand")) {
                            return; // Don't navigate if clicking the expand button
                        }
                        const id = this.getAttribute("data-task-id");
                        if (id) {
                            window.location.href = "/tasks/" + id;
                        }
                    });
                });

                // Handle expand button click
                tbody.querySelectorAll(".btn-task-expand").forEach(function (btn) {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const taskId = this.getAttribute("data-target");
                        const expandRow = document.getElementById("task-expand-" + taskId);
                        const icon = this.querySelector("i");
                        
                        if (expandRow) {
                            const isVisible = expandRow.style.display !== "none";
                            expandRow.style.display = isVisible ? "none" : "table-row";
                            
                            if (icon) {
                                icon.classList.remove(isVisible ? "fa-minus" : "fa-plus");
                                icon.classList.add(isVisible ? "fa-plus" : "fa-minus");
                            }
                            
                            this.classList.toggle("active", !isVisible);
                        }
                    });
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Failed to load tasks.</td></tr>';
            });
    }

    function loadDailyActivity(days) {
        const canvas = document.getElementById('dailyActivityChart');
        if (!canvas || typeof window.Chart === 'undefined') return;
        const data = window.dashboardDailyActivity || { labels: [], datasets: [] };
        const count = Number(days || document.getElementById('dailyActivityDays')?.value || 30);
        const datasets = data.datasets.map(function (series) {
            return { label: series.label, data: series.values.slice(-count), borderColor: series.color,
                backgroundColor: series.color, fill: false, tension: .25, borderWidth: 2,
                pointRadius: count > 14 ? 2 : 3, pointHoverRadius: 5 };
        });
        if (dailyActivityChart) dailyActivityChart.destroy();
        dailyActivityChart = new Chart(canvas, {
            type: 'line', data: { labels: data.labels.slice(-count), datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,.18)' } },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
                }
            }
        });
    }

    function loadDealsWidget() {
        const tbody = document.querySelector("#dashboardDealsTable tbody");
        if (!tbody) {
            return;
        }

        getJson(endpoints.dealsWidget)
            .then(function (response) {
                const deals = response && response.data ? response.data : [];
                if (!Array.isArray(deals) || deals.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No deals found.</td></tr>';
                    return;
                }

                tbody.innerHTML = deals.map(function (deal) {
                    const statusName = deal && deal.status && typeof deal.status === "object"
                        ? (deal.status.name || "-")
                        : (deal.status || "-");
                    const normalized = statusName.toLowerCase().replace(/\s+/g, "_").replace(/-/g, "_");
                    const badgeTone = ['won', 'closed_won', 'completed', 'approved'].includes(normalized) ? 'green'
                        : ['lost', 'closed_lost', 'cancelled', 'rejected'].includes(normalized) ? 'red'
                        : ['in_process', 'in_progress', 'working', 'pending'].includes(normalized) ? 'amber'
                        : ['new', 'open', 'qualified'].includes(normalized) ? 'blue' : 'slate';
                    const dealCustomerName = deal.customer || deal.customer_name || "-";
                    const dealAssignedTo = deal.assigned_to || deal.assigned_user || "-";
                    const dealExpectedCloseDate = deal.expected_close_date || "-";

                    return '<tr class="deal-main-row" data-deal-id="' + deal.id + '">'
                        + '<td>' + escapeHtml(deal.name || deal.title || "-") + '</td>'
                        + '<td class="d-none d-md-table-cell">₹' + formatCurrency(deal.amount) + '</td>'
                        + '<td class="text-center d-none d-md-table-cell"><span class="dashboard-label-badge dashboard-label-badge--' + badgeTone + '">' + escapeHtml(statusName.replace(/_/g, " ")) + '</span></td>'
                        + '<td class="text-center d-md-none">'
                        + '<button type="button" class="btn-deal-expand" data-target="' + deal.id + '">'
                        + '<i class="fa-solid fa-plus"></i>'
                        + '</button>'
                        + '</td>'
                        + '</tr>'
                        + '<tr class="deal-expand-row d-md-none" id="deal-expand-' + deal.id + '" style="display: none;">'
                        + '<td colspan="4">'
                        + '<div class="task-expand-content">'
                        + '<div class="row g-3">'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-building"></i> Customer Name :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealCustomerName) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Amount :</div>'
                        + '<div class="expand-value">₹' + formatCurrency(deal.amount) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-user-tie"></i> Assigned User :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealAssignedTo) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Expected Close Date :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealExpectedCloseDate) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>'
                        + '<div><span class="dashboard-label-badge dashboard-label-badge--' + badgeTone + '">' + escapeHtml(statusName.replace(/_/g, " ")) + '</span></div>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</td>'
                        + '</tr>';
                }).join("");

                tbody.querySelectorAll(".deal-main-row").forEach(function (row) {
                    row.style.cursor = "pointer";
                    row.addEventListener("click", function (e) {
                        if (e.target.closest(".btn-deal-expand")) {
                            return;
                        }
                        const id = this.getAttribute("data-deal-id");
                        if (id) {
                            window.location.href = "/deals/" + id;
                        }
                    });
                });

                // Handle expansion
                tbody.querySelectorAll(".btn-deal-expand").forEach(function (btn) {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const dealId = this.getAttribute("data-target");
                        const expandRow = document.getElementById("deal-expand-" + dealId);
                        const icon = this.querySelector("i");

                        if (expandRow) {
                            const isVisible = expandRow.style.display !== "none";
                            expandRow.style.display = isVisible ? "none" : "table-row";

                            if (icon) {
                                icon.classList.remove(isVisible ? "fa-minus" : "fa-plus");
                                icon.classList.add(isVisible ? "fa-plus" : "fa-minus");
                            }

                            this.classList.toggle("active", !isVisible);
                        }
                    });
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Failed to load deals.</td></tr>';
            });
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function formatNumber(value) {
        const n = Number(value || 0);
        return Number.isFinite(n) ? n.toLocaleString("en-IN") : "0";
    }

    function formatCurrency(value) {
        const n = Number(value || 0);
        return Number.isFinite(n)
            ? n.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : "0.00";
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
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
