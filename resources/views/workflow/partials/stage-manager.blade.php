<div class="modal fade" id="manageStagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-list-ol text-primary me-2"></i> Manage Stages (<span id="modalWorkflowTitle" class="text-capitalize"></span>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addStageForm" class="d-flex gap-2 mb-4">
                    <input type="text" id="newStageName" name="name" required maxlength="255" aria-label="New stage name" class="form-control" placeholder="Enter new stage name...">
                    <button type="submit" class="btn px-3 fw-bold text-white" style="background-color: var(--crm-bg-sidebar); border-color: var(--crm-bg-sidebar);" title="Add Stage">Add</button>
                </form>
                
                <div id="stageManagerError" class="text-danger small mb-3" role="alert"></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Stage Name</th>
                                <th style="width: 100px;" class="text-center">Order</th>
                                <th style="width: 150px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stagesTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Loading stages...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
