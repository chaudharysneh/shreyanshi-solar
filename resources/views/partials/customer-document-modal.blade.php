<div class="modal fade" id="estimateDocsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Customer Documents</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <input type="hidden" id="estimateDocsEstimateId">
                <div class="customer-docs-intro mb-4">
                    <span class="customer-docs-icon"><i class="bi bi-folder2-open"></i></span>
                    <div><h6 class="fw-bold mb-1">Customer document checklist</h6><p class="small text-muted mb-0">Upload available documents now; you can add the rest later. Select multiple files in each field.<br>PDF, Office documents and images · Up to 10 MB per file.</p></div>
                </div>
                <div class="row g-3" id="estimateDocsFields">
                    @foreach (\App\Support\CustomerDocumentFields::REQUIRED as $category => $label)
                        <div class="col-md-6">
                            <div class="customer-doc-field h-100" data-category="{{ $category }}" data-label="{{ $label }}">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <label for="estimate-doc-{{ $category }}" class="form-label fw-semibold mb-0">{{ $label }}</label>
                                    <span class="customer-doc-status badge rounded-pill">Required</span>
                                </div>
                                <input type="file" id="estimate-doc-{{ $category }}" class="form-control customer-doc-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.jfif">
                                <div class="customer-doc-selection small text-muted mt-2" aria-live="polite">No files selected</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 mt-4 mb-3">
                    <div><h6 class="fw-bold mb-1">Extra documents</h6><span class="small text-muted">Add a name and attach any supporting files.</span></div>
                    <button type="button" class="btn btn-outline-dark-blue btn-sm flex-shrink-0" id="estimateDocsAddMore"><i class="bi bi-plus-lg me-1"></i>Add more</button>
                </div>
                <div id="estimateDocsExtraFields" class="d-flex flex-column gap-3"></div>
                <div class="alert alert-danger mt-3 mb-0" id="estimateDocsFilesError" role="alert" style="display:none;"></div>
                <h6 class="fw-bold mt-4 mb-3">Uploaded documents</h6>
                <div id="estimateDocsList" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark-blue" id="estimateDocsUploadBtn">Upload</button>
            </div>
        </div>
    </div>
</div>

