<div class="modal fade dashboard-document-modal" id="dashboard-document-list" tabindex="-1" aria-labelledby="dashboard-document-list-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header dashboard-document-header"><div><h5 class="modal-title fw-bold" id="dashboard-document-list-title">Saved Documents</h5><p class="small mb-0 mt-1">Solar documents and subsidy bonds saved by you.</p></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body p-4">
            <div class="row g-2 mb-3"><div class="col-md-8"><input class="form-control" type="search" data-document-search placeholder="Search by file name, customer or phone" aria-label="Search documents"></div><div class="col-md-4"><select class="form-select" data-document-filter aria-label="Document type"><option value="">All Documents</option><option value="solar">Solar Documents</option><option value="subsidy">Subsidy Bond</option></select></div></div>
            <div class="small text-danger mb-2" data-document-list-error role="status"></div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>File Name</th><th>Customer</th><th>Phone</th><th>Document Type</th><th>Updated</th><th>Action</th></tr></thead><tbody data-document-rows></tbody></table></div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="small text-muted" data-document-page></span><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-dark-blue" data-document-prev>Previous</button><button type="button" class="btn btn-sm btn-outline-dark-blue" data-document-next>Next</button></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
