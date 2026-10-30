@extends('layouts.app')

@section('page_title', 'Inventory History')

@push('styles')
    <link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Inventory History</h4>
                    <p class="text-muted small mb-0">{{ $product->name }} - Stock history</p>
                </div>
                <a href="{{ route('inventory.index') }}" class="btn btn-dark-blue">
                    <i class="fa-solid fa-angle-left pe-2"></i>Back
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="historyTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-center" style="width: 80px;">Sr.No</th>
                            <th class="text-center">Type</th>
                            <th class="d-none d-md-table-cell text-center">Current Stock</th>
                            <th class="d-none d-md-table-cell text-center">Stock IN/OUT</th>
                            <th class="d-none d-md-table-cell text-center">Create By</th>
                            <th class="d-none d-md-table-cell text-center text-end pe-4" style="width: 180px;">Date</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="card-footer border-top-0 py-4 px-4" id="historyPagination"></div>
        </div>
    </div>
</div>

<input type="hidden" id="productId" value="{{ $product->id }}">

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ crm_asset('js/inventory-history.js') }}"></script>
@endpush
