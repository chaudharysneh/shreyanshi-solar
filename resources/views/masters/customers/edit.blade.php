@extends('layouts.app')

@section('page_title', 'Masters - Edit Customer')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Customer</h1>
                        <p class="text-muted small mb-0">Update customer profile and settings.</p>
                    </div>
                    <a href="{{ route('masters.customers.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form id="customerForm" data-id="{{ $customer->id }}" enctype="multipart/form-data"
                    data-free-step-navigation="true" data-always-show-submit="true" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="country_id" value="">
                    <input type="hidden" name="city_id" value="">
                    <input type="hidden" name="dob" value="">
                    <input type="hidden" name="anniversary_date" value="">

                    <div class="mb-4">
                        <div class="d-flex flex-nowrap gap-2 customer-form-tabs" id="customerFormSteps">
                            <button type="button" class="btn btn-outline-dark-blue active text-nowrap" data-step="1">Personal
                                Information</button>
                            <button type="button" class="btn btn-outline-dark-blue text-nowrap" data-step="2">Other
                                Information</button>
                        </div>
                    </div>

                    <div class="customer-form-step" data-step="1">
                        <div class="border-bottom mb-4 pb-3">
                            <h5 class="mb-1">Personal Information</h5>
                            <p class="text-muted small mb-0">Enter the customer's primary contact information.
                            </p>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $customer->name) }}" placeholder="Customer Full Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $customer->email) }}" placeholder="email@example.com">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone no. <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control"
                                    value="{{ old('phone', $customer->phone) }}" placeholder="+1 234 567 890">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">WhatsApp no.</label>
                                <div class="crm-phone-copy">
                                    <input type="text" name="whatsapp" class="form-control"
                                    value="{{ old('whatsapp', $customer->whatsapp) }}" placeholder="WhatsApp Number">
                                    <button type="button" class="btn btn-sm btn-outline-dark-blue crm-phone-copy-button">Copy Phone</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea name="address" class="form-control" rows="2"
                                    placeholder="Full residential or office address">{{ old('address', $customer->address) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="customer-form-step d-none" data-step="2">
                        <div class="border-bottom mb-4 pb-3">
                            <h5 class="mb-1">Other Information</h5>
                            <p class="text-muted small mb-0">Complete the remaining customer profile information.</p>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Profile Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company Name</label>
                                <input type="text" name="company_name" class="form-control"
                                    value="{{ old('company_name', $customer->company_name) }}"
                                    placeholder="Business / Company Name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Type</label>
                                <select name="type" class="form-select">
                                    @foreach(['Individual', 'Corporate', 'Government', 'NGO'] as $type)
                                        <option value="{{ $type }}" {{ old('type', $customer->type) == $type ? 'selected' : '' }}>
                                            {{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Website</label>
                                <input type="text" name="website" class="form-control"
                                    value="{{ old('website', $customer->website) }}" placeholder="https://example.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tax Number</label>
                                <input type="text" name="tax_number" class="form-control"
                                    value="{{ old('tax_number', $customer->tax_number) }}" placeholder="GST / VAT / Tax ID">
                            </div>
                            @include('masters.customers.partials.source-fields')
                            @include('partials.custom_fields', ['model' => $customer])
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex gap-2 customer-form-actions">
                        <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-dark-blue cancel-step flex-grow-1 flex-sm-grow-0">Cancel</a>
                        <div class="me-sm-auto d-none d-sm-block"></div>
                        <button type="button" class="btn btn-outline-dark-blue prev-step d-none flex-grow-1 flex-sm-grow-0">Previous</button>
                        <button type="button" class="btn btn-dark-blue next-step flex-grow-1 flex-sm-grow-0">Next</button>
                        <button type="submit" class="btn btn-dark-blue flex-grow-1 flex-sm-grow-0">Save &amp; Exit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
        <style>
            .customer-form-tabs .btn.active {
                border-color: transparent !important;
                box-shadow: rgba(10, 37, 64, 0.5) 0 0 0 .25rem;
            }

            @media (max-width: 575.98px) {
                .customer-form-tabs .btn {
                    flex: 1 1 0;
                    min-width: 0;
                    padding-inline: .25rem;
                    font-size: .9rem;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script
            src="{{ crm_asset('js/customer.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const countryElement = document.getElementById('country_id');
                const cityElement = document.getElementById('city_id');

                if (!countryElement || !cityElement) {
                    return;
                }

                // Initialize TomSelect with proper configuration to prevent flickering
                const countrySelect = new TomSelect(countryElement, {
                    placeholder: 'Search Country',
                    maxOptions: null,
                    closeOnSelect: true,
                    hideSelected: false,
                    dropdownParent: 'body'
                });
                
                const citySelect = new TomSelect(cityElement, {
                    placeholder: 'Search City',
                    maxOptions: null,
                    closeOnSelect: true,
                    hideSelected: false,
                    dropdownParent: 'body'
                });

                // Handle country change - load cities
                countryElement.addEventListener('change', function () {
                    const countryId = this.value;

                    // Clear city selection
                    citySelect.clear();
                    citySelect.clearOptions();

                    if (!countryId) {
                        return;
                    }

                    // Fetch cities for selected country
                    fetch(`/masters/cities-by-country/${countryId}`)
                        .then(response => response.json())
                        .then(data => {
                            citySelect.clear();
                            citySelect.clearOptions();
                            
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(city => {
                                    citySelect.addOption({ value: city.id, text: city.name });
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching cities:', error);
                            citySelect.clear();
                            citySelect.clearOptions();
                        });
                });
            });
        </script>
    @endpush
@include('masters.customers.partials.source-assets')
@endsection
