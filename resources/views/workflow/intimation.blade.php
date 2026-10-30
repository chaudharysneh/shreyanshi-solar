@extends('layouts.app')
 
@push('styles')
<style>
    /* Styling alignment with user design */
    .workflow-header-container {
        margin-bottom: 24px;
    }

    .workflow-title-prefix {
        font-size: 0.75rem;
        font-weight: 700;
        color: #16a34a;
        letter-spacing: 1px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .workflow-title-line {
        width: 16px;
        height: 2px;
        background-color: #16a34a;
        display: inline-block;
    }

    .workflow-title {
        font-size: 2.2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 20px;
        font-family: 'Outfit', sans-serif;
    }

    /* Tabs styling matching screenshot */
    .intimation-tabs-container {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
    }

    .intimation-tab-btn {
        padding: 8px 16px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        background: transparent;
    }

    .intimation-tab-btn.active {
        background-color: #eaf8ef;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .intimation-tab-btn.inactive {
        background-color: white;
        color: #64748b;
        border-color: #e2e8f0;
    }

    .intimation-tab-btn.inactive:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }

    .tab-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        display: inline-block;
    }

    .intimation-tab-btn.active .tab-badge {
        background-color: #bbf7d0;
        color: #16a34a;
    }

    .intimation-tab-btn.inactive .tab-badge {
        background-color: #f1f5f9;
        color: #64748b;
    }

    /* Toolbar Controls styling */
    .toolbar-container {
        display: flex;
        gap: 16px;
        align-items: center;
        width: 100%;
        margin-bottom: 24px;
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .search-wrapper {
        position: relative;
        flex-grow: 1;
    }

    .search-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
    }

    .search-input {
        width: 100%;
        height: 46px;
        padding: 10px 16px 10px 48px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.2s ease;
        color: #334155;
    }

    .search-input:focus {
        border-color: #cbd5e1;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .btn-toolbar-action {
        height: 46px;
        padding: 0 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background-color: white;
        color: #334155;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-toolbar-action:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }

    /* Board & List Container */
    .board-container {
        border: 1px solid var(--crm-bg-sidebar);
        border-radius: 16px;
        overflow: hidden;
        background-color: #f8fafc;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .board-header {
        background-color: var(--crm-bg-sidebar);
        color: white;
        padding: 14px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
    }

    .board-header-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .board-header-dot {
        width: 8px;
        height: 8px;
        background-color: white;
        border-radius: 50%;
        display: inline-block;
    }

    .board-header-badge {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .board-list {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-height: 200px;
    }

    /* Customer Card Row */
    .customer-card-row {
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .customer-card-row:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .card-docs-pending-status {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #94a3b8;
    }

    .customer-title-block {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .customer-name-txt {
        font-size: 1.05rem;
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        margin: 0;
    }

    .aspl-badge-pill {
        background-color: #eaf8ef;
        color: #16a34a;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
    }

    .metadata-pill-block {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Metadata details row */
    .metadata-details-row {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .metadata-details-row span {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .metadata-details-row i {
        color: #94a3b8;
    }

    .metadata-details-row .bolt-icon {
        color: #16a34a;
    }

    .metadata-details-row .bolt-text,
    .metadata-details-row .cost-text {
        color: #1e293b;
        font-weight: 700;
    }

    .metadata-details-row .clock-block {
        color: #a855f7;
    }

    .metadata-details-row .clock-block i {
        color: #a855f7;
    }

    /* Tasks Section */
    .task-cards-grid {
        display: flex;
        gap: 12px;
        margin-top: 4px;
        width: 100%;
    }

    .task-card-item {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: white;
        min-width: 180px;
    }

    .task-card-item:hover {
        border-color: #334155;
    }

    .task-card-icon-wrapper {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .task-card-item.uploaded-state {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
    }

    .task-card-item.uploaded-state .task-card-icon-wrapper {
        background-color: #16a34a;
    }

    .task-card-item.uploaded-state .task-label-txt {
        color: #16a34a;
    }

    .task-card-item.uploaded-state .task-status-txt {
        color: #15803d;
    }

    /* Icons for pending tasks */
    .bg-icon-blue { background-color: #3b82f6; }
    .bg-icon-yellow { background-color: #eab308; }
    .bg-icon-purple { background-color: #8b5cf6; }
    .bg-icon-green { background-color: #10b981; }

    .task-details-wrapper {
        display: flex;
        flex-direction: column;
    }

    .task-label-txt {
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
    }

    .task-status-txt {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* Footer info */
    .footer-summary-text {
        text-align: center;
        font-size: 0.82rem;
        color: #64748b;
        margin-top: 16px;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    
    <!-- Top Header Area -->
    <div class="workflow-header-container mb-4">
        <div class="workflow-title-prefix">
            <span class="workflow-title-line"></span> WORKFLOW
        </div>
        <h1 class="workflow-title">{{ $pageTitle }}</h1>

        <!-- Custom Tab buttons -->
        <div class="intimation-tabs-container" id="intimationStageTabs"></div>

        <!-- Custom Search and controls toolbar -->
        <div class="toolbar-container">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="workflow-search-box" class="search-input" placeholder="Search by name, ASPL ID, phone, city..." oninput="handleSearch(this.value)">
            </div>
            <button class="btn-toolbar-action" type="button" onclick="openManageStagesModal()"><i class="fa-solid fa-list-ol me-1"></i> Manage Stages</button>
            <button class="btn-toolbar-action" id="btn-sort" onclick="toggleSort()">
                <i class="fa-solid fa-arrows-up-down text-muted"></i> <span id="sort-btn-label">Latest First</span>
            </button>
        </div>
    </div>

    <!-- Board Section -->
    <div class="board-container">
        <div class="board-header">
            <div class="board-header-title">
                <span class="board-header-dot"></span>
                <span id="current-board-title">INTIMATION PENDING</span>
            </div>
            <div class="board-header-badge" id="current-board-count">0</div>
        </div>

        <div id="intimation-list-container" class="board-list">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                Loading data...
            </div>
        </div>
    </div>

    <!-- Footer Counter Info -->
    <div class="footer-summary-text" id="footer-summary-info">
        0 of 0 customers · Intimation Pending
    </div>

</div>
@include('workflow.partials.stage-manager')
@endsection

@push('scripts')
<script>
    let intimationColumns = [];
    let currentTab = null;
    let sortOrder = 'latest'; // 'latest' or 'oldest'
    let searchVal = '';
    let allData = { pending: { count: 0, records: [] }, completed: { count: 0, records: [] } };
    let searchDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadData();
    });

    function loadData() {
        fetch('/api/workflow/intimation', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => res.json())
            .then(data => {
                allData = data.data;
                intimationColumns = data.columns || [];
                if (!intimationColumns.some(column => column.key === currentTab)) currentTab = intimationColumns[0]?.key;
                const tabs = document.getElementById('intimationStageTabs');
                tabs.innerHTML = '';
                intimationColumns.forEach(function (column) {
                    const button = document.createElement('button');
                    button.className = 'intimation-tab-btn ' + (column.key === currentTab ? 'active' : 'inactive');
                    button.id = 'tab-' + column.key;
                    button.textContent = column.name + ' (' + (allData[column.key]?.count || 0) + ')';
                    button.addEventListener('click', function () { switchTab(column.key); });
                    tabs.appendChild(button);
                });
                document.getElementById('current-board-title').textContent = intimationColumns.find(column => column.key === currentTab)?.name || 'Intimation';
                renderBoard();
            })
            .catch(err => {
                console.error("Error fetching data:", err);
                document.getElementById('intimation-list-container').innerHTML = `
                    <div class="text-danger text-center py-4 fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Error loading data. Please try again.
                    </div>`;
            });
    }

    function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.intimation-tab-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('inactive');
        });
        
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.remove('inactive');
        activeBtn.classList.add('active');

        const title = intimationColumns.find(column => column.key === tab)?.name || 'Intimation';
        document.getElementById('current-board-title').innerText = title;

        renderBoard();
    }

    function handleSearch(val) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            searchVal = val.trim().toLowerCase();
            renderBoard();
        }, 150); // 150ms debounce
    }

    function toggleSort() {
        if (sortOrder === 'latest') {
            sortOrder = 'oldest';
            document.getElementById('sort-btn-label').innerText = 'Oldest First';
        } else {
            sortOrder = 'latest';
            document.getElementById('sort-btn-label').innerText = 'Latest First';
        }
        renderBoard();
    }

    function formatDuration(createdAtStr) {
        if (!createdAtStr) return '--';
        const created = new Date(createdAtStr);
        const now = new Date();
        const diffMs = now - created;
        if (diffMs < 0) return '0d 0h';
        const diffMins = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMins / 60);
        const days = Math.floor(diffHours / 24);
        const hours = diffHours % 24;
        return `${days}d ${hours}h`;
    }

    function formatCost(totalCost) {
        if (!totalCost) return '₹--';
        const val = Number(totalCost);
        if (isNaN(val)) return '₹' + totalCost;
        return '₹' + val.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    function renderBoard() {
        const rawData = allData[currentTab]?.records || [];
        
        // 1. Client-side Search filter
        let filteredRecords = rawData;
        if (searchVal) {
            filteredRecords = rawData.filter(r => {
                const c = r.customer || {};
                const asplId = (r.aspl_id || '').toLowerCase();
                const name = (c.name || '').toLowerCase();
                const phone = (c.phone || '').toLowerCase();
                const city = (c.city_name || '').toLowerCase();
                return name.includes(searchVal) || asplId.includes(searchVal) || phone.includes(searchVal) || city.includes(searchVal);
            });
        }

        // 2. Client-side Sorting
        const sortedRecords = [...filteredRecords];
        if (sortOrder === 'latest') {
            sortedRecords.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } else {
            sortedRecords.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        // Set counts
        document.getElementById('current-board-count').innerText = sortedRecords.length;
        
        // Footer description
        const totalOverall = Object.values(allData).reduce((sum, item) => sum + (item.count || 0), 0);
        const tabLabelText = intimationColumns.find(column => column.key === currentTab)?.name || 'Intimation';
        document.getElementById('footer-summary-info').innerText = `${sortedRecords.length} of ${totalOverall} customers · ${tabLabelText}`;

        // Populate elements
        const listContainer = document.getElementById('intimation-list-container');
        listContainer.innerHTML = '';

        if (sortedRecords.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-5 text-muted fw-semibold">
                    <i class="fa-solid fa-folder-open fs-4 d-block mb-2"></i> No records found.
                </div>`;
            return;
        }

        sortedRecords.forEach(record => {
            listContainer.innerHTML += generateRowHtml(record);
        });
    }

    function toggleDocumentStatus(id, field, currentStatus) {
        // cycle status: pending -> uploaded -> completed -> pending
        let nextStatus = 'pending';
        if (currentStatus === 'pending' || currentStatus === null) nextStatus = 'uploaded';
        else if (currentStatus === 'uploaded') nextStatus = 'completed';
        
        const payload = {};
        payload[field] = nextStatus;

        fetch(`/api/workflow/intimation/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadData(); // reload to reflect status changes
            }
        });
    }

    function checkAllUploaded(record) {
        const isDone = (status) => status === 'uploaded' || status === 'completed';
        
        if (
            isDone(record.self_certificate_status) && 
            isDone(record.gps_photo_status) && 
            isDone(record.dcr_certificate_status) && 
            isDone(record.model_agreement_status) &&
            record.status !== 'completed'
        ) {
            // Automatically mark workflow as completed if all docs are uploaded
            fetch(`/api/workflow/intimation/${record.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ status: intimationColumns[intimationColumns.length - 1]?.key || 'completed' })
            }).then(() => loadData());
        }
    }

    function getDocCardHtml(record, label, field, iconClass, iconBgColorClass) {
        const status = record[field] || 'pending';
        let cardClass = 'task-card-item shadow-sm';
        let iconHtml = `<div class="task-card-icon-wrapper ${iconBgColorClass}"><i class="${iconClass}"></i></div>`;
        
        if (status === 'ready' || status === 'uploaded' || status === 'completed') {
            cardClass += ' uploaded-state';
            iconHtml = `<div class="task-card-icon-wrapper text-white"><i class="fa-solid fa-check"></i></div>`;
        }

        const displayStatus = status.charAt(0).toUpperCase() + status.slice(1);

        return `
        <div class="${cardClass}" onclick="toggleDocumentStatus(${record.id}, '${field}', '${status}')">
            ${iconHtml}
            <div class="task-details-wrapper">
                <span class="task-label-txt">${label}</span>
                <span class="task-status-txt">${displayStatus}</span>
            </div>
        </div>
        `;
    }

    function generateRowHtml(record) {
        const c = record.customer || {};
        const asplFull = record.aspl_id || 'N/A';
        const asplNumBadge = asplFull.replace('ASPL-', ''); // strip prefix for suffix-only badge next to name
        const name = c.name || 'Unknown';
        const phone = c.phone || '--';
        const city = c.city_name || '--';
        const capacity = c.system_capacity ? c.system_capacity + ' kW' : '--';
        const costStr = formatCost(c.total_cost);
        const docLabel = intimationEscape(intimationColumns.find(column => column.key === currentTab)?.name || 'Intimation');
        const nextColumn = intimationColumns[intimationColumns.findIndex(column => column.key === currentTab) + 1];
        const handlerName = record.assigned_user ? record.assigned_user.name : 'System Handler';
        const timeDiff = formatDuration(record.created_at);

        // Build metadata location/discom string
        const parts = [
            c.address,
            c.sub_division,
            c.division,
            c.city_name,
            c.discom
        ].filter(p => p && p.trim() !== '');
        const metaPillText = parts.length > 0 ? parts.join(' / ').toUpperCase() : '--';

        // Auto transition check
        if (intimationColumns.length === 2 && currentTab === intimationColumns[0]?.key) setTimeout(() => checkAllUploaded(record), 100);

        return `
        <div class="customer-card-row">
            <div class="card-docs-pending-status">${docLabel}</div>
            ${nextColumn ? `<button type="button" class="btn btn-sm btn-outline-dark-blue mb-2" onclick="moveIntimationStage(${record.id}, '${nextColumn.key}')">Next: ${intimationEscape(nextColumn.name)} <i class="bi bi-arrow-right"></i></button>` : ''}
            
            <div class="customer-title-block">
                <h3 class="customer-name-txt">${name}</h3>
                <span class="aspl-badge-pill">${asplNumBadge}</span>
                <span class="metadata-pill-block" title="${metaPillText}">${metaPillText}</span>
            </div>
            
            <div class="metadata-details-row">
                <span><i class="fa-solid fa-phone"></i> ${phone}</span>
                <span><i class="fa-solid fa-location-dot"></i> ${city}</span>
                <span><i class="fa-solid fa-bolt bolt-icon"></i> <span class="bolt-text">${capacity}</span></span>
                <span><i class="fa-solid fa-user"></i> ${handlerName}</span>
                <span class="clock-block"><i class="fa-regular fa-clock"></i> <strong>${timeDiff}</strong></span>
                <span><i class="fa-solid fa-indian-rupee-sign"></i> <span class="cost-text">${costStr}</span></span>
            </div>

            <div class="task-cards-grid">
                ${getDocCardHtml(record, 'SELF CERTIFICATE', 'self_certificate_status', 'fa-solid fa-file-contract', 'bg-icon-blue')}
                ${getDocCardHtml(record, 'GPS PHOTO', 'gps_photo_status', 'fa-solid fa-camera', 'bg-icon-yellow')}
                ${getDocCardHtml(record, 'DCR CERTIFICATE', 'dcr_certificate_status', 'fa-solid fa-file-invoice', 'bg-icon-purple')}
                ${getDocCardHtml(record, 'MODEL AGREEMENT', 'model_agreement_status', 'fa-solid fa-file-signature', 'bg-icon-green')}
            </div>
        </div>
        `;
    }
    function intimationEscape(value) { const span = document.createElement('span'); span.textContent = value; return span.innerHTML; }
    async function moveIntimationStage(id, key) {
        const response = await fetch('/api/workflow/intimation/' + id, { method: 'PUT', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ status: key }) });
        const result = await response.json();
        if (response.ok && result.success) loadData();
        else if (window.Swal) Swal.fire('Unable to move stage', result.message || 'Please try again.', 'error');
    }
    window.workflowStageManager = { workflowType: 'intimation', masterId: function () { return null; }, refresh: function () { return loadData(); } };
</script>
<script src="{{ crm_asset('js/workflow-stage-manager.js') }}"></script>
@endpush
