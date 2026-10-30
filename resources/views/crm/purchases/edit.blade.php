@extends('layouts.app')

@section('page_title', 'Purchases - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden purchase-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Material IN</h1>
                        <p class="text-muted small mb-0">Edit material IN entry.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('purchases.view')
                            <a href="{{ route('purchases.show', $purchase->invoice_id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('purchases.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/v1/purchases/{{ $purchase->invoice_id }}" enctype="multipart/form-data"
                    class="needs-validation ajax-purchase-form" novalidate id="purchaseEditForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-person"></i> Select Vendor </label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1">
                                    <select name="customer_id" id="customer_id"
                                        class="form-select @error('customer_id') is-invalid @enderror" required>
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" @selected($purchase->customer_id == $vendor->id)>
                                                {{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a vendor!</div>
                                </div>
                                <button type="button" class="btn btn-dark-blue" id="addVendorBtn" data-bs-toggle="modal" data-bs-target="#addVendorModal" title="Add New Vendor">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-calendar"></i> IN Date </label>
                            <input type="date" name="invoice_date" id="invoice_date"
                                value="{{ $purchase->invoice_date?->format('Y-m-d') }}"
                                class="form-control @error('invoice_date') is-invalid @enderror" required>
                            <div class="invalid-feedback">Please enter invoice date!</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box"></i> Product Name </label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1">
                                    <select name="product_id" id="product_id"
                                        class="form-select @error('product_id') is-invalid @enderror" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" @selected($purchase->product_id == $product->id)>
                                                {{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a product.</div>
                                </div>
                                <button type="button" class="btn btn-dark-blue" id="addProductBtn" data-bs-toggle="modal" data-bs-target="#addProductModal" title="Add New Product">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bar-chart"></i> Qty </label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ $purchase->quantity }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="0" required>
                            <div class="invalid-feedback">Please enter a valid quantity.</div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-chat-left-text"></i> Comment</label>
                            <textarea name="comment" id="comment"
                                class="form-control @error('comment') is-invalid @enderror" rows="3"
                                placeholder="Add any comments...">{{ $purchase->comment }}</textarea>
                            <div class="invalid-feedback">@error('comment') {{ $message }} @enderror</div>
                        </div>

                        <!-- Hidden Fields -->
                        <input type="hidden" name="price" id="price" value="{{ $purchase->price ?? 0 }}">
                        <input type="hidden" name="gst" id="gst" value="{{ $purchase->gst ?? 0 }}">
                        <input type="hidden" name="discount" id="discount" value="{{ $purchase->discount ?? 0 }}">
                        <input type="hidden" name="total" id="total" value="{{ $purchase->total ?? 0 }}">
                        <input type="hidden" name="status" id="status" value="{{ $purchase->status ?? 'pending' }}">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>

                <!-- Add Vendor Modal -->
                <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addVendorModalLabel">Add New Vendor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="quickVendorForm" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Vendor Name <span class="text-danger">*</span></label>
                                        <input type="text" name="vendor_name" id="vendor_name" class="form-control" required>
                                        <div class="invalid-feedback" id="vendor_name-error"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="vendor_phone" id="vendor_phone" class="form-control" placeholder="10 digits" required>
                                        <div class="invalid-feedback" id="vendor_phone-error"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-dark-blue" id="submitVendorBtn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="vendorBtnSpinner"></span>
                                    <span id="vendorBtnText">Add Vendor</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Product Modal -->
            <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="quickProductForm" novalidate>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" name="product_name" id="product_name" class="form-control" required>
                                    <div class="invalid-feedback" id="product_name-error"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                    <select name="product_category" id="product_category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="product_category-error"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="product_quantity" id="product_quantity" class="form-control" min="0" placeholder="0" required>
                                    <div class="invalid-feedback" id="product_quantity-error"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-dark-blue" id="submitProductBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="productBtnSpinner"></span>
                                <span id="productBtnText">Add Product</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Vendor modal error styling */
        #addVendorModal .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        #addVendorModal .form-control.is-invalid {
            border-color: #dc3545;
        }
        /* Product Modal Error Styling */
        #addProductModal .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        #addProductModal .form-control.is-invalid,
        #addProductModal .form-select.is-invalid {
            border-color: #dc3545;
        }
    </style>
    <script>
        // Handle Add Vendor Form Submission
        document.getElementById('submitVendorBtn').addEventListener('click', function() {
            const form = document.getElementById('quickVendorForm');
            const vendorNameInput = document.getElementById('vendor_name');
            const vendorPhoneInput = document.getElementById('vendor_phone');
            const vendorNameError = document.getElementById('vendor_name-error');
            const vendorPhoneError = document.getElementById('vendor_phone-error');
            
            // Clear previous errors
            vendorNameInput.classList.remove('is-invalid');
            vendorPhoneInput.classList.remove('is-invalid');
            vendorNameError.textContent = '';
            vendorPhoneError.textContent = '';
            
            // Basic client-side validation
            let hasError = false;
            
            if (!vendorNameInput.value.trim()) {
                vendorNameInput.classList.add('is-invalid');
                vendorNameError.textContent = 'Please enter vendor name.';
                hasError = true;
            }
            
            if (!vendorPhoneInput.value.trim()) {
                vendorPhoneInput.classList.add('is-invalid');
                vendorPhoneError.textContent = 'Please enter phone number.';
                hasError = true;
            }
            
            if (hasError) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const vendorData = {
                name: vendorNameInput.value,
                phone: vendorPhoneInput.value,
            };

            const submitBtn = document.getElementById('submitVendorBtn');
            const btnSpinner = document.getElementById('vendorBtnSpinner');
            const btnText = document.getElementById('vendorBtnText');
            submitBtn.disabled = true;
            btnSpinner.classList.remove('d-none');

            $.ajax({
                url: '/api/vendors',
                type: 'POST',
                data: JSON.stringify(vendorData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Add new vendor to dropdown
                        const vendorSelect = document.getElementById('customer_id');
                        const newOption = document.createElement('option');
                        newOption.value = response.data.id;
                        newOption.textContent = response.data.name;
                        newOption.selected = true;
                        vendorSelect.appendChild(newOption);

                        // Reset form and close modal
                        form.reset();
                        vendorNameInput.classList.remove('is-invalid');
                        vendorPhoneInput.classList.remove('is-invalid');
                        vendorNameError.textContent = '';
                        vendorPhoneError.textContent = '';
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addVendorModal'));
                        modal.hide();

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', 'Vendor added successfully!', 'Success!');
                        } else {
                            alert('Vendor added successfully!');
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            if (field === 'name') {
                                vendorNameInput.classList.add('is-invalid');
                                vendorNameError.textContent = xhr.responseJSON.errors[field][0];
                            } else if (field === 'phone') {
                                vendorPhoneInput.classList.add('is-invalid');
                                vendorPhoneError.textContent = xhr.responseJSON.errors[field][0];
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

        // Clear validation on modal close
        document.getElementById('addVendorModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('quickVendorForm');
            const vendorNameInput = document.getElementById('vendor_name');
            const vendorPhoneInput = document.getElementById('vendor_phone');
            const vendorNameError = document.getElementById('vendor_name-error');
            const vendorPhoneError = document.getElementById('vendor_phone-error');
            
            form.reset();
            vendorNameInput.classList.remove('is-invalid');
            vendorPhoneInput.classList.remove('is-invalid');
            vendorNameError.textContent = '';
            vendorPhoneError.textContent = '';
        });

        // Handle Add Product Form Submission
        document.getElementById('submitProductBtn').addEventListener('click', function() {
            const form = document.getElementById('quickProductForm');
            const productNameInput = document.getElementById('product_name');
            const productCategoryInput = document.getElementById('product_category');
            const productQuantityInput = document.getElementById('product_quantity');
            const productNameError = document.getElementById('product_name-error');
            const productCategoryError = document.getElementById('product_category-error');
            const productQuantityError = document.getElementById('product_quantity-error');
            
            // Clear previous errors
            productNameInput.classList.remove('is-invalid');
            productCategoryInput.classList.remove('is-invalid');
            productQuantityInput.classList.remove('is-invalid');
            productNameError.textContent = '';
            productCategoryError.textContent = '';
            productQuantityError.textContent = '';
            
            // Basic client-side validation
            let hasError = false;
            
            if (!productNameInput.value.trim()) {
                productNameInput.classList.add('is-invalid');
                productNameError.textContent = 'Please enter product name.';
                hasError = true;
            }
            
            if (!productCategoryInput.value) {
                productCategoryInput.classList.add('is-invalid');
                productCategoryError.textContent = 'Please select a category.';
                hasError = true;
            }
            
            if (!productQuantityInput.value || productQuantityInput.value < 0) {
                productQuantityInput.classList.add('is-invalid');
                productQuantityError.textContent = 'Please enter a valid quantity.';
                hasError = true;
            }
            
            if (hasError) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const productData = {
                name: productNameInput.value,
                category_id: productCategoryInput.value,
                quantity: productQuantityInput.value,
            };

            const submitBtn = document.getElementById('submitProductBtn');
            const btnSpinner = document.getElementById('productBtnSpinner');
            submitBtn.disabled = true;
            btnSpinner.classList.remove('d-none');

            $.ajax({
                url: '/api/products',
                type: 'POST',
                data: JSON.stringify(productData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Add new product to product select
                        const productSelect = document.getElementById('product_id');
                        const newOption = document.createElement('option');
                        newOption.value = response.data.id;
                        newOption.textContent = response.data.name;
                        newOption.selected = true;
                        productSelect.appendChild(newOption);

                        // Reset form and close modal
                        form.reset();
                        productNameInput.classList.remove('is-invalid');
                        productCategoryInput.classList.remove('is-invalid');
                        productQuantityInput.classList.remove('is-invalid');
                        productNameError.textContent = '';
                        productCategoryError.textContent = '';
                        productQuantityError.textContent = '';
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                        modal.hide();

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', 'Product added successfully!', 'Success!');
                        } else {
                            alert('Product added successfully!');
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            if (field === 'name') {
                                productNameInput.classList.add('is-invalid');
                                productNameError.textContent = xhr.responseJSON.errors[field][0];
                            } else if (field === 'category_id') {
                                productCategoryInput.classList.add('is-invalid');
                                productCategoryError.textContent = xhr.responseJSON.errors[field][0];
                            } else if (field === 'quantity') {
                                productQuantityInput.classList.add('is-invalid');
                                productQuantityError.textContent = xhr.responseJSON.errors[field][0];
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

        // Clear validation on modal close
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('quickProductForm');
            const productNameInput = document.getElementById('product_name');
            const productCategoryInput = document.getElementById('product_category');
            const productQuantityInput = document.getElementById('product_quantity');
            const productNameError = document.getElementById('product_name-error');
            const productCategoryError = document.getElementById('product_category-error');
            const productQuantityError = document.getElementById('product_quantity-error');
            
            form.reset();
            productNameInput.classList.remove('is-invalid');
            productCategoryInput.classList.remove('is-invalid');
            productQuantityInput.classList.remove('is-invalid');
            productNameError.textContent = '';
            productCategoryError.textContent = '';
            productQuantityError.textContent = '';
        });

        document.getElementById('purchaseEditForm').addEventListener('submit', function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
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
                url: '/api/v1/purchases/{{ $purchase->invoice_id }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Material IN updated successfully.', 'Success!', '/purchases');
                        } else {
                            alert(response.message || 'Material IN updated successfully.');
                            window.location.href = '/purchases';
                        }
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            const input = document.getElementById(field);
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