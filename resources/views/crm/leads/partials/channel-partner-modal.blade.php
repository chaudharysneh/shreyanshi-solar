<div class="modal fade" id="leadChannelPartnerModal" tabindex="-1" aria-labelledby="leadChannelPartnerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="leadChannelPartnerForm" class="modal-content rounded-4 border-0 shadow" novalidate>
            <div class="modal-header py-3 px-4" style="background:#121a33">
                <h5 id="leadChannelPartnerTitle" class="modal-title fw-bold text-white">Add Channel Partner</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3"><label for="partner_name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label><input id="partner_name" name="name" class="form-control" maxlength="255" required><div class="invalid-feedback" data-error="name"></div></div>
                <div class="mb-3"><label for="partner_phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label><input id="partner_phone" name="phone" type="tel" class="form-control" maxlength="50" required><div class="invalid-feedback" data-error="phone"></div></div>
                <div><label for="partner_address" class="form-label fw-semibold">Address</label><textarea id="partner_address" name="address" class="form-control" rows="3" maxlength="2000"></textarea><div class="invalid-feedback" data-error="address"></div></div>
                <div class="text-danger small mt-2" id="leadChannelPartnerError" role="alert"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark-blue">Save Partner</button></div>
        </form>
    </div>
</div>
