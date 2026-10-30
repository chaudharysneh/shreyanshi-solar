@extends('layouts.app')
 
@section('page_title', $pageTitle)

@push('styles')
<style>
    .kanban-board {
        display: flex;
        overflow-x: auto;
        padding-bottom: 20px;
        gap: 20px;
        align-items: flex-start;
        min-height: calc(100vh - 200px);
    }
    
    .kanban-column {
        flex: 0 0 320px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 200px);
    }
    
    .kanban-column-header {
        background-color: var(--crm-bg-sidebar);
        color: white;
        padding: 12px 16px;
        border-radius: 11px 11px 0 0;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
    }
    
    .kanban-column-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
    }
    
    .kanban-cards {
        padding: 12px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
    }

    .kanban-board,
    .kanban-cards {
        scrollbar-width: thin;
        scrollbar-color: #808080 transparent;
    }

    .kanban-board::-webkit-scrollbar,
    .kanban-cards::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .kanban-board::-webkit-scrollbar-track,
    .kanban-cards::-webkit-scrollbar-track {
        background: transparent;
    }

    .kanban-board::-webkit-scrollbar-thumb,
    .kanban-cards::-webkit-scrollbar-thumb {
        background: #808080;
        border-radius: 999px;
    }

    .kanban-board::-webkit-scrollbar-thumb:hover,
    .kanban-cards::-webkit-scrollbar-thumb:hover {
        background: #808080;
    }

    .kanban-board::-webkit-scrollbar-button,
    .kanban-cards::-webkit-scrollbar-button {
        display: none;
        width: 0;
        height: 0;
    }
    
    .kanban-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    
    .kanban-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }

    .kanban-card[draggable="true"] { cursor: grab !important; }
    .kanban-card.is-dragging { opacity: 0.45; cursor: grabbing !important; }
    .kanban-column.drop-target {
        outline: 2px dashed var(--bs-primary);
        outline-offset: -2px;
        background: #eef5ff;
    }
    .kanban-board.drag-enabled .kanban-cards { min-height: 180px; }
    .kanban-board.is-saving { opacity: 0.7; pointer-events: none; }
    
    .k-card-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.95rem;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    
    .k-card-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 6px;
    }
    
    .k-badge {
        background: #eef2ff;
        color: #4f46e5;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .k-badge.green {
        background: #f0fdf4;
        color: #16a34a;
    }

    .k-badge.gray {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .k-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
    }
    
    .k-card-footer { gap: 10px; flex-wrap: wrap; }
    .k-card-footer .k-timer { font-size: .72rem; color: #64748b; }
    .k-card-footer > .d-flex { margin-left: auto; }

    .workflow-stage-menu { padding: 4px; }
    .workflow-stage-menu .dropdown-item {
        background-color: #0a2540;
        color: #fff !important;
        border-radius: 6px;
    }
    .workflow-stage-menu .dropdown-item:hover,
    .workflow-stage-menu .dropdown-item:focus,
    .workflow-stage-menu .dropdown-item:active,
    .workflow-stage-menu .dropdown-item.active {
        background-color: #051c33;
        color: #fff !important;
    }
    .workflow-stage-menu .dropdown-item i { color: #fff !important; }
    .workflow-stage-menu .dropdown-item:focus-visible { outline: 2px solid #8ca8c4; outline-offset: 2px; }

    .workflow-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .workflow-search {
        flex: 1;
        min-width: 300px;
        position: relative;
    }

    .workflow-search input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .workflow-search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .btn-workflow {
        background: white;
        border: 1px solid #cbd5e1;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Finance Tabs Styles */
    .finance-tabs-wrapper {
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
    }

    .finance-tabs {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 50rem;
        padding: 6px;
        display: inline-flex;
        flex-wrap: wrap;
        gap: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    
    .btn-finance-tab {
        background: transparent;
        border: none;
        color: #64748b;
        border-radius: 50rem;
        padding: 8px 20px;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-finance-tab i {
        color: #94a3b8;
    }
    
    .btn-finance-tab:hover:not(.active) {
        background: #f8fafc;
        color: #475569;
    }
    
    .btn-finance-tab.active {
        background: #fee2e2;
        color: #dc2626;
        font-weight: 600;
    }

    .btn-finance-tab.active i {
        color: #dc2626;
    }
    
    .btn-finance-tab .badge {
        background: #f1f5f9 !important;
        color: #64748b !important;
        border-radius: 50rem;
        padding: 4px 8px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .btn-finance-tab.active .badge {
        background: white !important;
        color: #dc2626 !important;
    }

    /* Move Modal Styles */
    .modal-move-stage .modal-header {
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 11px 11px 0 0;
    }
    .modal-move-stage .stage-flow {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f0fdf4;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .modal-move-stage .stage-flow .badge {
        font-size: 0.9rem;
        padding: 6px 12px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    
    <div class="mb-4">
        <span class="fw-bold small text-uppercase letter-spacing-1 d-flex align-items-center gap-2 mb-1" style="color: var(--crm-bg-sidebar);">
            <span style="width: 24px; height: 2px; background-color: var(--crm-bg-sidebar); display: inline-block;"></span> WORKFLOW
        </span>
        <h1 class="h2 fw-bold text-dark mb-0">{{ $pageTitle }}</h1>
    </div>

    <div class="workflow-header">
        <div class="workflow-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, phone, city, ASPL ID...">
        </div>
        <div class="workflow-actions d-flex gap-2">
            <button type="button" class="btn-workflow" onclick="openManageStagesModal()">
                <i class="fa-solid fa-list-ol text-primary"></i> Add Stages
            </button>
        </div>
    </div>

    @if(in_array($stage, ['finance', 'installations']) && isset($financeOptions))
    <div class="finance-tabs-wrapper">
        <div class="finance-tabs">
            @foreach($financeOptions as $opt)
            <button class="btn-finance-tab {{ $loop->first ? 'active' : '' }}" data-finance-id="{{ $opt->id }}">
                <i class="fa-solid {{ $stage === 'finance' ? 'fa-indian-rupee-sign' : 'fa-bolt' }}"></i> {{ $opt->name }} <span class="badge">{{ $opt->current_count }}</span>
            </button>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Kanban Board -->
    <div class="kanban-board" id="kanbanBoard">
        <!-- Columns will be injected here via JS -->
    </div>
</div>

<!-- Move Stage Modal -->
<div class="modal fade modal-move-stage" id="moveStageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark">Move Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-muted small mb-3">Customer: <span id="modalCustomerName" class="fw-bold text-dark"></span> (<span id="modalAsplId"></span>)</div>
                
                <div class="stage-flow">
                    <span class="badge bg-light text-secondary border">In Progress</span>
                    <i class="fa-solid fa-arrow-right text-success"></i>
                    <span class="badge bg-success text-white">Completed / Next Stage</span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">DATE *</label>
                    <input type="date" id="moveDate" class="form-control form-control-lg bg-light" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">REMARK / NOTE</label>
                    <textarea id="moveRemark" class="form-control bg-light" rows="3" placeholder="Add a note about this stage change..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success flex-grow-1 fw-bold py-2" onclick="confirmMove()">
                        <i class="fa-regular fa-circle-check me-2"></i> Confirm Move
                    </button>
                    <button type="button" class="btn btn-outline-secondary py-2 px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-body p-0">
                <div class="p-4 bg-white position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.8rem;"></button>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-user-group fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark" id="detailName">KIRTAN UGREJA</h5>
                            <span class="badge bg-success bg-opacity-25 text-success px-2 py-1" id="detailAsplId">0020</span>
                        </div>
                    </div>
                </div>

                <div class="bg-success bg-opacity-25 px-4 py-2 d-flex justify-content-between align-items-center text-success fw-bold" style="font-size: 0.9rem;">
                    <div><i class="fa-solid fa-arrow-right me-2"></i> <span id="detailStatus">Pending Planning</span></div>
                    <div><i class="fa-regular fa-clock me-1"></i> <span id="detailTime">3d 23h</span></div>
                </div>

                <div class="bg-white px-4 py-3 d-flex justify-content-between align-items-center border-bottom text-muted" style="font-size: 0.85rem;">
                    <div><i class="fa-solid fa-phone me-1"></i> <span id="detailPhone">9879879871</span></div>
                    <div><i class="fa-solid fa-location-dot me-1"></i> <span id="detailCity">PUNE</span></div>
                    <div class="text-success fw-bold"><i class="fa-solid fa-bolt me-1"></i> <span id="detailCapacity">10 kW</span></div>
                </div>

                <div class="p-4 bg-white text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;" id="detailHistoryContent">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        <i class="fa-regular fa-clock text-secondary fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">No moves recorded yet</h6>
                    <p class="text-muted small mb-0">Move this card to start tracking stage history</p>
                </div>

                <div class="p-3 bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Stages Modal -->
@include('workflow.partials.stage-manager')

@endsection

@push('scripts')
<script>
    const stage = '{{ $stage }}';
    const dragEnabled = [
        'document-verification', 'registration', 'feasibility', 'finance',
        'waiting-floor', 'dispatch', 'installations', 'net-metering', 'subsidy',
    ].includes(stage);
    let columnsConfig = @json($columns);
    let currentMovingRecord = null;
    let currentMovingNextStatus = null;
    let moveModal = null;
    let detailsModal = null;
    let activeFinanceId = '';
    let draggedCard = null;
    let savingStage = false;
    let boardRequest = null;

    document.addEventListener('DOMContentLoaded', function() {
        moveModal = new bootstrap.Modal(document.getElementById('moveStageModal'));
        detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        document.getElementById('moveDate').valueAsDate = new Date();
        
        // Initialize activeFinanceId based on the first active tab
        const activeTab = document.querySelector('.btn-finance-tab.active');
        if (activeTab) {
            activeFinanceId = activeTab.getAttribute('data-finance-id');
        }

        loadKanbanData();

        document.getElementById('searchInput').addEventListener('input', debounce(function(e) {
            loadKanbanData(e.target.value);
        }, 500));

        // Finance Tab clicks
        const tabs = document.querySelectorAll('.btn-finance-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                activeFinanceId = this.getAttribute('data-finance-id');
                loadKanbanData(document.getElementById('searchInput').value);
            });
        });
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async function loadKanbanData(search = document.getElementById('searchInput').value) {
        boardRequest?.abort();
        const currentRequest = new AbortController();
        boardRequest = currentRequest;
        try {
            let url = `/api/workflow/${stage}`;
            const params = new URLSearchParams();
            if (search.trim()) params.append('search', search.trim());
            if (activeFinanceId) params.append('finance_id', activeFinanceId);
            
            const queryString = params.toString();
            if (queryString) {
                url += `?${queryString}`;
            }
            
            const response = await fetch(url, {
                signal: currentRequest.signal,
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('token') // If sanctum token is used, or rely on web session
                }
            });
            const res = await response.json();
            if (currentRequest !== boardRequest) return;
            if (!response.ok || !res.success) throw new Error(res.message || 'Unable to search workflow records.');
            
            if (res.success) {
                if (res.columns) {
                    columnsConfig = res.columns;
                }
                renderBoard(res.data);
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Failed to load kanban data', error);
            if (typeof window.showAlert === 'function') window.showAlert('error', error.message);
        }
    }

    function renderBoard(data) {
        const board = document.getElementById('kanbanBoard');
        board.classList.toggle('drag-enabled', dragEnabled);
        board.innerHTML = '';
        
        const numCols = columnsConfig.length;
        let columnKeys = [];
        
        if (columnsConfig[0] && columnsConfig[0].key) {
            columnKeys = columnsConfig.map(c => c.key);
        } else {
            for (let i = 0; i < numCols; i++) {
                if (i === 0) columnKeys.push('pending');
                else if (i === 1) columnKeys.push('in_progress');
                else if (i === numCols - 1) columnKeys.push('completed');
                else columnKeys.push('stage_' + (i + 1));
            }
        }

        columnsConfig.forEach((colConfig, index) => {
            const key = columnKeys[index];
            const colData = data[key] || { count: 0, records: [] };
            
            let html = `
            <div class="kanban-column" data-status="${key}">
                <div class="kanban-column-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white text-success rounded-circle p-1" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;">${index + 1}</span>
                        ${workflowStageText(colConfig.name)}
                    </div>
                    <div class="kanban-column-badge">${colData.count}</div>
                </div>
                <div class="kanban-cards">
            `;

            if (colData.records.length === 0) {
                html += `<div class="d-flex align-items-center justify-content-center h-100 text-muted small py-5">No customers</div>`;
            } else {
                colData.records.forEach(record => {
                    html += generateCardHtml(record, key, index, columnKeys);
                });
            }

            html += `</div></div>`;
            board.innerHTML += html;
        });
        bindBoardDragAndDrop(board);
    }

    function bindBoardDragAndDrop(board) {
        if (!dragEnabled) return;

        board.querySelectorAll('.kanban-card').forEach(card => {
            card.draggable = true;
            card.addEventListener('dragstart', event => {
                if (savingStage || event.target.closest('a, button, .dropdown, .k-card-footer')) {
                    event.preventDefault();
                    return;
                }
                draggedCard = card;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.recordId);
                card.classList.add('is-dragging');
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('is-dragging');
                draggedCard = null;
                board.querySelectorAll('.drop-target').forEach(column => column.classList.remove('drop-target'));
            });
        });

        board.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', event => {
                if (!draggedCard || savingStage || draggedCard.closest('.kanban-column') === column) return;
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                board.querySelectorAll('.drop-target').forEach(target => target.classList.remove('drop-target'));
                column.classList.add('drop-target');
                const cards = column.querySelector('.kanban-cards');
                const bounds = cards.getBoundingClientRect();
                if (event.clientY < bounds.top + 45) cards.scrollTop -= 15;
                if (event.clientY > bounds.bottom - 45) cards.scrollTop += 15;
                const boardBounds = board.getBoundingClientRect();
                if (event.clientX < boardBounds.left + 60) board.scrollLeft -= 20;
                if (event.clientX > boardBounds.right - 60) board.scrollLeft += 20;
            });
            column.addEventListener('dragleave', event => {
                if (!column.contains(event.relatedTarget)) column.classList.remove('drop-target');
            });
            column.addEventListener('drop', async event => {
                event.preventDefault();
                column.classList.remove('drop-target');
                if (!draggedCard || savingStage || draggedCard.closest('.kanban-column') === column) return;
                const id = draggedCard.dataset.recordId;
                draggedCard.classList.remove('is-dragging');
                draggedCard = null;
                savingStage = true;
                board.classList.add('is-saving');
                board.setAttribute('aria-busy', 'true');
                try {
                    await updateStatus(id, column.dataset.status);
                } finally {
                    savingStage = false;
                    board.classList.remove('is-saving');
                    board.removeAttribute('aria-busy');
                }
            });
        });
    }

    function hasCardValue(value) {
        return value !== null && value !== undefined && !['', '--', '-', 'n/a', 'null'].includes(String(value).trim().toLowerCase());
    }

    function workflowCardTimestamp(record) {
        const value = record.updated_at || record.created_at;
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const formatted = new Intl.DateTimeFormat('en-GB', {
            timeZone: @json(config('app.timezone')), day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        }).format(date);
        return `<span class="k-timer" title="${record.updated_at ? 'Last updated' : 'Created'}"><i class="fa-regular fa-clock" aria-hidden="true"></i> <time datetime="${date.toISOString()}">${formatted}</time></span>`;
    }

    function generateCardHtml(record, statusKey, colIndex, columnKeys) {
        const c = record.customer || {};
        const aspl = record.aspl_id || 'N/A';
        const name = c.name || 'Unknown';
        const phone = c.phone || '--';
        const city = c.city_name || '--';
        const capacity = c.system_capacity ? c.system_capacity + ' kW' : '--';
        const panels = c.no_of_panels ? c.no_of_panels + ' panels' : '--';
        const inverter = c.inverter_kw ? c.inverter_kw + ' kW' : '--';
        const cost = c.total_cost ? Number(c.total_cost).toLocaleString('en-IN') : '--';
        
        // Navigation arrows logic dynamically based on columns
        let leftArrow = `<i class="fa-solid fa-chevron-left" style="cursor:not-allowed; opacity:0.5;"></i>`;
        let rightArrow = `<i class="fa-solid fa-chevron-right" style="cursor:not-allowed; opacity:0.5;"></i>`;
        
        if (colIndex > 0) {
            const prevKey = columnKeys[colIndex - 1];
            leftArrow = `<i class="fa-solid fa-chevron-left text-primary" style="cursor:pointer;" onclick="event.stopPropagation(); updateStatus(${record.id}, '${prevKey}')" title="Move to Previous"></i>`;
        }

        if (colIndex < columnKeys.length - 1) {
            const nextKey = columnKeys[colIndex + 1];
            if (colIndex === columnKeys.length - 2) {
                rightArrow = `
                <div class="dropdown d-inline-block">
                    <i class="fa-solid fa-chevron-right text-primary fs-6" data-bs-toggle="dropdown" style="cursor:pointer; padding: 5px 10px;" title="Move Stage"></i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 workflow-stage-menu" style="min-width: 150px; border-radius: 8px;">
                        <li><a class="dropdown-item py-2 fw-medium" href="#" onclick="event.preventDefault(); event.stopPropagation(); openMoveModal(${record.id}, '${name.replace(/'/g, "\\'")}', '${aspl}', '${nextKey}')"><i class="fa-regular fa-circle-check me-2 text-success"></i>${workflowStageText(columnsConfig[colIndex + 1]?.name || 'Completed')}</a></li>
                    </ul>
                </div>`;
            } else {
                rightArrow = `
                <div class="dropdown d-inline-block">
                    <i class="fa-solid fa-chevron-right text-primary fs-6" data-bs-toggle="dropdown" style="cursor:pointer; padding: 5px 10px;" title="Move Stage"></i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 workflow-stage-menu" style="min-width: 150px; border-radius: 8px;">
                        <li><a class="dropdown-item py-2 fw-medium" href="#" onclick="event.preventDefault(); event.stopPropagation(); updateStatus(${record.id}, '${nextKey}')"><i class="fa-regular fa-circle me-2 text-primary"></i>${workflowStageText(columnsConfig[colIndex + 1]?.name || 'Next')}</a></li>
                    </ul>
                </div>`;
            }
        }

        return `
        <div class="kanban-card" data-record-id="${record.id}" style="cursor: pointer;" onclick="if (!draggedCard && !savingStage) openDetailsModal('${name.replace(/'/g, "\\'")}', '${aspl}', '${phone || ''}', '${city || ''}', '${capacity}', '${statusKey}', ${record.customer_id})">
            <div class="k-card-title">${workflowStageText(name)}</div>
            ${(hasCardValue(record.aspl_id) || hasCardValue(c.system_capacity)) ? `<div class="k-card-row">
                ${hasCardValue(record.aspl_id) ? `<span class="k-badge green">${workflowStageText(record.aspl_id)}</span>` : ''}
                ${hasCardValue(c.system_capacity) ? `<span class="k-badge green">${workflowStageText(c.system_capacity)} kW</span>` : ''}
            </div>` : ''}
            ${(hasCardValue(c.phone) || hasCardValue(c.city_name)) ? `<div class="k-card-row">
                ${hasCardValue(c.phone) ? `<span><i class="fa-solid fa-phone"></i> ${workflowStageText(c.phone)}</span>` : ''}
                ${hasCardValue(c.city_name) ? `<span><i class="fa-solid fa-location-dot"></i> ${workflowStageText(c.city_name)}</span>` : ''}
            </div>` : ''}
            ${hasCardValue(c.no_of_panels) ? `<div class="k-card-row"><i class="fa-solid fa-bolt"></i> ${workflowStageText(c.no_of_panels)} panels</div>` : ''}
            ${hasCardValue(c.inverter_kw) ? `<div class="k-card-row"><i class="fa-solid fa-server"></i> ${workflowStageText(c.inverter_kw)} kW</div>` : ''}
            ${hasCardValue(c.total_cost) && Number.isFinite(Number(c.total_cost)) ? `<div class="k-card-row mt-2 text-dark fw-bold"><i class="fa-solid fa-indian-rupee-sign"></i> ${Number(c.total_cost).toLocaleString('en-IN')}</div>` : ''}
            <div class="k-card-footer" onclick="event.stopPropagation()">
                ${workflowCardTimestamp(record)}
                <div class="d-flex align-items-center gap-3">
                    ${leftArrow}
                    ${rightArrow}
                </div>
            </div>
        </div>
        `;
    }

    async function updateStatus(id, newStatus) {
        try {
            const response = await fetch(`/api/workflow/${stage}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ status: newStatus, finance_id: activeFinanceId || null })
            });
            const res = await response.json();
            
            if (response.ok && res.success) {
                await loadKanbanData(document.getElementById('searchInput').value);
                if (typeof toastr !== 'undefined') {
                    toastr.success('Successfully updated status!');
                }
            } else {
                alert(res.message || 'Error updating status. Check console for details.');
                console.error('Update status failed:', res);
            }
        } catch (e) {
            console.error('Update status exception:', e);
            alert('An error occurred while updating status.');
        }
    }

    function openMoveModal(id, customerName, asplId, nextKey = 'completed') {
        currentMovingRecord = id;
        currentMovingNextStatus = nextKey;
        document.getElementById('modalCustomerName').textContent = customerName;
        document.getElementById('modalAsplId').textContent = asplId;
        document.getElementById('moveRemark').value = '';
        moveModal.show();
    }

    async function confirmMove() {
        if (!currentMovingRecord) return;
        
        const remark = document.getElementById('moveRemark').value;
        const btn = document.querySelector('.modal-move-stage .btn-success');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        try {
            const response = await fetch(`/api/workflow/${stage}/${currentMovingRecord}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ 
                    status: currentMovingNextStatus,
                    finance_id: activeFinanceId || null,
                    remarks: remark 
                })
            });
            const res = await response.json();
            
            if (res.success) {
                moveModal.hide();
                loadKanbanData(); // Refresh board
                
                // Show success toast (using whatever library is available, fallback to alert)
                if (typeof toastr !== 'undefined') {
                    toastr.success('Successfully moved to next stage!');
                }
            } else {
                alert(res.message || 'Error moving stage. Check console for details.');
                console.error('Move stage failed:', res);
            }
        } catch (e) {
            console.error('Move stage exception:', e);
            alert('An error occurred while moving stage.');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    async function openDetailsModal(name, asplId, phone, city, capacity, statusKey, customerId) {
        document.getElementById('detailName').textContent = name || 'Unknown';
        document.getElementById('detailAsplId').textContent = asplId || 'N/A';
        document.getElementById('detailPhone').textContent = phone || '--';
        document.getElementById('detailCity').textContent = city || '--';
        document.getElementById('detailCapacity').textContent = capacity || '--';
        
        let statusText = 'Pending';
        if (statusKey === 'in_progress') statusText = 'In Progress';
        if (statusKey === 'completed') statusText = 'Completed';
        
        const stageName = '{{ $pageTitle }}'; 
        document.getElementById('detailStatus').textContent = statusText + ' ' + stageName;
        
        detailsModal.show();
        
        // Fetch history
        if (customerId) {
            const historyContainer = document.getElementById('detailHistoryContent');
            historyContainer.innerHTML = '<div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>';
            
            try {
                const response = await fetch(`/api/workflow/customer-history/${customerId}`);
                const res = await response.json();
                
                if (res.success && res.data.length > 0) {
                    let historyHtml = '<div class="w-100 text-start px-2 py-3" style="max-height: 400px; overflow-y: auto;">';
                    res.data.forEach(item => {
                        let fromStage = item.transition.split(' -> ')[0] || 'Started';
                        let toStage = item.transition.split(' -> ')[1] || item.stage;
                        historyHtml += `
                            <div class="mb-3 p-3 border rounded shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                                        <span class="badge bg-light text-dark border p-2">${fromStage}</span> 
                                        <i class="fa-solid fa-arrow-right text-muted"></i> 
                                        <span class="badge bg-success bg-opacity-25 text-success p-2">${toStage}</span>
                                    </div>
                                    <div class="text-muted small">By ${item.by}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="border rounded p-3 bg-white flex-grow-1" style="min-width: 150px;">
                                        <div class="text-muted small fw-bold mb-1 d-flex align-items-center gap-2"><div class="bg-success bg-opacity-25 text-success rounded p-1"><i class="fa-regular fa-calendar"></i></div> DATE</div>
                                        <div class="fw-bold text-dark">${item.date}</div>
                                    </div>
                                    <div class="border rounded p-3 bg-white flex-grow-1" style="min-width: 150px;">
                                        <div class="text-muted small fw-bold mb-1 d-flex align-items-center gap-2"><div class="bg-light text-secondary rounded p-1"><i class="fa-regular fa-note-sticky"></i></div> NOTE</div>
                                        <div class="text-dark">${item.note}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    historyHtml += '</div>';
                    historyContainer.innerHTML = historyHtml;
                    historyContainer.style.minHeight = 'auto';
                } else {
                    historyContainer.innerHTML = `
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                            <i class="fa-regular fa-clock text-secondary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">No moves recorded yet</h6>
                        <p class="text-muted small mb-0">Move this card to start tracking stage history</p>
                    `;
                }
            } catch (error) {
                historyContainer.innerHTML = '<div class="text-danger p-4">Failed to load history</div>';
            }
        }
    }

    function workflowStageText(value) { const span = document.createElement('span'); span.textContent = value; return span.innerHTML; }
    window.workflowStageManager = {
        workflowType: stage,
        masterId: function () { return activeFinanceId; },
        refresh: function () { return loadKanbanData(document.getElementById('searchInput').value); }
    };
</script>
<script src="{{ crm_asset('js/workflow-stage-manager.js') }}"></script>
@endpush
