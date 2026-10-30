@extends('layouts.app')

@section('page_title', 'Projects - Create')

@section('content')
    <div class="container-fluid">

        <div class="card shadow-sm border-0">
            <div class="p-4">

                {{-- Consistent Header Section --}}
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3">
                    <div>
                        <h1 class="h4 mb-1">Add Project</h1>
                        <p class="text-muted small mb-0">Create a new project for your customer.</p>
                    </div>
                    <a href="{{ route('projects.index') }}" class="btn btn-dark-blue"><i
                            class="bi bi-arrow-left pe-2"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="/api/projects" id="projectForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project Name </label>
                            <input name="name" id="name" value="{{ old('name') }}" class="form-control" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer </label>
                            <select name="customer_id" id="customer_id" class="form-select"
                                data-search-url="{{ route('customers.search.api') }}" data-search-type="customer"
                                data-search-placeholder="-- Search Customer --" required>
                                <option value="">-- Search Customer --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                        data-phone="{{ $customer->phone }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id" class="form-select"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="-- Search User --">
                                    <option value="">-- Search User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback" id="assigned_user_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status </label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                @foreach (['pending' => 'Pending', 'ongoing' => 'Active', 'completed' => 'Completed', 'canceled' => 'Cancelled'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date </label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="start_date-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date </label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="end_date-error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="description" rows="2" class="form-control"
                                placeholder="Enter project details..." required>{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('projects.index') }}" class="btn btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="{{ crm_asset('js/project.js') }}"></script>
    @endpush
@endsection