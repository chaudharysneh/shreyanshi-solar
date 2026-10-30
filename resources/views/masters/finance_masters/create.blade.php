@extends('layouts.masters')

@section('page_title', 'Masters - Add Finance Option')

@section('masters_content')
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold mb-1">Add Finance Option</h4>
                <p class="text-muted small mb-0">Create a new finance master option and define its workflow stages.</p>
            </div>
            <div>
                <a href="{{ route('masters.finance_masters.index') }}" class="btn btn-outline-dark-blue">
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

        <form method="POST" action="{{ route('masters.finance_masters.store') }}" novalidate>
            @csrf
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">
                        Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="Enter finance option name"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="is_active" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Enter optional description...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="border-top pt-4 mt-2 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Pipeline Stages</h5>
                        <p class="text-muted small mb-0">Define the sequential workflow stages for this finance option.</p>
                    </div>
                    <button type="button" id="addStageBtn" class="btn btn-outline-dark-blue btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Add Stage
                    </button>
                </div>

                <div id="stagesContainer">
                    <!-- Initial Stage Row -->
                    <div class="row align-items-center mb-3 stage-row">
                        <div class="col-md-10 col-9">
                            <input type="text" name="stages[0][name]" class="form-control" placeholder="e.g. Document Collection" required>
                        </div>
                        <div class="col-md-2 col-3">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-stage" disabled title="Remove stage">
                                <i class="fa-solid fa-trash me-1"></i><span class="d-none d-md-inline">Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4 text-muted opacity-25">

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-dark-blue px-4">
                    <i class="fa-solid fa-check me-1"></i>Save Finance Option
                </button>
                <a href="{{ route('masters.finance_masters.index') }}" class="btn btn-outline-secondary px-3">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stagesContainer = document.getElementById('stagesContainer');
        const addStageBtn = document.getElementById('addStageBtn');
        let stageIndex = 1;

        addStageBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row align-items-center mb-3 stage-row';
            row.innerHTML = `
                <div class="col-md-10 col-9">
                    <input type="text" name="stages[${stageIndex}][name]" class="form-control" placeholder="Enter stage name" required>
                </div>
                <div class="col-md-2 col-3">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-stage" title="Remove stage">
                        <i class="fa-solid fa-trash me-1"></i><span class="d-none d-md-inline">Delete</span>
                    </button>
                </div>
            `;
            stagesContainer.appendChild(row);
            stageIndex++;
            updateRemoveButtons();
        });

        stagesContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-stage');
            if (btn && !btn.disabled) {
                btn.closest('.stage-row').remove();
                updateRemoveButtons();
            }
        });

        function updateRemoveButtons() {
            const rows = stagesContainer.querySelectorAll('.stage-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('.remove-stage');
                if (rows.length === 1) {
                    btn.disabled = true;
                } else {
                    btn.disabled = false;
                }
            });
        }
    });
</script>
@endpush
