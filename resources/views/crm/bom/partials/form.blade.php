@php
    $dummyBomImageUrl = url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'images/template/faveicone.png');
    $bomPreviewImageUrl = ($product?->image)
        ? route('bom-products.image', $product) . '?v=' . (optional($product?->updated_at)->timestamp ?? time())
        : $dummyBomImageUrl;
@endphp

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bom-form-card">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h4 mb-1 fw-semibold">{{ $title }}</h1>
                <p class="text-muted small mb-0">{{ $subtitle }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                @if($product)
                    <a href="{{ route('bom-products.show', $product) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                @endif
                <a href="{{ route('bom-products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="needs-validation ajax-bom-form" novalidate id="bomProductForm">
            @csrf
            @if($method)
                @method($method)
            @endif

            <div class="row g-3">
                <!-- Row 1: Name | Make -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $product?->product_name) }}" class="form-control" placeholder="BOM name" required>
                    <div class="invalid-feedback d-block" id="product_name-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Make</label>
                    <div class="position-relative">
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-grow-1 position-relative">
                                <select name="category_id[]" id="category_id" class="form-select select2-searchable" multiple data-placeholder="Select Make">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_id', $product?->categories?->pluck('id')->toArray() ?? [])))>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-dark-blue" id="add-make-btn" data-bs-toggle="modal" data-bs-target="#addMakeModal" style="padding: 0.5rem 0.75rem;" title="Add New Make">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="category_id-error"></div>
                    </div>
                </div>

                <!-- Row 2: HSN/SAC | Price -->
                <div class="col-md-6">
                    <label for="hsn_sac" class="form-label fw-semibold bom-label"><i class="fa-solid fa-hashtag me-2"></i>HSN/SAC Code</label>
                    <input type="text" name="hsn_sac" id="hsn_sac" value="{{ old('hsn_sac', $product?->hsn_sac) }}" class="form-control" placeholder="Enter HSN/SAC code" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-tag me-2"></i>Price <span class="text-danger">*</span></label>
                    <input type="number" name="price" id="price" value="{{ old('price', $product?->price) }}" class="form-control" placeholder="Enter price" step="1" min="1" required>
                    <div class="invalid-feedback d-block" id="price-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-percent me-2"></i>Tax Type</label>
                    <select name="tax_type" id="tax_type" class="form-select select2-searchable">
                        <option value="">Select Tax</option>
                        @foreach($taxes->groupBy('name') as $taxName => $taxGroup)
                            <option value="{{ $taxName }}" @selected(old('tax_type', $product?->tax_type) == $taxName)>{{ $taxName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Row 3: Tax Rate (%) | Technology -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-percentage me-2"></i>Tax Rate (%)</label>
                    <select name="tax_rate" id="tax_rate" class="form-select select2-searchable">
                        <option value="">Select Rate</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Technology</label>
                    <div class="d-flex align-items-center gap-2">
                        <select name="technology_id" id="technology_id" class="form-select select2-searchable flex-grow-1" required>
                            <option value="">Select Technology</option>
                            @foreach($technologies as $technology)
                                <option value="{{ $technology->id }}" @selected(old('technology_id', $product?->technology_id) == $technology->id)>{{ $technology->title }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-dark-blue" id="add-technology-btn" data-bs-toggle="modal" data-bs-target="#addTechnologyModal" style="padding: 0.5rem 0.75rem;" title="Add New Technology">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block" id="technology_id-error"></div>
                </div>

                <!-- Row 4: Warranty | Capacity -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Warranty</label>
                    <div class="d-flex align-items-center gap-2">
                        <select name="warranty_id" id="warranty_id" class="form-select select2-searchable flex-grow-1">
                            <option value="">Select Warranty</option>
                            @foreach($warranties as $warranty)
                                <option value="{{ $warranty->id }}" @selected(old('warranty_id', $product?->warranty_id) == $warranty->id)>{{ $warranty->title }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-dark-blue" id="add-warranty-btn" data-bs-toggle="modal" data-bs-target="#addWarrantyModal" style="padding: 0.5rem 0.75rem;" title="Add New Warranty">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block" id="warranty_id-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Capacity</label>
                    <input type="text" name="capacity" id="capacity" value="{{ old('capacity', $product?->capacity) }}" class="form-control" placeholder="Enter capacity">
                </div>

                <!-- Row 5: Size of Pipe | Thickness -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Size of Pipe</label>
                    <input type="text" name="size_of_pipe" id="size_of_pipe" value="{{ old('size_of_pipe', $product?->size_of_pipe) }}" class="form-control" placeholder="Enter size of pipe">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Thickness</label>
                    <input type="text" name="thickness" id="thickness" value="{{ old('thickness', $product?->thickness) }}" class="form-control" placeholder="Enter thickness">
                </div>

                <!-- Row 6: Height | Fitting Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Height</label>
                    <input type="text" name="height" id="height" value="{{ old('height', $product?->height) }}" class="form-control" placeholder="Enter height">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Type</label>
                    <input type="text" name="fitting_type" id="fitting_type" value="{{ old('fitting_type', $product?->fitting_type) }}" class="form-control" placeholder="Enter fitting type">
                </div>

                <!-- Row 7: Fitting Material | Meter -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Material</label>
                    <input type="text" name="fitting_material" id="fitting_material" value="{{ old('fitting_material', $product?->fitting_material) }}" class="form-control" placeholder="Enter fitting material">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-ruler me-2"></i>Meter</label>
                    <input type="text" name="meter" id="meter" value="{{ old('meter', $product?->meter) }}" class="form-control" placeholder="Enter meter">
                </div>

                <!-- Row 8: Nos (Pieces) | Image -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-cubes me-2"></i>Nos (Pieces)</label>
                    <input type="number" name="nos" id="nos" value="{{ old('nos', $product?->nos) }}" class="form-control" placeholder="Enter pieces count" step="1" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-image me-2"></i>Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml">
                    <div class="invalid-feedback d-block" id="image-error"></div>
                    <div class="mt-2" id="bom-image-preview-wrap">
                        <img src="{{ $bomPreviewImageUrl }}" alt="{{ $product?->product_name ?: 'BOM image' }}" class="img-thumbnail" style="width: 120px; height: 120px; object-fit: contain;" id="bom-image-preview" data-default-src="{{ $bomPreviewImageUrl }}" onerror="this.onerror=null;this.src='{{ $dummyBomImageUrl }}';">
                    </div>
                </div>

                <!-- Row 9: Description (Full Width) -->
                <div class="col-12">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-pen me-2"></i>Description</label>
                    <textarea name="description" id="description" class="form-control" rows="2" placeholder="Enter BOM description">{{ old('description', $product?->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                <a href="{{ route('bom-products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                    <span id="btnText">{{ $product ? 'Update' : 'Submit' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Dynamic Tax Rate Dropdown Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== TAX RATE DYNAMIC DROPDOWN =====
    const taxTypeSelect = document.getElementById('tax_type');
    const taxRateSelect = document.getElementById('tax_rate');
    const currentTaxRate = "{{ old('tax_rate', $product?->tax_rate) }}";

    // Tax data from backend
    const taxData = @json($taxes->groupBy('name'));

    function updateTaxRateOptions() {
        const selectedTaxType = taxTypeSelect.value;
        const taxes = taxData[selectedTaxType] || [];

        // Clear existing options except the first one
        taxRateSelect.innerHTML = '<option value="">Select Rate</option>';

        // Add new options from database
        taxes.forEach(tax => {
            const optionElement = document.createElement('option');
            optionElement.value = tax.rate;
            optionElement.textContent = `${tax.name} (${tax.rate}%)`;
            // Check if this option should be selected
            if (tax.rate == currentTaxRate) {
                optionElement.selected = true;
            }
            taxRateSelect.appendChild(optionElement);
        });

        // If current tax rate doesn't match any predefined option, add it as a custom option
        if (currentTaxRate && !taxes.some(tax => tax.rate == currentTaxRate)) {
            const customOption = document.createElement('option');
            customOption.value = currentTaxRate;
            customOption.textContent = `${selectedTaxType} (${currentTaxRate}%)`;
            customOption.selected = true;
            taxRateSelect.appendChild(customOption);
        }

        if (window.jQuery && $(taxRateSelect).data('select2')) {
            $(taxRateSelect).trigger('change');
        }
    }

    // Update on page load
    updateTaxRateOptions();

    // Update when Tax Type changes
    if (window.jQuery) {
        $(taxTypeSelect).on('change', updateTaxRateOptions);
    } else {
        taxTypeSelect.addEventListener('change', updateTaxRateOptions);
    }

    // ===== CLEAR VALIDATION ERRORS ON INPUT =====
    function clearFieldError(fieldId, inputElement) {
        const errorElement = document.getElementById(`${fieldId}-error`);
        if (errorElement) {
            errorElement.textContent = '';
        }
        if (inputElement) {
            inputElement.classList.remove('is-invalid');
        }
    }

    // Clear errors for all form fields when user starts typing/selecting
    const formFields = [
        { id: 'product_name', element: document.getElementById('product_name') },
        { id: 'price', element: document.getElementById('price') },
        { id: 'image', element: document.getElementById('image') },
        { id: 'tax_type', element: document.getElementById('tax_type') },
        { id: 'tax_rate', element: document.getElementById('tax_rate') },
        { id: 'technology_id', element: document.getElementById('technology_id') },
        { id: 'warranty_id', element: document.getElementById('warranty_id') }
    ];

    formFields.forEach(field => {
        if (field.element) {
            // For input fields
            if (field.element.type === 'text' || field.element.type === 'number' || field.element.type === 'file') {
                field.element.addEventListener('input', function() {
                    clearFieldError(field.id, this);
                });
            }
            // For select fields
            else if (field.element.tagName === 'SELECT') {
                field.element.addEventListener('change', function() {
                    clearFieldError(field.id, this);
                });
            }
        }
    });

    // Special handling for tax_rate custom input (when GST custom is selected)
    document.addEventListener('input', function(e) {
        if (e.target.id === 'tax_rate_custom') {
            clearFieldError('tax_rate', e.target);
        }
    });

    // ===== IMAGE PREVIEW FUNCTIONALITY =====
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('bom-image-preview');
    const imagePreviewWrap = document.getElementById('bom-image-preview-wrap');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/avif', 'image/svg+xml'];
                if (!validTypes.includes(file.type)) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select a valid image file (JPEG, PNG, GIF, BMP, WEBP, AVIF, SVG).');
                    } else {
                        alert('Please select a valid image file.');
                    }
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select an image smaller than 2MB.');
                    } else {
                        alert('Please select an image smaller than 2MB.');
                    }
                    this.value = '';
                    return;
                }
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.alt = file.name;
                    } else {
                        // Create new preview image if it doesn't exist
                        const newPreview = document.createElement('img');
                        newPreview.src = e.target.result;
                        newPreview.alt = file.name;
                        newPreview.className = 'img-thumbnail';
                        newPreview.style.width = '120px';
                        newPreview.style.height = '120px';
                        newPreview.style.objectFit = 'contain';
                        newPreview.id = 'bom-image-preview';
                        imagePreviewWrap.appendChild(newPreview);
                    }
                    
                    // Show preview wrapper
                    if (imagePreviewWrap) {
                        imagePreviewWrap.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if (imagePreview) {
                    imagePreview.src = imagePreview.dataset.defaultSrc || @json($dummyBomImageUrl);
                    imagePreview.alt = 'BOM image';
                }
                if (imagePreviewWrap) {
                    imagePreviewWrap.classList.remove('d-none');
                }
            }
        });
    }

    // ===== ADD MAKE MODAL FUNCTIONALITY =====
    const addMakeModal = document.getElementById('addMakeModal');
    const addMakeForm = document.getElementById('add-make-form');
    const saveMakeBtn = document.getElementById('save-make-btn');
    const newMakeNameInput = document.getElementById('new-make-name');
    const newMakeImageInput = document.getElementById('new-make-image');

    saveMakeBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('name', newMakeNameInput.value.trim());
        
        if (newMakeImageInput.files[0]) {
            formData.append('image', newMakeImageInput.files[0]);
        }

        // Clear previous errors
        document.querySelectorAll('#addMakeModal .invalid-feedback').forEach(el => {
            el.textContent = '';
            el.previousElementSibling?.classList.remove('is-invalid');
        });

        // Show loading state
        saveMakeBtn.disabled = true;
        saveMakeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/make', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new option to Select2
                const newOption = new Option(data.data.name, data.data.id, true, true);
                $('#category_id').append(newOption).trigger('change');
                
                // Reset form and close modal
                addMakeForm.reset();
                bootstrap.Modal.getInstance(addMakeModal).hide();
                
                // Show success message (optional)
                // You can add a toast notification here if needed
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(`new-make-${field}-error`);
                        const inputElement = document.getElementById(`new-make-${field}`);
                        
                        if (errorElement && inputElement) {
                            errorElement.textContent = data.errors[field][0];
                            inputElement.classList.add('is-invalid');
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding make:', error);
            alert('Error adding make. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveMakeBtn.disabled = false;
            saveMakeBtn.innerHTML = 'Add Make';
        });
    });

    // Reset form when modal is closed
    addMakeModal.addEventListener('hidden.bs.modal', function() {
        addMakeForm.reset();
        document.querySelectorAll('#addMakeModal .invalid-feedback').forEach(el => {
            el.textContent = '';
            el.previousElementSibling?.classList.remove('is-invalid');
        });
    });

    // ===== ADD TECHNOLOGY MODAL FUNCTIONALITY =====
    const addTechnologyModal = document.getElementById('addTechnologyModal');
    const addTechnologyForm = document.getElementById('add-technology-form');
    const saveTechnologyBtn = document.getElementById('save-technology-btn');
    const newTechnologyNameInput = document.getElementById('new-technology-name');
    const newTechnologyDescriptionInput = document.getElementById('new-technology-description');
    const technologySelect = document.getElementById('technology_id');
    const newTechnologyNameError = document.getElementById('new-technology-name-error');
    const newTechnologyDescriptionError = document.getElementById('new-technology-description-error');

    saveTechnologyBtn.addEventListener('click', function() {
        // Clear previous errors
        newTechnologyNameInput.classList.remove('is-invalid');
        newTechnologyDescriptionInput.classList.remove('is-invalid');
        newTechnologyNameError.textContent = '';
        newTechnologyDescriptionError.textContent = '';
        
        // Basic client-side validation
        let hasError = false;
        
        if (!newTechnologyNameInput.value.trim()) {
            newTechnologyNameInput.classList.add('is-invalid');
            newTechnologyNameError.textContent = 'Please enter technology name.';
            hasError = true;
        }
        
        if (hasError) {
            return;
        }

        const formData = new FormData();
        formData.append('title', newTechnologyNameInput.value.trim());
        formData.append('description', newTechnologyDescriptionInput.value.trim());

        // Show loading state
        saveTechnologyBtn.disabled = true;
        saveTechnologyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/technology', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new technology to the dropdown
                const newOption = document.createElement('option');
                newOption.value = data.data.id;
                newOption.textContent = data.data.title;
                newOption.selected = true;
                technologySelect.appendChild(newOption);
                if (window.jQuery && $(technologySelect).data('select2')) {
                    $(technologySelect).trigger('change');
                }
                
                // Reset form and close modal
                addTechnologyForm.reset();
                newTechnologyNameInput.classList.remove('is-invalid');
                newTechnologyDescriptionInput.classList.remove('is-invalid');
                newTechnologyNameError.textContent = '';
                newTechnologyDescriptionError.textContent = '';
                bootstrap.Modal.getInstance(addTechnologyModal).hide();
                
                // Clear error state
                technologySelect.classList.remove('is-invalid');
                const errorElement = document.getElementById('technology_id-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        if (field === 'title') {
                            newTechnologyNameInput.classList.add('is-invalid');
                            newTechnologyNameError.textContent = data.errors[field][0];
                        } else if (field === 'description') {
                            newTechnologyDescriptionInput.classList.add('is-invalid');
                            newTechnologyDescriptionError.textContent = data.errors[field][0];
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding technology:', error);
            alert('Error adding technology. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveTechnologyBtn.disabled = false;
            saveTechnologyBtn.innerHTML = 'Add Technology';
        });
    });

    // Reset form when modal is closed
    addTechnologyModal.addEventListener('hidden.bs.modal', function() {
        addTechnologyForm.reset();
        newTechnologyNameInput.classList.remove('is-invalid');
        newTechnologyDescriptionInput.classList.remove('is-invalid');
        newTechnologyNameError.textContent = '';
        newTechnologyDescriptionError.textContent = '';
    });

    // ===== ADD WARRANTY MODAL FUNCTIONALITY =====
    const addWarrantyModal = document.getElementById('addWarrantyModal');
    const addWarrantyForm = document.getElementById('add-warranty-form');
    const saveWarrantyBtn = document.getElementById('save-warranty-btn');
    const newWarrantyNameInput = document.getElementById('new-warranty-name');
    const newWarrantyDescriptionInput = document.getElementById('new-warranty-description');
    const warrantySelect = document.getElementById('warranty_id');
    const newWarrantyNameError = document.getElementById('new-warranty-name-error');
    const newWarrantyDescriptionError = document.getElementById('new-warranty-description-error');

    saveWarrantyBtn.addEventListener('click', function() {
        // Clear previous errors
        newWarrantyNameInput.classList.remove('is-invalid');
        newWarrantyDescriptionInput.classList.remove('is-invalid');
        newWarrantyNameError.textContent = '';
        newWarrantyDescriptionError.textContent = '';
        
        // Basic client-side validation
        let hasError = false;
        
        if (!newWarrantyNameInput.value.trim()) {
            newWarrantyNameInput.classList.add('is-invalid');
            newWarrantyNameError.textContent = 'Please enter warranty name.';
            hasError = true;
        }
        
        if (hasError) {
            return;
        }

        const formData = new FormData();
        formData.append('title', newWarrantyNameInput.value.trim());
        formData.append('description', newWarrantyDescriptionInput.value.trim());

        // Show loading state
        saveWarrantyBtn.disabled = true;
        saveWarrantyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/warranty', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new warranty to the dropdown
                const newOption = document.createElement('option');
                newOption.value = data.data.id;
                newOption.textContent = data.data.title;
                newOption.selected = true;
                warrantySelect.appendChild(newOption);
                if (window.jQuery && $(warrantySelect).data('select2')) {
                    $(warrantySelect).trigger('change');
                }
                
                // Reset form and close modal
                addWarrantyForm.reset();
                newWarrantyNameInput.classList.remove('is-invalid');
                newWarrantyDescriptionInput.classList.remove('is-invalid');
                newWarrantyNameError.textContent = '';
                newWarrantyDescriptionError.textContent = '';
                bootstrap.Modal.getInstance(addWarrantyModal).hide();
                
                // Clear error state
                warrantySelect.classList.remove('is-invalid');
                const errorElement = document.getElementById('warranty_id-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        if (field === 'title') {
                            newWarrantyNameInput.classList.add('is-invalid');
                            newWarrantyNameError.textContent = data.errors[field][0];
                        } else if (field === 'description') {
                            newWarrantyDescriptionInput.classList.add('is-invalid');
                            newWarrantyDescriptionError.textContent = data.errors[field][0];
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding warranty:', error);
            alert('Error adding warranty. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveWarrantyBtn.disabled = false;
            saveWarrantyBtn.innerHTML = 'Add Warranty';
        });
    });

    // Reset form when modal is closed
    addWarrantyModal.addEventListener('hidden.bs.modal', function() {
        addWarrantyForm.reset();
        newWarrantyNameInput.classList.remove('is-invalid');
        newWarrantyDescriptionInput.classList.remove('is-invalid');
        newWarrantyNameError.textContent = '';
        newWarrantyDescriptionError.textContent = '';
    });

    // ===== BOM FORM AJAX SUBMISSION =====
    const bomForm = document.getElementById('bomProductForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');

    bomForm.addEventListener('change', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const fieldId = e.target.id || e.target.name.replace('[]', '');
            const errorElement = document.getElementById(`${fieldId}-error`);
            if (errorElement) errorElement.textContent = '';
            
            if (e.target.classList.contains('select2-hidden-accessible')) {
                const select2Container = e.target.nextElementSibling;
                if (select2Container && select2Container.classList.contains('select2-container')) {
                    const selection = select2Container.querySelector('.select2-selection');
                    if (selection) selection.classList.remove('is-invalid');
                }
            }
        }
    });
    bomForm.addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const fieldId = e.target.id || e.target.name;
            const errorElement = document.getElementById(`${fieldId}-error`);
            if (errorElement) errorElement.textContent = '';
        }
    });

    bomForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });

        // Show loading state
        submitBtn.disabled = true;
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Processing...';

        // Prepare form data
        const formData = new FormData(bomForm);
        
        // Submit form
        fetch(bomForm.action, {
            method: bomForm.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                if (typeof window.showAlert === 'function') {
                    window.showAlert('success', data.message || 'BOM product saved successfully.', 'Success!', data.redirect || '/all_product');
                } else {
                    alert(data.message || 'BOM product saved successfully.');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(`${field}-error`);
                        let inputElement = document.getElementById(field) || document.querySelector(`[name="${field}"]`) || document.querySelector(`[name="${field}[]"]`);
                        
                        if (errorElement) {
                            errorElement.textContent = data.errors[field][0];
                        }
                        if (inputElement) {
                            inputElement.classList.add('is-invalid');
                            // Add is-invalid to select2 container if applicable
                            if (inputElement.classList.contains('select2-hidden-accessible')) {
                                const select2Container = inputElement.nextElementSibling;
                                if (select2Container && select2Container.classList.contains('select2-container')) {
                                    const selection = select2Container.querySelector('.select2-selection');
                                    if (selection) selection.classList.add('is-invalid');
                                }
                            }
                        }
                    });
                }
                
                // Don't show popup error message, just scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            if (typeof window.showAlert === 'function') {
                window.showAlert('error', 'Something went wrong. Please try again.');
            } else {
                alert('Something went wrong. Please try again.');
            }
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnSpinner.classList.add('d-none');
            btnText.textContent = bomForm.querySelector('[name="_method"][value="PATCH"]') ? 'Update' : 'Submit';
        });
    });
});
</script>

<div class="modal fade" id="addMakeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Make</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-make-form" novalidate enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="new-make-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-make-name" placeholder="Enter make name">
                        <div class="invalid-feedback" id="new-make-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-make-image" class="form-label fw-semibold">Image</label>
                        <input type="file" class="form-control" id="new-make-image" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml">
                        <div class="invalid-feedback d-block" id="new-make-image-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-make-btn">Add Make</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Technology Modal -->
<div class="modal fade" id="addTechnologyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Technology</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-technology-form" novalidate>
                    <div class="mb-3">
                        <label for="new-technology-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-technology-name" placeholder="Enter technology name">
                        <div class="invalid-feedback" id="new-technology-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-technology-description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="new-technology-description" placeholder="Enter description" rows="2"></textarea>
                        <div class="invalid-feedback" id="new-technology-description-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-technology-btn">Add Technology</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Warranty Modal -->
<div class="modal fade" id="addWarrantyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Warranty</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-warranty-form" novalidate>
                    <div class="mb-3">
                        <label for="new-warranty-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-warranty-name" placeholder="Enter warranty name">
                        <div class="invalid-feedback" id="new-warranty-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-warranty-description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="new-warranty-description" placeholder="Enter description" rows="2"></textarea>
                        <div class="invalid-feedback" id="new-warranty-description-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-warranty-btn">Add Warranty</button>
            </div>
        </div>
    </div>
</div>
