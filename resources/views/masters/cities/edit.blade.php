@extends('layouts.masters')

@section('page_title', 'Masters - Edit City')

@section('masters_content')
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold mb-1">Edit City</h4>
                <p class="text-muted small mb-0">Modify city details and country affiliation.</p>
            </div>
            <div>
                <a href="{{ route('masters.cities.index') }}" class="btn btn-outline-dark-blue">
                    <i class="fa-solid fa-angle-left pe-1"></i><span>Back</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('masters.cities.update', $city) }}" novalidate>
            @csrf
            @method('PUT')
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">
                        City Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $city->name) }}"
                           placeholder="Enter city name"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="country_id" class="form-label fw-semibold">
                        Country
                    </label>
                    <select id="country_id" name="country_id" class="form-select @error('country_id') is-invalid @enderror">
                        <option value="">Select country (optional)</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id', $city->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="is_active" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" {{ old('is_active', $city->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $city->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4 text-muted opacity-25">

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-dark-blue px-4">
                    <i class="fa-solid fa-check me-1"></i>Update City
                </button>
                <a href="{{ route('masters.cities.index') }}" class="btn btn-outline-secondary px-3">Cancel</a>
            </div>
        </form>
    </div>
@endsection
