@extends('layouts.app')

@section('page_title', 'Services - Create')

@section('content')
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0">
            <div class="p-4">

                <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
                    <div>
                        <h1 class="h4 fw-bold mb-1">Add Service</h1>
                        <p class="text-muted small mb-0">Create a new service for an existing product.</p>
                    </div>
                    <a href="{{ route('services.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-2"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="/api/services" id="serviceForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-3">

                        {{-- Product --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Product 
                            </label>

                            <select name="product_id" id="product_id" class="form-select" required>
                                <option value="">Loading products...</option>
                            </select>

                            <div class="invalid-feedback" id="product_id-error"></div>
                        </div>


                        {{-- Service Name --}}
                        <div class="col-md-6">
                            <label class="form-label">Service Name </label>
                            <input type="text" name="service_name" id="service_name" value="{{ old('service_name') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="service_name-error"></div>
                        </div>


                        {{-- Price --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Service Price 
                            </label>

                            <input type="number" name="service_price" id="service_price" value="{{ old('service_price') }}"
                                class="form-control" step="0.01" required>

                            <div class="invalid-feedback" id="service_price-error"></div>
                        </div>


                        {{-- Status --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Status 
                            </label>

                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active" @selected(old('status') == 'active')>
                                    Active
                                </option>

                                <option value="inactive" @selected(old('status') == 'inactive')>
                                    Inactive
                                </option>

                            </select>

                            <div class="invalid-feedback" id="status-error"></div>
                        </div>


                        {{-- Description --}}
                        <div class="col-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description" id="description" rows="2" class="form-control"
                                placeholder="Enter service details..." required>{{ old('description') }}</textarea>

                            <div class="invalid-feedback" id="description-error"></div>

                        </div>

                    </div>


                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('services.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>


    <!-- Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1050" id="toastContainer">
    </div>

@endsection

@push('scripts')
    <script src="{{ crm_asset('js/service.js') }}"></script>
@endpush
