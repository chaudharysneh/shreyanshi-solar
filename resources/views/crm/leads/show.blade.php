@extends('layouts.app')

@section('page_title', 'Lead Profile')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card mb-4">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Lead Profile</h1>
                        <p class="text-muted small mb-0">{{ $lead->name }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @if ($lead->is_converted && $lead->converted_customer_id)
                            <a href="{{ route('masters.customers.edit', $lead->converted_customer_id) }}"
                                class="btn btn-success w-100 w-md-100 w-lg-auto">
                                <i class="bi bi-person-check me-1"></i>View Customer
                            </a>
@else
                            @can('leads.edit')
                                <form method="POST" action="{{ route('leads.convert', $lead) }}" class="w-100 w-md-100 w-lg-auto">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-person-plus me-1"></i>Convert to Customer
                                    </button>
                                </form>
                            @endcan
                        @endif
                        @can('leads.edit')
                            <a href="{{ route('leads.edit', $lead) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('leads.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-envelope"></i>Email:</span>
                        <span class="detail-view-value">{{ $lead->email ?? '--' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-phone"></i>Phone:</span>
                        <span class="detail-view-value">{{ $lead->phone ?? '--' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-circle-info"></i>Status:</span>
                        <span class="badge rounded-pill px-3 bg-primary text-white">
                            {{ \App\Models\LeadStatus::options()[$lead->status] ?? ucfirst(str_replace('_', ' ', $lead->status)) }}
                        </span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-bullhorn"></i>Lead Source:</span>
                        <span class="detail-view-value">{{ $lead->leadSource?->name ?? ($lead->source ?? '--') }}</span>
                    </div>
                      @if($lead->referral_name)
                      <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-user"></i>Referral Name:</span>
                        <span class="detail-view-value">{{ $lead->referral_name }}</span>
                      </div>
                      @endif
                      @if($lead->channelPartner)
                      <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-handshake"></i>Channel Partner:</span>
                        <span class="detail-view-value">{{ $lead->channelPartner->name }}</span>
                      </div>
                      @endif
                      <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-layer-group"></i>Lead Stage:</span>
                        <span class="detail-view-value">{{ $lead->leadStage?->name ?? '--' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-building"></i>Company:</span>
                        <span class="detail-view-value">{{ $lead->company_name ?? '--' }}</span>
                    </div>
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-calendar-days"></i>Created:</span>
                        <span class="detail-view-value">{{ $lead->created_at?->format('d M, Y') ?? '--' }}</span>
                    </div>
                    <div class="col-md-12 detail-view-row">
                        <span class="detail-view-label"><i class="fa-solid fa-location-dot"></i>Address:</span>
                        <span class="detail-view-value">{{ $lead->address ?? '--' }}</span>
                    </div>
                </div>

                @php
                    $customFields = \App\Models\CustomField::where('module', 'Lead')
                        ->where('is_active', true)
                        ->get();
                @endphp
                @if ($customFields->count() > 0)
                    <div class="row g-0 detail-view-grid mt-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary text-uppercase mb-3 mt-2" style="font-size: 0.8rem; letter-spacing: 0.05em;">Additional Info</h6>
                        </div>
                        @foreach ($customFields as $field)
                            @php $val = $lead->getCustomFieldValue($field->name); @endphp
                            @if ($val)
                                <div class="col-md-6 detail-view-row">
                                    <span class="detail-view-label">{{ $field->label }}:</span>
                                    <span class="detail-view-value">{{ $val }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0">Activity Timeline</h6>
                        @can('followups.create')
                        <a href="{{ route('followups.create', ['lead_id' => $lead->id]) }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
                            <i class="bi bi-plus-lg me-1"></i>Add Activity
                        </a>
                        @endcan
                    </div>

                    <div class="timeline">
                        <div class="timeline-item d-flex gap-3 mb-4">
                            <div class="timeline-icon bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0; min-width: 32px;">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div class="timeline-content pb-3 border-bottom w-100">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold mb-0">Lead Created</h6>
                                    <span class="small text-muted">{{ $lead->created_at?->format('d M, Y h:i A') }}</span>
                                </div>
                                <p class="text-muted small mb-0">System automatically initialized the lead profile.</p>
                            </div>
                        </div>

                        @forelse ($lead->followUps->sortByDesc('scheduled_at') as $fu)
                            <div class="timeline-item d-flex gap-3 mb-4">
                                <div class="timeline-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0; min-width: 32px;">
                                    @switch($fu->channel)
                                        @case('Call') <i class="bi bi-telephone"></i> @break
                                        @case('WhatsApp') <i class="bi bi-whatsapp"></i> @break
                                        @case('Email') <i class="bi bi-envelope"></i> @break
                                        @case('Meeting') <i class="bi bi-people"></i> @break
                                        @default <i class="bi bi-chat-dots"></i>
                                    @endswitch
                                </div>
                                <div class="timeline-content pb-3 border-bottom w-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                {{ $fu->channel }} Follow-Up
                                                @if($fu->assignedUser)
                                                    <span class="text-muted fw-normal small ms-2">assigned to {{ $fu->assignedUser->name }}</span>
                                                @endif
                                            </h6>
                                            <div class="text-muted small mb-2">
                                                <i class="bi bi-calendar-event me-1"></i>{{ \Illuminate\Support\Carbon::parse($fu->scheduled_at)->format('d M, Y h:i A') }}
                                            </div>
                                            @if($fu->notes)
                                                <div class="bg-light p-2 rounded small text-dark mb-2 border-start border-primary border-4">
                                                    {{ $fu->notes }}
                                                </div>
                                            @endif
                                            <div class="small">
                                                <i class="bi bi-clock-history me-1"></i>Next: {{ $fu->next_follow_up_at ? \Illuminate\Support\Carbon::parse($fu->next_follow_up_at)->format('d M, Y h:i A') : 'None scheduled' }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            <div class="form-check form-switch p-0">
                                                <input class="form-check-input ms-0 status-toggle" type="checkbox" role="switch" data-id="{{ $fu->id }}" {{ $fu->completed ? 'checked' : '' }}>
                                                <span class="badge ms-1 {{ $fu->completed ? 'bg-success' : 'bg-warning text-dark' }} status-label">
                                                    {{ $fu->completed ? 'Completed' : 'Pending' }}
                                                </span>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                @can('followups.edit')
                                                <a href="{{ route('followups.edit', $fu) }}" class="btn btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                                @endcan
                                                <form action="{{ route('api.followups.destroy', $fu) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete activity?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-light text-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-chat-left-dots fs-3 d-block mb-2 opacity-50"></i>
                                No follow-up activities recorded yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeline = document.querySelector('.timeline');
        if (!timeline) return;

        timeline.addEventListener('change', function(e) {
            if (e.target.classList.contains('status-toggle')) {
                const id = e.target.dataset.id;
                const label = e.target.closest('.timeline-item').querySelector('.status-label');
                const checkbox = e.target;

                fetch(`/follow-ups/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        label.textContent = data.completed ? 'Completed' : 'Pending';
                        label.className = `badge ms-1 ${data.completed ? 'bg-success' : 'bg-warning text-dark'} status-label`;
                        checkbox.checked = data.completed;
                    }
                })
                .catch(err => {
                    checkbox.checked = !checkbox.checked;
                    console.error('Toggle failed:', err);
                });
            }
        });
    });
</script>
@endpush
