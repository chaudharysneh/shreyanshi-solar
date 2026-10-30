@extends('layouts.app')

@section('page_title', 'Sales - Create')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Material OUT</h1>
                        <p class="text-muted small mb-0">Create a new material OUT entry.</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/v1/sales" enctype="multipart/form-data" class="needs-validation ajax-sales-form" novalidate id="salesCreateForm">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-person"></i> Select Customer <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1">
                                    <select name="customer_id" id="customer_id" class="form-select searchable-select js-select2 @error('customer_id') is-invalid @enderror" data-placeholder="Select Customer" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
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
                                            <option value="{{ $person->id }}" @selected(old('handover_id') == $person->id)>{{ $person->name }}</option>
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
                            <input type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="form-control @error('invoice_date') is-invalid @enderror" required>
                            <div class="invalid-feedback">Please enter OUT date!</div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                                <h5 class="fw-semibold mb-0"><i class="bi bi-box me-2"></i>Product Items <span class="badge bg-primary ms-2" id="productCount">1</span></h5>
                                <button type="button" class="btn btn-success btn-sm mt-2 mt-sm-0" id="addProductBtn">
                                    <i class="bi bi-plus-lg me-1"></i>Add Product
                                </button>
                            </div>
                            
                            <!-- Product Items Container -->
                            <div id="productItemsContainer">
                                <!-- Initial Product Item -->
                                <div class="product-item border rounded p-3 mb-3" data-item-index="0">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-2 align-items-start">
                                                <div class="flex-grow-1">
                                                    <select name="products[0][product_id]" class="form-select product-select js-select2" data-placeholder="Select Product" required>
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            @php
                                                                $currentStock = optional($product->inventories->sortByDesc('id')->first())->current_stock ?? $product->quantity ?? 0;
                                                            @endphp
                                                            <option value="{{ $product->id }}" data-stock="{{ $currentStock }}">{{ $product->name }}</option>
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
                                            <input type="number" min="0" name="products[0][quantity]" class="form-control quantity-input" placeholder="0" required>
                                            <div class="invalid-feedback">Please enter a valid quantity.</div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-product-btn" style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-chat-left-text"></i> Comment</label>
                            <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="3" placeholder="Add any comments...">{{ old('comment') }}</textarea>
                            <div class="invalid-feedback">@error('comment') {{ $message }} @enderror</div>
                        </div>

                        <!-- Hidden Fields (for backward compatibility) -->
                        <input type="hidden" name="price" id="price" value="0">
                        <input type="hidden" name="gst" id="gst" value="0">
                        <input type="hidden" name="discount" id="discount" value="0">
                        <input type="hidden" name="total" id="total" value="0">
                        <input type="hidden" name="status" id="status" value="pending">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('sales.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
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
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.6;
            padding-left: 0;
        }
        .select2-container--bootstrap-5 .select2-dropdown .select2-search .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.45rem 0.65rem;
            outline: none;
        }
        .select2-container--bootstrap-5 .select2-dropdown .select2-search--dropdown {
            display: block !important;
            padding: 0.5rem;
        }
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .input-group .select2-container {
            flex: 1 1 auto;
            width: 1% !important;
            min-width: 0;
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
        .product-item .remove-product-btn {
            transition: all 0.2s ease;
        }
        .product-item .remove-product-btn:hover {
            transform: scale(1.1);
        }
        #addProductBtn {
            transition: all 0.2s ease;
        }
        #addProductBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        #quickCustomerModal .invalid-feedback,
        #quickHandoverModal .invalid-feedback,
        #quickProductModal .invalid-feedback {
            display: block;
        }
        .sales-search-select {
            position: relative;
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
        }
        .sales-search-select__native {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        .sales-search-select__toggle {
            width: 100%;
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            background: #fff;
            color: #212529;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.375rem 0.75rem;
            text-align: left;
        }
        .sales-search-select__toggle:focus,
        .sales-search-select.open .sales-search-select__toggle {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: 0;
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
            color: #212529;
            display: block;
            padding: 0.45rem 0.55rem;
            text-align: left;
        }
        .sales-search-select__option:hover,
        .sales-search-select__option.active {
            background: #0d6efd;
            color: #fff;
        }
    </style>
    <script>
        let productItemIndex = 0;
        let quickProductTargetSelect = null;
        let salesCreateFormBooted = false;

        const salesQuickConfig = {
            customerStoreUrl: @json(route('api.customers.store')),
            handoverStoreUrl: @json(route('api.handover-persons.store')),
            productStoreUrl: @json(route('api.products.store')),
            categoryIndexUrl: @json(route('api.products.categories.index')),
            categoryStoreUrl: @json(route('api.products.categories.store')),
        };

        function ensureSelect2Loaded(callback) {
            if (window.jQuery && $.fn.select2) {
                callback();
                return;
            }

            if (document.getElementById('salesSelect2Fallback')) {
                setTimeout(function () {
                    if (window.jQuery && $.fn.select2) {
                        callback();
                    } else {
                        initSearchableSelectFallback();
                    }
                }, 800);
                return;
            }

            const script = document.createElement('script');
            script.id = 'salesSelect2Fallback';
            script.src = "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js";
            script.onload = callback;
            script.onerror = function () {
                initSearchableSelectFallback();
            };
            document.body.appendChild(script);

            setTimeout(function () {
                if (!window.jQuery || !$.fn.select2) {
                    initSearchableSelectFallback();
                }
            }, 1200);
        }

        function initSalesSelect2(scope) {
            if (!window.jQuery || !$.fn.select2) {
                initSearchableSelectFallback(scope);
                return;
            }

            const $scope = scope ? $(scope) : $(document);
            $scope.find('select.js-select2, select.searchable-select, select.product-select').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    return;
                }
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: this.dataset.placeholder || this.querySelector('option[value=""]')?.textContent || 'Select',
                    allowClear: false,
                    minimumResultsForSearch: 0,
                });
            });
        }

        function initSearchableSelectFallback(scope) {
            const root = scope || document;
            const selects = root.querySelectorAll
                ? root.querySelectorAll('select.js-select2, select.searchable-select, select.product-select')
                : [];

            selects.forEach(function (select) {
                if (select.dataset.searchFallback === '1' || select.classList.contains('select2-hidden-accessible')) {
                    return;
                }

                select.dataset.searchFallback = '1';
                select.classList.add('sales-search-select__native');

                const wrapper = document.createElement('div');
                wrapper.className = 'sales-search-select';
                wrapper.innerHTML = `
                    <button type="button" class="sales-search-select__toggle">
                        <span class="sales-search-select__label"></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="sales-search-select__dropdown">
                        <input type="text" class="form-control form-control-sm sales-search-select__search" placeholder="Search...">
                        <div class="sales-search-select__options"></div>
                    </div>
                `;

                select.insertAdjacentElement('afterend', wrapper);

                const label = wrapper.querySelector('.sales-search-select__label');
                const toggle = wrapper.querySelector('.sales-search-select__toggle');
                const search = wrapper.querySelector('.sales-search-select__search');
                const optionsContainer = wrapper.querySelector('.sales-search-select__options');

                const updateLabel = function () {
                    const selectedOption = select.options[select.selectedIndex];
                    label.textContent = selectedOption?.textContent || select.dataset.placeholder || 'Select';
                };

                const renderOptions = function (filter = '') {
                    const normalizedFilter = filter.trim().toLowerCase();
                    optionsContainer.innerHTML = '';

                    Array.from(select.options).forEach(function (option) {
                        if (option.disabled) {
                            return;
                        }

                        const text = option.textContent || '';
                        if (normalizedFilter && !text.toLowerCase().includes(normalizedFilter)) {
                            return;
                        }

                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'sales-search-select__option' + (option.selected ? ' active' : '');
                        item.textContent = text;
                        item.dataset.value = option.value;
                        item.addEventListener('click', function () {
                            select.value = option.value;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            wrapper.classList.remove('open');
                            search.value = '';
                            updateLabel();
                            renderOptions();
                        });
                        optionsContainer.appendChild(item);
                    });

                    if (!optionsContainer.children.length) {
                        optionsContainer.innerHTML = '<div class="text-muted small px-2 py-1">No results found</div>';
                    }
                };

                toggle.addEventListener('click', function () {
                    document.querySelectorAll('.sales-search-select.open').forEach(function (openWrapper) {
                        if (openWrapper !== wrapper) {
                            openWrapper.classList.remove('open');
                        }
                    });
                    wrapper.classList.toggle('open');
                    renderOptions();
                    if (wrapper.classList.contains('open')) {
                        setTimeout(function () {
                            search.focus();
                        }, 0);
                    }
                });

                search.addEventListener('input', function () {
                    renderOptions(search.value);
                });

                select.addEventListener('change', function () {
                    updateLabel();
                    renderOptions(search.value);
                });

                updateLabel();
                renderOptions();
            });
        }

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.sales-search-select')) {
                document.querySelectorAll('.sales-search-select.open').forEach(function (wrapper) {
                    wrapper.classList.remove('open');
                });
            }
        });

        function retryInitSalesSelect2(attempts = 8) {
            initSalesSelect2();
            initCategorySelect2();
            if (attempts > 0 && (!window.jQuery || !$.fn.select2 || !$('.searchable-select').first().hasClass('select2-hidden-accessible'))) {
                setTimeout(function () {
                    retryInitSalesSelect2(attempts - 1);
                }, 250);
            } else if (!window.jQuery || !$.fn.select2) {
                initSearchableSelectFallback();
            }
        }

        function initCategorySelect2() {
            if (!window.jQuery || !$.fn.select2) {
                return;
            }

            const $category = $('#quick_product_category');
            if ($category.hasClass('select2-hidden-accessible')) {
                return;
            }
            $category.select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                dropdownParent: $('#quickProductModal'),
                placeholder: 'Select or Add Category',
                createTag: function (params) {
                    const term = $.trim(params.term);
                    if (!term) {
                        return null;
                    }
                    return { id: term, text: term, newTag: true };
                },
                templateResult: function (data) {
                    if (data.newTag) {
                        return $('<span>Create category: <strong></strong></span>').find('strong').text(data.text).end();
                    }
                    return data.text;
                },
            });
        }

        function showFieldError(input, message) {
            if (!input) {
                return;
            }
            input.classList.add('is-invalid');
            const error = document.getElementById(input.id + '-error');
            if (error) {
                error.textContent = message || 'Invalid value';
            }
        }

        function clearQuickFormErrors(form) {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }

        function csrfHeaders(json = true) {
            const headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            };
            if (json) {
                headers['Content-Type'] = 'application/json';
            }
            return headers;
        }

        function parseJsonResponse(response) {
            return response.text().then(function (text) {
                let payload = {};
                try {
                    payload = text ? JSON.parse(text) : {};
                } catch (error) {
                    payload = { message: text || 'Invalid server response.' };
                }

                if (!response.ok) {
                    throw payload;
                }

                return payload;
            });
        }

        function closeModalById(modalId) {
            const modal = document.getElementById(modalId);
            const closeBtn = modal?.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
                return;
            }
            modal?.classList.remove('show');
            modal?.setAttribute('aria-hidden', 'true');
            modal?.removeAttribute('aria-modal');
            modal?.style.removeProperty('display');
            document.body.classList.remove('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        }

        // Add Product Item Function
        function addProductItem() {
            productItemIndex++;
            const productItemsContainer = document.getElementById('productItemsContainer');
            
            const newProductItem = document.createElement('div');
            newProductItem.className = 'product-item border rounded p-3 mb-3';
            newProductItem.setAttribute('data-item-index', productItemIndex);
            
            newProductItem.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="products[${productItemIndex}][product_id]" class="form-select product-select js-select2" data-placeholder="Select Product" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    @php
                                        $currentStock = optional($product->inventories->sortByDesc('id')->first())->current_stock ?? $product->quantity ?? 0;
                                    @endphp
                                    <option value="{{ $product->id }}" data-stock="{{ $currentStock }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-dark-blue quick-add-product-btn" data-bs-toggle="modal" data-bs-target="#quickProductModal" title="Add Product">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please select a product.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                        <input type="number" min="0" name="products[${productItemIndex}][quantity]" class="form-control quantity-input" placeholder="0" required>
                        <div class="invalid-feedback">Please enter a valid quantity.</div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-product-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            productItemsContainer.appendChild(newProductItem);
            initSalesSelect2(newProductItem);
            updateRemoveButtons();
        }

        // Remove Product Item Function
        function removeProductItem(button) {
            const productItem = button.closest('.product-item');
            productItem.remove();
            updateRemoveButtons();
        }

        // Update Remove Buttons Visibility and Product Count
        function updateRemoveButtons() {
            const productItems = document.querySelectorAll('.product-item');
            const removeButtons = document.querySelectorAll('.remove-product-btn');
            const productCount = document.getElementById('productCount');
            
            // Update product count badge
            if (productCount) {
                productCount.textContent = productItems.length;
            }
            
            removeButtons.forEach(button => {
                if (productItems.length > 1) {
                    button.style.display = 'block';
                } else {
                    button.style.display = 'none';
                }
            });
        }

        function addOptionToSelect(select, id, text, selected) {
            if (!select || !id) {
                return;
            }
            let option = select.querySelector('option[value="' + id + '"]');
            if (!option) {
                option = new Option(text, id, selected, selected);
                select.appendChild(option);
            }
            if (selected) {
                option.selected = true;
                if ($(select).hasClass('select2-hidden-accessible')) {
                    $(select).trigger('change');
                }
            }
        }

        function resolveQuickCategory() {
            const categorySelect = document.getElementById('quick_product_category');
            const selectedValue = categorySelect.value;
            const selectedText = categorySelect.options[categorySelect.selectedIndex]?.textContent?.trim() || selectedValue;

            if (/^\d+$/.test(String(selectedValue))) {
                return Promise.resolve(selectedValue);
            }

            if (!selectedText) {
                return Promise.reject({ errors: { category_id: ['Please select or add category.'] } });
            }

            const findExistingCategory = function () {
                const url = salesQuickConfig.categoryIndexUrl + '?search=' + encodeURIComponent(selectedText) + '&per_page=20';
                return fetch(url, {
                    headers: csrfHeaders(false),
                }).then(parseJsonResponse).then(function (payload) {
                    const categories = payload?.data?.data || payload?.data || [];
                    const match = Array.isArray(categories)
                        ? categories.find(category => String(category.name || '').toLowerCase() === selectedText.toLowerCase())
                        : null;

                    if (!match) {
                        throw { errors: { category_id: ['Category already exists. Please select it from the dropdown.'] } };
                    }

                    addOptionToSelect(categorySelect, match.id, match.name, true);
                    return match.id;
                });
            };

            return fetch(salesQuickConfig.categoryStoreUrl, {
                method: 'POST',
                headers: csrfHeaders(true),
                body: JSON.stringify({ name: selectedText }),
            }).then(parseJsonResponse).then(payload => {
                const category = payload.data;
                addOptionToSelect(categorySelect, category.id, category.name, true);
                return category.id;
            }).catch(error => {
                if (error?.errors?.name) {
                    return findExistingCategory();
                }

                error._categoryError = true;
                throw error;
            }));
        }

        function submitQuickCustomer() {
            const form = document.getElementById('quickCustomerForm');
            const submitBtn = document.getElementById('submitQuickCustomerBtn');
            const spinner = document.getElementById('quickCustomerSpinner');
            clearQuickFormErrors(form);

            const payload = {
                name: document.getElementById('quick_customer_name').value.trim(),
                phone: document.getElementById('quick_customer_phone').value.trim(),
                address: '',
            };

            let hasError = false;
            if (!payload.name) {
                showFieldError(document.getElementById('quick_customer_name'), 'Please enter customer name.');
                hasError = true;
            }
            if (!/^[0-9]{10}$/.test(payload.phone)) {
                showFieldError(document.getElementById('quick_customer_phone'), 'Please enter 10 digit phone number.');
                hasError = true;
            }
            if (hasError) {
                return;
            }

            submitBtn.disabled = true;
            spinner.classList.remove('d-none');

            fetch(salesQuickConfig.customerStoreUrl, {
                method: 'POST',
                headers: csrfHeaders(true),
                body: JSON.stringify(payload),
            }).then(parseJsonResponse).then(data => {
                const customer = data.data;
                addOptionToSelect(document.getElementById('customer_id'), customer.id, customer.name, true);
                form.reset();
                closeModalById('quickCustomerModal');
            }).catch(error => {
                const errors = error.errors || {};
                if (errors.name) showFieldError(document.getElementById('quick_customer_name'), errors.name[0]);
                if (errors.phone) showFieldError(document.getElementById('quick_customer_phone'), errors.phone[0]);
                if (!Object.keys(errors).length) {
                    window.showAlert ? window.showAlert('error', error.message || 'Unable to add customer.') : alert(error.message || 'Unable to add customer.');
                }
            }).finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        }

        function submitQuickHandover() {
            const form = document.getElementById('quickHandoverForm');
            const submitBtn = document.getElementById('submitQuickHandoverBtn');
            const spinner = document.getElementById('quickHandoverSpinner');
            clearQuickFormErrors(form);

            const payload = {
                name: document.getElementById('quick_handover_name').value.trim(),
                phone: document.getElementById('quick_handover_phone').value.trim(),
            };

            let hasError = false;
            if (payload.name.length < 3) {
                showFieldError(document.getElementById('quick_handover_name'), 'Name must be at least 3 characters.');
                hasError = true;
            }
            if (!/^[0-9]{10}$/.test(payload.phone)) {
                showFieldError(document.getElementById('quick_handover_phone'), 'Please enter 10 digit phone number.');
                hasError = true;
            }
            if (hasError) {
                return;
            }

            submitBtn.disabled = true;
            spinner.classList.remove('d-none');

            fetch(salesQuickConfig.handoverStoreUrl, {
                method: 'POST',
                headers: csrfHeaders(true),
                body: JSON.stringify(payload),
            }).then(parseJsonResponse).then(data => {
                const person = data.data;
                addOptionToSelect(document.getElementById('handover_id'), person.id, person.name, true);
                form.reset();
                closeModalById('quickHandoverModal');
            }).catch(error => {
                const errors = error.errors || {};
                if (errors.name) showFieldError(document.getElementById('quick_handover_name'), errors.name[0]);
                if (errors.phone) showFieldError(document.getElementById('quick_handover_phone'), errors.phone[0]);
                if (!Object.keys(errors).length) {
                    window.showAlert ? window.showAlert('error', error.message || 'Unable to add handover person.') : alert(error.message || 'Unable to add handover person.');
                }
            }).finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        }

        function submitQuickProduct() {
            const form = document.getElementById('quickProductForm');
            const submitBtn = document.getElementById('submitQuickProductBtn');
            const spinner = document.getElementById('quickProductSpinner');
            clearQuickFormErrors(form);

            const nameInput = document.getElementById('quick_product_name');
            const quantityInput = document.getElementById('quick_product_quantity');
            let hasError = false;
            if (!nameInput.value.trim()) {
                showFieldError(nameInput, 'Please enter product name.');
                hasError = true;
            }
            if (parseInt(quantityInput.value || 0, 10) < 0 || quantityInput.value === '') {
                showFieldError(quantityInput, 'Please enter valid quantity.');
                hasError = true;
            }
            if (!document.getElementById('quick_product_category').value) {
                showFieldError(document.getElementById('quick_product_category'), 'Please select or add category.');
                hasError = true;
            }
            if (hasError) {
                return;
            }

            submitBtn.disabled = true;
            spinner.classList.remove('d-none');

            resolveQuickCategory().then(categoryId => {
                return fetch(salesQuickConfig.productStoreUrl, {
                    method: 'POST',
                    headers: csrfHeaders(true),
                    body: JSON.stringify({
                        name: nameInput.value.trim(),
                        category_id: categoryId,
                        quantity: quantityInput.value,
                        status: 'active',
                        availability: parseInt(quantityInput.value || 0, 10) > 0 ? 'in_stock' : 'out_of_stock',
                    }),
                });
            }).then(parseJsonResponse).then(data => {
                const product = data.data;
                if (!product?.id) {
                    throw { message: 'Product was saved, but product data was not returned.' };
                }
                document.querySelectorAll('.product-select').forEach(select => {
                    addOptionToSelect(select, product.id, product.name, select === quickProductTargetSelect);
                    const option = select.querySelector('option[value="' + product.id + '"]');
                    if (option) {
                        option.dataset.stock = product.current_stock ?? product.quantity ?? 0;
                    }
                });
                if (quickProductTargetSelect) {
                    quickProductTargetSelect.value = product.id;
                    $(quickProductTargetSelect).trigger('change');
                }
                form.reset();
                if (window.jQuery && $.fn.select2) {
                    $('#quick_product_category').val('').trigger('change');
                } else {
                    document.getElementById('quick_product_category').value = '';
                }
                closeModalById('quickProductModal');
            }).catch(error => {
                const errors = error.errors || {};
                if (errors.name && error._categoryError) {
                    showFieldError(document.getElementById('quick_product_category'), errors.name[0]);
                } else if (errors.name) {
                    showFieldError(document.getElementById('quick_product_name'), errors.name[0]);
                }
                if (errors.category_id) showFieldError(document.getElementById('quick_product_category'), errors.category_id[0]);
                if (errors.quantity) showFieldError(document.getElementById('quick_product_quantity'), errors.quantity[0]);
                if (!Object.keys(errors).length) {
                    window.showAlert ? window.showAlert('error', error.message || 'Unable to add product.') : alert(error.message || 'Unable to add product.');
                }
            }).finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        }

        function bootSalesCreateForm() {
            if (salesCreateFormBooted) {
                return;
            }
            salesCreateFormBooted = true;

            ensureSelect2Loaded(function () {
                retryInitSalesSelect2();
            });
            document.getElementById('submitQuickCustomerBtn')?.addEventListener('click', submitQuickCustomer);
            document.getElementById('submitQuickHandoverBtn')?.addEventListener('click', submitQuickHandover);
            document.getElementById('submitQuickProductBtn')?.addEventListener('click', submitQuickProduct);

            document.addEventListener('click', function (e) {
                const quickAddBtn = e.target.closest('.quick-add-product-btn');
                if (quickAddBtn) {
                    quickProductTargetSelect = quickAddBtn.closest('.product-item')?.querySelector('.product-select') || document.querySelector('.product-select');
                }
            });

            // Add Product Button
            document.getElementById('addProductBtn').addEventListener('click', addProductItem);
            
            // Remove Product Button (Event Delegation)
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-product-btn')) {
                    removeProductItem(e.target.closest('.remove-product-btn'));
                }
            });
            
            // Initialize remove buttons visibility
            updateRemoveButtons();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootSalesCreateForm);
        } else {
            bootSalesCreateForm();
        }

        function validateStockQuantities(showErrors = true) {
            let valid = true;
            const requestedByProduct = {};
            const stockByProduct = {};

            document.querySelectorAll('.quantity-input.is-invalid').forEach(function (input) {
                input.classList.remove('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Please enter a valid quantity.';
                }
            });

            document.querySelectorAll('.product-item').forEach(function (item) {
                const select = item.querySelector('.product-select');
                const qtyInput = item.querySelector('.quantity-input');
                if (!select || !qtyInput || !select.value) {
                    return;
                }

                const selectedOption = select.options[select.selectedIndex];
                const stock = parseInt(selectedOption?.dataset?.stock || 0, 10);
                const requested = parseInt(qtyInput.value || 0, 10);
                qtyInput.setAttribute('max', stock);
                requestedByProduct[select.value] = (requestedByProduct[select.value] || 0) + requested;
                stockByProduct[select.value] = stock;

                if (requested > stock) {
                    valid = false;
                    if (showErrors) {
                        qtyInput.classList.add('is-invalid');
                        const feedback = qtyInput.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = `Available quantity is ${stock}.`;
                            feedback.style.display = 'block';
                        }
                    }
                }
            });

            Object.keys(requestedByProduct).forEach(function (productId) {
                if (requestedByProduct[productId] <= stockByProduct[productId]) {
                    return;
                }

                valid = false;
                if (!showErrors) {
                    return;
                }

                document.querySelectorAll('.product-item').forEach(function (item) {
                    const select = item.querySelector('.product-select');
                    const qtyInput = item.querySelector('.quantity-input');
                    if (select?.value !== productId || !qtyInput) {
                        return;
                    }
                    qtyInput.classList.add('is-invalid');
                    const feedback = qtyInput.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = `Total requested for this product is ${requestedByProduct[productId]}, available quantity is ${stockByProduct[productId]}.`;
                        feedback.style.display = 'block';
                    }
                });
            });

            return valid;
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches('.product-select')) {
                const item = e.target.closest('.product-item');
                const qtyInput = item?.querySelector('.quantity-input');
                const stock = parseInt(e.target.options[e.target.selectedIndex]?.dataset?.stock || 0, 10);
                if (qtyInput) {
                    qtyInput.setAttribute('max', stock);
                    validateStockQuantities(false);
                }
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.matches('.quantity-input')) {
                validateStockQuantities(true);
            }
        });

        // Form Submission
        document.getElementById('salesCreateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            if (!validateStockQuantities(true)) {
                this.classList.add('was-validated');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const formData = new FormData(this);

            const submitBtn = document.getElementById('submitBtn');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnText = document.getElementById('btnText');
            submitBtn.disabled = true;
            btnSpinner.classList.remove('d-none');

            $.ajax({
                url: '/api/v1/sales',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Material OUT created successfully.', 'Success!', '/sales');
                        } else {
                            alert(response.message || 'Material OUT created successfully.');
                            window.location.href = '/sales';
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = xhr.responseJSON.errors[field][0];
                                    feedback.style.display = 'block';
                                }
                            }
                        });
                    } else {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', xhr.responseJSON?.message || 'Something went wrong.');
                        } else {
                            alert(xhr.responseJSON?.message || 'Something went wrong.');
                        }
                    }
                    submitBtn.disabled = false;
                    btnSpinner.classList.add('d-none');
                },
            });
        });
    </script>
@endpush
