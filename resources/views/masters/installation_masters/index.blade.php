@extends('layouts.masters')

@section('page_title', 'Masters - Installation Options')

@section('masters_content')
<div class="card-header border-bottom-0 py-3 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-0">Manage Installation Options</h4>
            <p class="text-muted small mb-0">Configure the installation options available for customer records.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('masters.installation_masters.create') }}" class="btn btn-dark-blue">
                <i class="fa-solid fa-plus me-1"></i>Add Installation Option
            </a>
        </div>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <h6 class="fw-bold mb-0">Active Installation Options</h6>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="masterSearch" class="form-control crm-search-input border-0" placeholder="Search installation options...">
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check view-toggle" name="viewToggle" id="tableView" value="table" checked>
                <label class="btn btn-outline-dark-blue px-3 d-flex align-items-center" for="tableView" title="Table View" style="height: 31px; cursor: pointer;"><i class="fa-solid fa-list"></i></label>
                
                <input type="radio" class="btn-check view-toggle" name="viewToggle" id="cardView" value="card">
                <label class="btn btn-outline-dark-blue px-3 d-flex align-items-center" for="cardView" title="Grid View" style="height: 31px; cursor: pointer;"><i class="fa-solid fa-border-all"></i></label>
            </div>
        </div>
    </div>
</div>

<div class="card-body p-0">
    @if (session('success'))
        <div class="alert alert-success mx-4 my-2 py-2">{{ session('success') }}</div>
    @endif

    <!-- Table View Section -->
    <div id="tableViewSection" class="table-responsive">
        <table id="installationTable" class="table table-hover align-middle mb-0 responsive-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 80px;">Sr.No</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-end pe-4" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="installationTableBody">
                @forelse ($installationMasters as $installation)
                    <tr>
                        <td class="ps-4" data-label="Sr.No">
                            <span class="small fw-semibold text-muted">{{ $loop->iteration + ($installationMasters->currentPage() - 1) * $installationMasters->perPage() }}</span>
                        </td>
                        <td data-label="Name">
                            <div class="fw-bold small text-dark">{{ $installation->name }}</div>
                        </td>
                        <td data-label="Description">
                            <div class="small text-muted">{{ $installation->description ?: '-' }}</div>
                        </td>
                        <td data-label="Status">
                            @if($installation->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-4" data-label="Actions">
                            <div class="d-inline-flex align-items-center justify-content-end gap-2 w-100">
                                <a href="{{ route('masters.installation_masters.edit', $installation) }}" class="btn crm-action-btn btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('masters.installation_masters.destroy', $installation) }}" method="POST" class="d-inline m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn crm-action-btn btn-sm text-danger" title="Delete" onclick="return confirm('Delete this installation option?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-state-row">
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-tools display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted mb-0">No installation options found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View Section -->
    <div id="cardViewSection" class="p-4 d-none">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" id="cardsGrid">
            @forelse ($installationMasters as $installation)
                @php
                    $initials = strtoupper(substr($installation->name, 0, 2));
                @endphp
                <div class="col master-card-item">
                    <div class="card border border-light-subtle shadow-sm rounded-3 h-100 overflow-hidden bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-grad-{{ $installation->id % 3 }} rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px; font-size: 0.95rem;">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">{{ $installation->name }}</h5>
                                        <span class="text-muted small d-block">ID: #{{ $installation->id }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($installation->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-muted small mb-0" style="min-height: 38px;">{{ $installation->description ?: 'No description provided.' }}</p>
                        </div>
                        <div class="card-footer bg-light border-0 py-2 px-3 d-flex justify-content-end gap-2">
                            <a href="{{ route('masters.installation_masters.edit', $installation) }}" class="btn crm-action-btn btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('masters.installation_masters.destroy', $installation) }}" method="POST" class="d-inline m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn crm-action-btn btn-sm text-danger" title="Delete" onclick="return confirm('Delete this installation option?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-4 small">No installation options added yet.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card-footer bg-white border-0 pt-3 pb-3 px-4 pagination-wrapper">
    {{ $installationMasters->links() }}
</div>
@endsection

@push('styles')
    <style>
        .crm-search-icon {
            background-color: #f8fafc;
            color: #94a3b8;
            border-top-left-radius: 9999px !important;
            border-bottom-left-radius: 9999px !important;
            padding-left: 1rem;
        }

        .crm-search-input {
            background-color: #f8fafc;
            border-top-right-radius: 9999px !important;
            border-bottom-right-radius: 9999px !important;
            padding-right: 1rem;
            color: #334155;
        }

        .crm-search-input:focus {
            background-color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(10, 37, 64, 0.08);
            border-color: #cbd5e1;
        }

        .crm-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            transition: all 0.2s ease;
        }

        .crm-action-btn:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .crm-action-btn.text-danger:hover {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #ef4444 !important;
        }

        .avatar-grad-0 { background: linear-gradient(135deg, #0a2540 0%, #1e3a5f 100%); }
        .avatar-grad-1 { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .avatar-grad-2 { background: linear-gradient(135deg, #059669 0%, #047857 100%); }

        #installationTable thead th {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .pagination-wrapper nav .d-none.flex-sm-fill > div:first-child {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle View
        const toggles = document.querySelectorAll('.view-toggle');
        const tableViewSection = document.getElementById('tableViewSection');
        const cardViewSection = document.getElementById('cardViewSection');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                if (this.value === 'table') {
                    tableViewSection.classList.remove('d-none');
                    cardViewSection.classList.add('d-none');
                } else {
                    tableViewSection.classList.add('d-none');
                    cardViewSection.classList.remove('d-none');
                }
            });
        });

        // Real-time Search
        const searchInput = document.getElementById('masterSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const value = this.value.toLowerCase().trim();
                
                // Filter table rows
                const tableRows = tableViewSection.querySelectorAll('#installationTableBody tr:not(.empty-state-row)');
                tableRows.forEach(row => {
                    if (row.textContent.toLowerCase().includes(value)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Filter cards
                const cards = cardViewSection.querySelectorAll('.master-card-item');
                cards.forEach(card => {
                    if (card.textContent.toLowerCase().includes(value)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endpush
