@extends('layouts.app')

@section('page_title', 'Products - Edit')

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
                        <h1 class="h4 mb-1 fw-semibold">Edit Product</h1>
                        <p class="text-muted small mb-0">Update product details.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('products.view')
                        <a href="{{ route('products.show', $product->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/products/{{ $product->id }}" enctype="multipart/form-data"
                    class="needs-validation ajax-product-form" novalidate id="productEditForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box"></i> Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="Product Name" required>
                            <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-tag"></i> Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="category_id-error">@error('category_id') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bar-chart"></i> Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ old('quantity', $currentStock) }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="0" required>
                            <div class="invalid-feedback" id="quantity-error">@error('quantity') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box-seam"></i> Stock</label>
                            <select name="availability" id="availability" class="form-select @error('availability') is-invalid @enderror">
                                <option value="">Select Stock Status</option>
                                <option value="in_stock" @selected(old('availability', $product->availability) == 'in_stock')>In Stock</option>
                                <option value="out_of_stock" @selected(old('availability', $product->availability) == 'out_of_stock')>Out of Stock</option>
                            </select>
                            <div class="invalid-feedback" id="availability-error">@error('availability') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-hash"></i> Serial No</label>
                            <div class="input-group">
                                <input type="text" readonly name="serial_no" id="serial_no" value="{{ old('serial_no', $product->serial_no) }}"
                                    class="form-control @error('serial_no') is-invalid @enderror" placeholder="Scan barcode or type manually">
                                <button disabled type="button" class="btn btn-dark-blue" id="scanBarcodeBtn" title="Scan Barcode">
                                    <i class="bi bi-camera"></i> Scan
                                </button>
                            </div>
                            <div class="invalid-feedback" id="serial_no-error">@error('serial_no') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bullseye"></i> Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">Select Status</option>
                                <option value="active" @selected(old('status', $product->status) == 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $product->status) == 'inactive')>Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-pencil-square"></i> Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                rows="3" placeholder="Product description">{{ old('description', $product->description) }}</textarea>
                            <div class="invalid-feedback" id="description-error">@error('description') {{ $message }} @enderror</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Barcode Scanner Modal -->
    <div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="bi bi-camera me-2"></i>Scan Barcode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div id="scanner-container" style="width: 100%; max-width: 500px; margin: 0 auto;">
                            <video id="scanner-video" style="width: 100%; height: 300px; border: 2px solid #dee2e6; border-radius: 8px; background: #f8f9fa;"></video>
                        </div>
                        <div class="mt-3">
                            <p class="text-muted mb-2">Position the barcode within the camera view</p>
                            <div id="scanner-status" class="alert alert-info">
                                <i class="bi bi-camera-video me-2"></i>Initializing camera...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-dark-blue" id="manualEntryBtn">
                        <i class="bi bi-keyboard me-1"></i>Manual Entry
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="{{ crm_asset('js/product.js') }}"></script>
    <script>
        let scannerActive = false;

        // Add custom CSS for scanner button
        const style = document.createElement('style');
        style.textContent = `
            #scanBarcodeBtn {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                border-left: 0;
                transition: all 0.2s ease;
            }
            #scanBarcodeBtn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .input-group .form-control {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
            #addCategoryBtn {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                border-left: 0;
                transition: all 0.2s ease;
            }
            #addCategoryBtn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .input-group .form-select {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
            #scanner-video {
                object-fit: cover;
            }
            .modal-body .alert {
                margin-bottom: 0;
            }
        `;
        document.head.appendChild(style);

        // Barcode Scanner Functions
        function initBarcodeScanner() {
            const video = document.getElementById('scanner-video');
            const statusDiv = document.getElementById('scanner-status');
            
            // Update status
            statusDiv.innerHTML = '<i class="bi bi-camera-video me-2"></i>Starting camera...';
            statusDiv.className = 'alert alert-info';

            // Check if camera is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Camera not supported on this device';
                statusDiv.className = 'alert alert-warning';
                return;
            }

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: video,
                    constraints: {
                        width: 500,
                        height: 300,
                        facingMode: "environment" // Use back camera if available
                    }
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                frequency: 10,
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                },
                locate: true
            }, function(err) {
                if (err) {
                    console.error('QuaggaJS initialization error:', err);
                    let errorMessage = 'Camera access denied or not available';
                    
                    // Handle specific error types
                    if (err.name === 'NotAllowedError') {
                        errorMessage = 'Camera access denied. Please allow camera access and try again.';
                    } else if (err.name === 'NotFoundError') {
                        errorMessage = 'No camera found on this device.';
                    } else if (err.name === 'NotSupportedError') {
                        errorMessage = 'Camera not supported on this browser.';
                    }
                    
                    statusDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${errorMessage}`;
                    statusDiv.className = 'alert alert-warning';
                    return;
                }
                
                console.log("QuaggaJS initialization finished. Ready to start");
                Quagga.start();
                scannerActive = true;
                
                // Update status
                statusDiv.innerHTML = '<i class="bi bi-camera me-2"></i>Camera ready! Position barcode in view';
                statusDiv.className = 'alert alert-success';
            });

            // Handle successful barcode detection
            Quagga.onDetected(function(result) {
                if (scannerActive) {
                    const code = result.codeResult.code;
                    console.log('Barcode detected:', code);
                    
                    // Update the serial number field
                    document.getElementById('serial_no').value = code;
                    
                    // Show success message
                    statusDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i>Barcode detected: ${code}`;
                    statusDiv.className = 'alert alert-success';
                    
                    // Stop scanner and close modal after a short delay
                    setTimeout(() => {
                        stopBarcodeScanner();
                        $('#barcodeScannerModal').modal('hide');
                    }, 1500);
                }
            });
        }

        function stopBarcodeScanner() {
            if (scannerActive) {
                Quagga.stop();
                scannerActive = false;
                console.log('Barcode scanner stopped');
            }
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent default camera access prompts and handle errors gracefully
            window.addEventListener('error', function(e) {
                if (e.message && e.message.includes('camera')) {
                    e.preventDefault();
                    console.log('Camera error handled gracefully');
                }
            });

            // Scan button click
            document.getElementById('scanBarcodeBtn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Check if HTTPS or localhost (required for camera access)
                if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    alert('Camera access requires HTTPS connection. Please use manual entry.');
                    document.getElementById('serial_no').focus();
                    return;
                }
                
                $('#barcodeScannerModal').modal('show');
            });

            // Initialize Select2 with tags option for on-the-fly category creation
            $('#category_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                placeholder: 'Select Category',
                createTag: function (params) {
                    const term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                },
                templateResult: function (data) {
                    if (data.newTag) {
                        return $('<span>Add new category: <strong></strong></span>').find('strong').text(data.text).end();
                    }
                    return data.text;
                }
            });

            // Handle selection of a new tag (category)
            $('#category_id').on('select2:select', function (e) {
                const data = e.params.data;
                if (data.newTag) {
                    const categoryName = data.text;

                    const formData = new FormData();
                    formData.append('name', categoryName);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                    fetch('/api/v1/categories', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => Promise.reject(err));
                        }
                        return response.json();
                    })
                    .then(res => {
                        if (res.success) {
                            // Remove temporary option
                            $(`#category_id option[value="${categoryName}"]`).remove();
                            // Append real option
                            const newOption = new Option(res.data.name, res.data.id, true, true);
                            $('#category_id').append(newOption).trigger('change');
                        } else {
                            $('#category_id').val('').trigger('change');
                            showNotification(res.message || 'Failed to create category', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error creating category:', err);
                        $('#category_id').val('').trigger('change');
                        showNotification(err.message || 'Failed to create category', 'error');
                    });
                }
            });

            // Manual entry button
            document.getElementById('manualEntryBtn').addEventListener('click', function() {
                stopBarcodeScanner();
                $('#barcodeScannerModal').modal('hide');
                document.getElementById('serial_no').focus();
            });

            // Modal events
            $('#barcodeScannerModal').on('shown.bs.modal', function() {
                try {
                    initBarcodeScanner();
                } catch (error) {
                    console.error('Scanner initialization failed:', error);
                    const statusDiv = document.getElementById('scanner-status');
                    statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Scanner initialization failed. Please use manual entry.';
                    statusDiv.className = 'alert alert-warning';
                }
            });

            $('#barcodeScannerModal').on('hidden.bs.modal', function() {
                stopBarcodeScanner();
            });
        });

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const icon = document.getElementById(previewId.replace('Preview', 'Icon'));
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    icon.classList.add('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush
