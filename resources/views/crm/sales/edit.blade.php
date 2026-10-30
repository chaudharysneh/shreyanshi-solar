@extends('layouts.app')

@section('page_title', 'Sales - Edit')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
        .input-group .select2-container,
        .sales-search-select {
            flex: 1 1 auto;
            width: 1% !important;
            min-width: 0;
        }
        .sales-search-select__native {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        .sales-search-select {
            position: relative;
        }
        .sales-search-select__toggle {
            width: 100%;
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.375rem 0.75rem;
        }
        .sales-search-select__dropdown {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            z-index: 1060;
            display: none;
            background: #fff;
            border: 1px solid #86b7fe;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
        }
        .sales-search-select.open .sales-search-select__dropdown {
            display: block;
        }
        .sales-search-select__search {
            margin-bottom: 0.5rem;
        }
        .sales-search-select__options {
            max-height: 220px;
            overflow-y: auto;
        }
        .sales-search-select__option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 0.25rem;
            display: block;
            padding: 0.45rem 0.55rem;
            text-align: left;
        }
        .sales-search-select__option:hover,
        .sales-search-select__option.active {
            background: #0d6efd;
            color: #fff;
        }
        .product-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6 !important;
            transition: all 0.3s ease;
        }
        .product-item:hover {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
        #quickCustomerModal .invalid-feedback,
        #quickHandoverModal .invalid-feedback,
        #quickProductModal .invalid-feedback {
            display: block;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Material OUT</h1>
                        <p class="text-muted small mb-0">Edit material OUT entry.</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/v1/sales/{{ $sale->invoice_id }}" enctype="multipart/form-data" class="needs-validation ajax-sales-form" novalidate id="salesEditForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-person"></i> Select Customer <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1">
                                    <select name="customer_id" id="customer_id" class="form-select searchable-select js-select2 @error('customer_id') is-invalid @enderror" data-placeholder="Select Customer" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected($sale->customer_id == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a customer!</div>
                                </div>
                                <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" title="Add Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-person-check"></i> Select Handover Person</label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1">
                                    <select name="handover_id" id="handover_id" class="form-select searchable-select js-select2 @error('handover_id') is-invalid @enderror" data-placeholder="Select Handover Person">
                                        <option value="">Select Handover Person</option>
                                        @foreach($handoverPersons as $person)
                                            <option value="{{ $person->id }}" @selected($sale->handover_id == $person->id)>{{ $person->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">@error('handover_id') {{ $message }} @enderror</div>
                                </div>
                                <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#quickHandoverModal" title="Add Handover Person">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-calendar"></i> OUT Date </label>
                            <input type="date" name="invoice_date" id="invoice_date" value="{{ $sale->invoice_date?->format('Y-m-d') }}" class="form-control @error('invoice_date') is-invalid @enderror" required>
                            <div class="invalid-feedback">Please enter OUT date!</div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                                <h5 class="fw-semibold mb-0"><i class="bi bi-box me-2"></i>Product Items <span class="badge bg-primary ms-2" id="productCount">1</span></h5>
                            </div>

                            <div id="productItemsContainer">
                                <div class="product-item border rounded p-3 mb-3" data-item-index="0">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-2 align-items-start">
                                                <div class="flex-grow-1">
                                                    <select name="product_id" id="product_id" class="form-select product-select js-select2 @error('product_id') is-invalid @enderror" data-placeholder="Select Product" required>
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            @php
                                                                $currentStock = optional($product->inventories->sortByDesc('id')->first())->current_stock ?? $product->quantity ?? 0;
                                                            @endphp
                                                            <option value="{{ $product->id }}" data-stock="{{ $currentStock }}" @selected($sale->product_id == $product->id)>{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback">Please select a product.</div>
                                                </div>
                                                <button type="button" class="btn btn-dark-blue quick-add-product-btn" data-bs-toggle="modal" data-bs-target="#quickProductModal" title="Add Product">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                                            <input type="number" min="0" name="quantity" id="quantity" value="{{ $sale->quantity }}" class="form-control quantity-input @error('quantity') is-invalid @enderror" placeholder="0" required>
                                            <div class="invalid-feedback">Please enter a valid quantity.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-chat-left-text"></i> Comment</label>
                            <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="3" placeholder="Add any comments...">{{ $sale->comment }}</textarea>
                            <div class="invalid-feedback">@error('comment') {{ $message }} @enderror</div>
                        </div>

                        <input type="hidden" name="price" id="price" value="{{ $sale->price ?? 0 }}">
                        <input type="hidden" name="gst" id="gst" value="{{ $sale->gst ?? 0 }}">
                        <input type="hidden" name="discount" id="discount" value="{{ $sale->discount ?? 0 }}">
                        <input type="hidden" name="total" id="total" value="{{ $sale->total ?? 0 }}">
                        <input type="hidden" name="status" id="status" value="{{ $sale->status ?? 'pending' }}">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('sales.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>

                <div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Customer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="quickCustomerForm" data-customer-store-url="{{ route('api.customers.store') }}" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="quick_customer_name" class="form-control" required>
                                        <div class="invalid-feedback" id="quick_customer_name-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" id="quick_customer_phone" class="form-control" maxlength="10" inputmode="numeric" required>
                                        <div class="invalid-feedback" id="quick_customer_phone-error"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-dark-blue" id="submitQuickCustomerBtn">
                                    <span class="spinner-border spinner-border-sm d-none" id="quickCustomerSpinner" aria-hidden="true"></span>
                                    <span>Add Customer</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="quickHandoverModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Handover Person</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="quickHandoverForm" data-handover-store-url="{{ route('api.handover-persons.store') }}" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="quick_handover_name" class="form-control" required>
                                        <div class="invalid-feedback" id="quick_handover_name-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" id="quick_handover_phone" class="form-control" maxlength="10" inputmode="numeric" required>
                                        <div class="invalid-feedback" id="quick_handover_phone-error"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-dark-blue" id="submitQuickHandoverBtn">
                                    <span class="spinner-border spinner-border-sm d-none" id="quickHandoverSpinner" aria-hidden="true"></span>
                                    <span>Add Handover Person</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="quickProductModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="quickProductForm" data-product-store-url="{{ route('api.products.store') }}" data-category-store-url="{{ route('api.products.categories.store') }}" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="quick_product_name" class="form-control" required>
                                        <div class="invalid-feedback" id="quick_product_name-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                        <select name="category_id" id="quick_product_category" class="form-select js-select2" data-placeholder="Select or Add Category" required>
                                            <option value="">Select or Add Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Type a category name and press Enter to create it.</div>
                                        <div class="invalid-feedback" id="quick_product_category-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" id="quick_product_quantity" class="form-control" min="0" placeholder="0" required>
                                        <div class="invalid-feedback" id="quick_product_quantity-error"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-dark-blue" id="submitQuickProductBtn">
                                    <span class="spinner-border spinner-border-sm d-none" id="quickProductSpinner" aria-hidden="true"></span>
                                    <span>Add Product</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ crm_asset('js/sales-create-select2.js') }}"></script>
@endpush
