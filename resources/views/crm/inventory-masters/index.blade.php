<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden categories-card">
        <div class="card-header bg-white border-bottom-0 px-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">{{ $pageTitle }}</h4>
                    <p class="text-muted small mb-0">Manage {{ strtolower($resourcePlural) }} in Solar CRM.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($permissions['create'] ?? true)
                    <button class="btn btn-dark-blue" id="{{ $moduleKey }}AddBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add {{ $resourceTitle }}
                    </button>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center categories-toolbar gap-3">
                <h6 class="fw-bold mb-0">All {{ $resourcePlural }}</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search {{ strtolower($resourcePlural) }}..." id="{{ $moduleKey }}Search">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="{{ $moduleKey }}Table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            @if($hasImage)
                                <th class="d-none d-md-table-cell" style="width: 100px;">Image</th>
                            @endif
                            <th>{{ $resourceTitle }} Name</th>
                            @if($hasDescription)
                                <th class="d-none d-md-table-cell">Description</th>
                            @endif
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="{{ 4 + ($hasDescription ? 1 : 0) + ($hasImage ? 1 : 0) }}" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="{{ $moduleKey }}Pagination" class="card-footer border-top-0 py-4 px-4"></div>
        </div>
    </div>

    <div class="modal fade" id="{{ $moduleKey }}Modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white" id="{{ $moduleKey }}ModalTitle">Add {{ $resourceTitle }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="#" id="{{ $moduleKey }}Form" novalidate enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="{{ $moduleKey }}FormMethod">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ $fieldLabel }} <span class="text-danger">*</span></label>
                            <input type="text" name="{{ $fieldName }}" id="{{ $moduleKey }}Field" class="form-control" required>
                            <div class="invalid-feedback" id="{{ $moduleKey }}FieldError"></div>
                        </div>
                        @if($hasDescription)
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="{{ $moduleKey }}Description" class="form-control" rows="3"></textarea>
                                <div class="invalid-feedback" id="{{ $moduleKey }}DescriptionError"></div>
                            </div>
                        @endif
                        @if($hasImage)
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" id="{{ $moduleKey }}Image" class="form-control" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml">
                                <div class="invalid-feedback d-block" id="{{ $moduleKey }}ImageError"></div>
                                <div class="mt-3 d-none" id="{{ $moduleKey }}ImagePreviewWrap">
                                    <img src="" alt="Preview" class="img-thumbnail" style="width: 120px; height: 120px; object-fit: contain;" id="{{ $moduleKey }}ImagePreview">
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-3 px-4">
                        <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4 rounded-3" id="{{ $moduleKey }}SubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
