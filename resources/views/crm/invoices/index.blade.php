@extends('layouts.app')

@section('page_title', 'Invoices')

@section('content')
    <style>
        .invoice-kpi { border:1px solid #e1e7ef; border-left:3px solid var(--invoice-kpi-color); border-radius:14px; background:var(--invoice-kpi-bg); padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; height:100%; }
        .invoice-kpi-label { color:#63748a; font-size:13px; font-weight:600; margin-bottom:6px; }
        .invoice-kpi-value { color:#0a2540; font-size:28px; font-weight:700; line-height:1.25; overflow-wrap:anywhere; }
        .invoice-kpi-icon { width:44px; height:44px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--invoice-kpi-icon-bg); color:var(--invoice-kpi-color); font-size:21px; }
        .invoice-kpi-note { color:#63748a; font-size:12px; margin-top:6px; }
        .invoice-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 110px;
            min-height: 32px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .invoice-status-paid,
        .invoice-status-paid:hover,
        .invoice-status-paid:focus {
            background-color: #dcfce7;
            color: #166534;
        }
        .invoice-status-unpaid,
        .invoice-status-unpaid:hover,
        .invoice-status-unpaid:focus {
            background-color: #fef3c7;
            color: #92400e;
        }
        .invoice-status-cancelled,
        .invoice-status-cancelled:hover,
        .invoice-status-cancelled:focus {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Invoices</h4>
                        <p class="text-muted small mb-0">Manage customer billing and payment records.</p>
                    </div>
                    <div class="d-flex flex-row gap-2 invoice-header-actions w-100 w-md-auto">
                        @can('invoices.create')
                            <a href="{{ route('invoices.create') }}" class="btn btn-dark-blue flex-fill flex-md-grow-0">
                                <i class="bi bi-plus-lg me-1"></i>Create Invoice
                            </a>
                        @endcan
                        @can('invoices.view')
                            <a href="{{ route('invoices.export') }}" class="btn btn-outline-dark-blue flex-fill flex-md-grow-0">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="row g-3 mb-3" aria-live="polite">
                    @foreach(['paid' => ['Paid Amount', 'bi-check-circle', '#15803d', '#dcfce7', 'Actual receipts and recorded settlements'], 'pending' => ['Pending Amount', 'bi-clock-history', '#b45309', '#fef3c7', 'Remaining invoice balances']] as $key => $card)
                        <div class="col-12 col-md-6">
                            <div class="invoice-kpi" style="--invoice-kpi-color:{{ $card[2] }};--invoice-kpi-bg:{{ $key === 'paid' ? '#f3faf7' : '#fffbf2' }};--invoice-kpi-icon-bg:{{ $card[3] }};">
                                <div class="min-width-0">
                                    <div class="invoice-kpi-label">{{ $card[0] }}</div>
                                    <div id="invoice-{{ $key }}-amount" class="invoice-kpi-value">—</div>
                                    <div class="invoice-kpi-note">{{ $card[4] }}</div>
                                </div>
                                <span class="invoice-kpi-icon" aria-hidden="true"><i class="bi {{ $card[1] }}"></i></span>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-12 mt-2"><small class="text-muted">Totals for all matching invoices, across every page.</small></div>
                </div>
                @include('crm.partials.module-filter-config', ['module' => 'invoices', 'searchId' => 'invoiceSearch', 'placeholder' => 'Search invoices...'])
            </div>
            <div class="card-body p-0">
                @if(!auth()->user()->isAdmin())
                <div class="px-4 pt-3">
                    <ul class="nav nav-tabs crm-filter-tabs" id="invoiceFilterTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                                Created By Me
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                                Assigned To Me
                            </button>
                        </li>
                    </ul>
                </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="invoicesTableMain">
                        <thead>
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th class="text-start">Customer Name</th>
                                <th class="text-start">Invoice Name</th>
                                <th class="text-center d-none d-md-table-cell">Invoice No</th>
                                <th class="text-center d-none d-md-table-cell">Invoice Date</th>
                                <th class="text-center d-none d-md-table-cell">Due Date</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="text-center d-none d-md-table-cell" style="width: 150px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesTable"></tbody>
                    </table>
                </div>

                <div id="invoicePaginationContainer" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/main.css') }}">
    <style>
        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.crmUserPermissions = {
            invoices: {
                view: @json(auth()->user()?->hasMatrixPermission('view_invoices')),
                create: @json(auth()->user()?->hasMatrixPermission('create_invoices')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_invoices')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_invoices')),
            }
        };
    </script>
    <script src="{{ crm_asset('js/invoice.js') }}"></script>
@endpush
