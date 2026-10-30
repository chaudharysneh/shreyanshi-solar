<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable quick-estimate-nested-modal">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add New Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addCustomerQuickForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_name" required>
                        <div class="invalid-feedback">Please enter customer name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_number" required>
                        <div class="invalid-feedback">Please enter mobile number</div>
                    </div>
                    <div class="mb-3">
                        <a href="#" class="small text-decoration-none" id="quickEstimateToggleAddress">+ Add Address (Optional)</a>
                    </div>
                    <div class="mb-0 d-none" id="quick_address_container">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea class="form-control" id="quick_customer_address" rows="2" placeholder="Address"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveQuickCustomerBtn">Save Customer</button>
            </div>
        </div>
    </div>
</div>
