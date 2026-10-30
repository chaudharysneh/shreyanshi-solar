@extends('layouts.masters')

@section('page_title', 'Masters - Customers')

@section('masters_content')

    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-0">Manage Customers</h4>
                <p class="text-muted small mb-0">View and manage your customer database and communication history.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('customers.create')
                    <button type="button" class="btn btn-outline-dark-blue" onclick="showImportDialog()">
                        <i class="fa-solid fa-upload me-1"></i>Import CSV
                    </button>
                @endcan
                @can('customers.view')
                    <a href="{{ route('masters.customers.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                @endcan
                @can('customers.create')
                    <a href="{{ route('masters.customers.create') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-plus me-1"></i>Add Customer
                    </a>
                @endcan
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex flex-nowrap gap-2 align-items-center" style="width:100%;max-width:450px;min-width:0;">
            <div class="input-group input-group-sm" style="max-width: 330px; flex:1; min-width:0;">
                <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="customerSearch" class="form-control crm-search-input border-0"
                    placeholder="Search customers..." name="search" value="{{ request('search') }}">
            </div>
            <button class="btn btn-outline-dark-blue flex-shrink-0 text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#customerFilters" aria-expanded="true" aria-controls="customerFilters"><i class="fa-solid fa-filter me-1"></i>Filters</button>
            </div>
            <div class="d-flex align-items-center gap-2"><label for="customerPerPage" class="text-muted small text-nowrap">Show per page:</label><select id="customerPerPage" class="form-select form-select-sm" style="width:88px;">@foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select></div>
        </div>
        <div id="customerFilters" class="collapse show">
            <div class="row gx-3 gy-3 gy-xl-0 mt-3 p-3 border rounded-4" style="background-color:var(--bs-tertiary-bg, #f8fafc);">
                <div class="col-md-6 col-xl-3"><label for="customerDateRange" class="form-label fw-semibold">From - To</label><div class="input-group"><span class="input-group-text"><i class="bi bi-calendar"></i></span><input id="customerDateRange" class="form-control" placeholder="Select date range" autocomplete="off"></div></div>
                <div class="col-md-6 col-xl-3"><label for="customerStatus" class="form-label fw-semibold">Status</label><select id="customerStatus" class="form-select"><option value="">All statuses</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
                <div class="col-md-6 col-xl-3"><label for="customerType" class="form-label fw-semibold">Customer Type</label><select id="customerType" class="form-select"><option value="">All types</option>@foreach(['Individual','Corporate','Government','NGO'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-3 d-flex align-items-end"><button type="button" id="clearCustomerFilters" class="btn btn-dark-blue w-100"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button></div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customerTable" class="table table-hover align-middle mb-0 responsive-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                        <th class="text-start">Customer Name</th>
                        <th class="d-none d-md-table-cell">Email</th>
                        <th class="d-none d-md-table-cell">Pipeline Stage</th>
                        <th class="d-none d-md-table-cell">Created By</th>
                        <th class="d-none d-md-table-cell">Created At</th>
                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="customersTable"></tbody>
            </table>
        </div>

        <!-- Pagination Container -->
        <div id="customerPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
    </div>
    <form id="customersImportForm" class="d-none" enctype="multipart/form-data">
        @csrf
        <input type="file" name="import_file" id="customersImportFile" accept=".csv,text/csv">
    </form>
@endsection

@push('styles')
    <style>
        .customer-action-disabled,
        .customer-action-disabled:hover,
        .customer-action-disabled:focus {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 1;
            box-shadow: none;
        }

        [data-theme="dark"] .customer-action-disabled,
        [data-theme="dark"] .customer-action-disabled:hover,
        [data-theme="dark"] .customer-action-disabled:focus {
            background-color: #1e293b;
            border-color: rgba(255, 255, 255, .08);
            color: #64748b;
        }

        /* Pagination Styles */
        .crm-pagination-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            padding-top: 0.5rem;
        }

        @media (min-width: 768px) {
            .crm-pagination-container {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .crm-pagination .page-item {
            margin: 0 3px;
        }

        .crm-pagination .page-link {
            border-radius: 10px !important;
            padding: 0.6rem 1.1rem;
            color: #475569;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .crm-pagination .page-link:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-color: #0f172a !important;
            color: #fff !important;
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.2);
        }

        .crm-pagination .page-item.disabled .page-link {
            background-color: #f8fafc;
            color: #cbd5e1;
            border-color: #f1f5f9;
            box-shadow: none;
            cursor: not-allowed;
        }

        [data-theme="dark"] .crm-pagination .page-link {
            background-color: #1e293b;
            border-color: rgba(255,255,255,0.06);
            color: #94a3b8;
        }

        [data-theme="dark"] .crm-pagination .page-link:hover {
            background-color: #243146;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        [data-theme="dark"] .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border-color: #3b82f6 !important;
            color: #0f172a !important;
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
        }

        .customer-pipeline-flow { box-sizing:border-box; overflow:hidden; padding:22px 28px; border:1px solid #edf0f7; border-radius:16px; background:radial-gradient(circle at 50% 45%,#fff 0,#fbfcff 52%,#f7f9fd 100%); scrollbar-width:thin; scrollbar-color:#0a2540 #eef2f7; }
        .customer-pipeline-flow::-webkit-scrollbar { height:5px; }
        .customer-pipeline-flow::-webkit-scrollbar-track { background:#eef2f7; border-radius:999px; }
        .customer-pipeline-flow::-webkit-scrollbar-thumb { background:#0a2540; border-radius:999px; }
        .s-pipeline-canvas { position:relative; width:100%; min-height:200px; min-width:0; padding: 20px 0; }
        .s-pipeline-connectors { position:absolute; inset:0; width:100%; height:100%; overflow:visible; pointer-events:none; z-index:1; }
        .s-pipeline-connector { fill:none; stroke:#cbd5e1; stroke-width:4; stroke-linecap:round; transition: stroke 0.3s ease; }
        .s-pipeline-connector.is-completed { stroke: #22c55e; stroke-width: 4; }
        .s-pipeline-node { position:absolute; z-index:2; left:0; top:0; width:120px; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center; transition:transform .2s ease; cursor:pointer; background:transparent !important; border:none !important; box-shadow:none !important; }
        .s-pipeline-node:hover,.s-pipeline-node.is-drag-over { z-index:3; transform:translateY(-3px); }
        .s-pipeline-node.is-drag-over .s-pipeline-node__marker { outline:4px solid rgba(34, 197, 94, 0.2); }
        .s-pipeline-node__marker { width:36px; height:36px; flex:0 0 36px; display:flex; align-items:center; justify-content:center; border:2px solid var(--stage-color, #cbd5e1); border-radius:50%; color:var(--stage-color, #64748b); background:var(--stage-color-light, #fff); font-size:.9rem; font-weight:700; transition: all 0.3s ease; position:relative; z-index:2; }
        .s-pipeline-node.is-completed .s-pipeline-node__marker { border-color: var(--stage-color, #22c55e); background: var(--stage-color, #22c55e); color: #fff; border-width: 2px; }
        .s-pipeline-node.is-current .s-pipeline-node__marker { border-color: var(--stage-color, #22c55e); border-width: 2px; color: var(--stage-color, #22c55e); background: #fff; box-shadow: 0 0 0 4px var(--stage-color-light, rgba(34,197,94,0.15)); outline:2px solid var(--stage-color, #22c55e); outline-offset:3px; }
        .s-pipeline-node__name { display:block; color:var(--stage-color, #64748b); font-size:.75rem; font-weight:700; line-height:1.2; word-break: break-word; }
        .s-pipeline-node.is-current .s-pipeline-node__name { color: var(--stage-color, #22c55e); font-weight: 800; }
        .s-pipeline-node.is-completed .s-pipeline-node__name { color: #0a2540; }
        .customer-pipeline-person { box-sizing:border-box; width:220px; max-width:100%; display:flex; align-items:center; gap:9px; overflow:hidden; padding:9px 11px; border:1px solid #b5c5d6; border-radius:12px; background:#fff; box-shadow:0 6px 18px rgba(30,41,59,.11); text-align:left; cursor:grab; }
        .customer-pipeline-person[draggable="false"] { cursor:default; }
        .customer-pipeline-person.is-dragging { opacity:.45; cursor:grabbing; }
        .customer-pipeline-person__avatar { width:34px; height:34px; flex:0 0 34px; display:flex; align-items:center; justify-content:center; border-radius:9px; color:#fff; background:linear-gradient(135deg,#0a2540,#1b3d60); font-size:.78rem; font-weight:800; }
        .customer-pipeline-person__details { min-width:0; flex:1 1 auto; display:block; overflow:hidden; }
        .customer-pipeline-person__details strong { display:block; overflow:hidden; color:#1e293b; font-size:.76rem; text-overflow:ellipsis; white-space:nowrap; }
        .customer-pipeline-person__details small { display:block; overflow:hidden; color:#64748b; font-size:.62rem; text-overflow:ellipsis; white-space:nowrap; }
    </style>
@endpush

@include('partials.customer-document-modal')
@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/customer-documents.css') }}">
@endpush
@push('scripts')
<script src="{{ crm_asset('js/customer-document-uploader.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploader = new window.CustomerDocumentUploader({customer:true,url:id=>@json(url('/masters/customers'))+'/'+id+'/customer-docs'});
    document.addEventListener('click',function (event) {
        const button=event.target.closest('.customer-documents-upload');
        if (button) uploader.open(button.dataset.id);
    });
});
</script>
    <script>
        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            customers: {
                view: @json(auth()->user()?->hasMatrixPermission('view_customers')),
                create: @json(auth()->user()?->hasMatrixPermission('create_customers')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_customers')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_customers')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/customer.js') }}"></script>
@endpush
