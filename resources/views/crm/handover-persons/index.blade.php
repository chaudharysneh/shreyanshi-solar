@extends('layouts.app')

@section('page_title', 'Handover Person')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link rel="stylesheet" href="{{ crm_asset('css/product-categories.css') }}">
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white border-0 py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Manage Handover Person</h1>
                    <p class="text-muted small mb-0">Manage handover persons in Solar CRM.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(auth()->user()?->hasMatrixPermission('create_handover_persons'))
                    <button class="btn btn-dark-blue" id="handoverPersonAddBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add Handover Person
                    </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 px-md-4 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">All Handover Persons</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0 bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search handover persons..." id="handoverPersonSearch">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="handoverPersonTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Phone</th>
                            <th class="d-none d-md-table-cell">Address</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="handoverPersonPagination" class="card-footer border-top-0 py-4 px-4"></div>
        </div>
    </div>

    <div class="modal fade" id="handoverPersonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                    <h5 class="modal-title fw-bold text-white" id="handoverPersonModalTitle">Add Handover Person</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="#" id="handoverPersonForm" novalidate>
                        @csrf
                        <input type="hidden" name="_method" id="handoverPersonFormMethod">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="handoverPersonName" class="form-control" required>
                            <div class="invalid-feedback" id="handoverPersonNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="handoverPersonPhone" class="form-control" required maxlength="10" inputmode="numeric" pattern="[0-9]{10}">
                            <div class="invalid-feedback" id="handoverPersonPhoneError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="handoverPersonAddress" class="form-control" rows="4"></textarea>
                            <div class="invalid-feedback" id="handoverPersonAddressError"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-3 px-4">
                        <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4 rounded-3" id="handoverPersonSubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.handoverPersonConfig = {
        indexUrl: @json(route('api.handover-persons.index')),
        storeUrl: @json(route('api.handover-persons.store')),
        showUrlTemplate: @json(route('api.handover-persons.show', ['handoverPerson' => '__ID__'])),
        updateUrlTemplate: @json(route('api.handover-persons.update', ['handoverPerson' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.handover-persons.destroy', ['handoverPerson' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_handover_persons')),
            create: @json(auth()->user()?->hasMatrixPermission('create_handover_persons')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_handover_persons')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_handover_persons')),
        }
    };
</script>
<script src="{{ crm_asset('js/handover-person.js') }}"></script>
@endpush
