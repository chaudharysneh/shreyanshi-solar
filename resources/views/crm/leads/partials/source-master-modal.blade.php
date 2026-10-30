@if(auth()->user()->isAdmin())
<div class="modal fade" id="leadSourceMasterModal" tabindex="-1" aria-labelledby="leadSourceMasterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="leadSourceMasterForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="leadSourceMasterTitle">Add Lead Source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="leadSourceMasterName" class="form-label">Source Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="leadSourceMasterName" name="name" maxlength="255" required>
                <div class="text-danger small mt-2" id="leadSourceMasterError" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark-blue">Save</button>
            </div>
        </form>
    </div>
</div>
@endif
