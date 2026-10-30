@extends('layouts.app')

@section('page_title', 'Products - Create')

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
                        <h1 class="h4 mb-1 fw-semibold">Add Product</h1>
                        <p class="text-muted small mb-0">Create a new product entry.</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/products" enctype="multipart/form-data"
                    class="needs-validation ajax-product-form" novalidate id="productCreateForm">
                    @csrf

                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box"></i> Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="Product Name" required>
                            <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-tag"></i> Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="category_id-error">@error('category_id') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bar-chart"></i> Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ old('quantity') }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="0" required>
                            <div class="invalid-feedback" id="quantity-error">@error('quantity') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box-seam"></i> Stock</label>
                            <select name="availability" id="availability" class="form-select @error('availability') is-invalid @enderror">
                                <option value="">Select Stock Status</option>
                                <option value="in_stock" @selected(old('availability', 'in_stock') == 'in_stock')>In Stock</option>
                                <option value="out_of_stock" @selected(old('availability') == 'out_of_stock')>Out of Stock</option>
                            </select>
                            <div class="invalid-feedback" id="availability-error">@error('availability') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-hash"></i> Serial No</label>
                            <div class="input-group">
                                <input type="text" name="serial_no" id="serial_no" value="{{ old('serial_no') }}"
                                    class="form-control @error('serial_no') is-invalid @enderror" placeholder="Scan barcode or type manually">
                                <button type="button" class="btn btn-dark-blue" id="scanBarcodeBtn" title="Scan Barcode">
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
                                <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                                <option value="inactive" @selected(old('status') == 'inactive')>Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-pencil-square"></i> Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                rows="3" placeholder="Product description">{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error">@error('description') {{ $message }} @enderror</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Barcode Scanner Modal -->
    <div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-height: 90vh;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="bi bi-camera me-2"></i>Scan Barcode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="w-100">
                        <div id="scanner-container" style="width: 100%; height: 400px; border: 2px solid #dee2e6; border-radius: 8px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                            <div id="scanner-placeholder" class="text-muted">Waiting for camera...</div>
                        </div>
                        <div class="mt-3">
                            <p class="text-muted mb-2">Position the barcode within the camera view</p>
                            <div id="scanner-status" class="alert alert-info m-0">
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
            #scanner-container {
                position: relative;
                overflow: hidden;
                background: #f8f9fa;
                width: 100%;
            }
            #scanner-container video,
            #scanner-container canvas,
            #scanner-container > div {
                position: absolute !important;
                top: 0;
                left: 0;
                width: 100% !important;
                height: 100% !important;
                object-fit: cover;
            }
            #scanner-container #scanner-placeholder {
                position: relative !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 1;
            }
            .modal-body .alert {
                margin-bottom: 0;
            }
            @media (max-width: 576px) {
                #barcodeScannerModal .modal-dialog {
                    margin: 0;
                    width: 100%;
                    max-width: 100%;
                }
                #barcodeScannerModal .modal-content {
                    height: 100vh;
                    border-radius: 0;
                }
                #barcodeScannerModal .modal-body {
                    display: flex;
                    flex-direction: column;
                    flex-grow: 1;
                }
                #scanner-container {
                    flex-grow: 1;
                    height: auto !important;
                    min-height: 400px;
                }
            }
        `;
        document.head.appendChild(style);

        // Barcode Scanner Functions
        let barcodeDetectedHandler = null;

        function initBarcodeScanner() {
            const scannerContainer = document.getElementById('scanner-container');
            const statusDiv = document.getElementById('scanner-status');

            if (!scannerContainer || !statusDiv) {
                return;
            }

            // Clean up any previous Quagga instance if it exists
            if (window.Quagga && typeof Quagga.stop === 'function') {
                try {
                    Quagga.stop();
                } catch (ignore) {}
            }

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
                    name: 'Live',
                    type: 'LiveStream',
                    target: scannerContainer,
                    constraints: {
                        width: 500,
                        height: 300,
                        facingMode: 'environment',
                    },
                },
                locator: {
                    patchSize: 'medium',
                    halfSample: true,
                },
                numOfWorkers: navigator.hardwareConcurrency ? Math.max(1, navigator.hardwareConcurrency - 1) : 2,
                frequency: 10,
                decoder: {
                    readers: [
                        'code_128_reader',
                        'ean_reader',
                        'ean_8_reader',
                        'code_39_reader',
                        'code_39_vin_reader',
                        'codabar_reader',
                        'upc_reader',
                        'upc_e_reader',
                        'i2of5_reader',
                    ],
                },
                locate: true,
            }, function(err) {
                if (err) {
                    console.error('QuaggaJS initialization error:', err);
                    let errorMessage = 'Camera access denied or not available';

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

                console.log('QuaggaJS initialization finished. Ready to start');
                Quagga.start();
                scannerActive = true;
                const placeholder = document.getElementById('scanner-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                statusDiv.innerHTML = '<i class="bi bi-camera me-2"></i>Camera ready! Position barcode in view';
                statusDiv.className = 'alert alert-success';
            });

            if (barcodeDetectedHandler) {
                Quagga.offDetected(barcodeDetectedHandler);
            }

            barcodeDetectedHandler = function(result) {
                if (!scannerActive) {
                    return;
                }

                const code = result.codeResult?.code;
                if (!code) {
                    return;
                }

                console.log('Barcode detected:', code);
                const serialInput = document.getElementById('serial_no');
                if (serialInput) {
                    serialInput.value = code;
                    serialInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                statusDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i>Barcode detected: ${code}`;
                statusDiv.className = 'alert alert-success';

                setTimeout(() => {
                    stopBarcodeScanner();
                    $('#barcodeScannerModal').modal('hide');
                }, 1500);
            };

            Quagga.onDetected(barcodeDetectedHandler);
        }

        function stopBarcodeScanner() {
            if (scannerActive) {
                Quagga.stop();
                scannerActive = false;
                console.log('Barcode scanner stopped');
            }
            const placeholder = document.getElementById('scanner-placeholder');
            if (placeholder) {
                placeholder.style.display = 'flex';
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
