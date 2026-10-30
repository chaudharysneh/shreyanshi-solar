<div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Status Update History</h5>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Status</th>
                        <th class="d-none d-md-table-cell">Comment</th>
                        <th class="d-none d-md-table-cell">Updated By</th>
                        <th class="d-none d-md-table-cell">Date</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody class="js-status-history-body">
                    @forelse($histories as $history)
                        <tr>
                            <td class="ps-4">{{ $loop->iteration }}</td>
                            <td>
                                @php
                                    $leadStatusLabel = $history instanceof \App\Models\LeadStatusHistory
                                        ? (($leadStatusLabels ?? \App\Models\LeadStatus::options())[$history->status] ?? null) : null;
                                    $statusLabel = $leadStatusLabel ?? match (strtolower((string) $history->status)) {
                                        'ready_to_close' => 'Ready to Close',
                                        'won' => 'Closed Won',
                                        'lost' => 'Closed Lost',
                                        default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '-',
                                    };
                                @endphp
                                {{ $statusLabel }}
                            </td>
                            <td class="d-none d-md-table-cell text-break">{{ $history->comment ?: '-' }}</td>
                            <td class="d-none d-md-table-cell">{{ $history->updater?->name ?? 'System' }}</td>
                            <td class="d-none d-md-table-cell">{{ $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '-' }}</td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand js-status-expand" data-id="{{ $history->id }}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="status-details-{{ $history->id }}" style="display: none;">
                            <td colspan="3" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-comment-dots"></i> Comment :</div>
                                            <div class="expand-value text-break">{{ $history->comment ?: '-' }}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-user-pen"></i> Updated By :</div>
                                            <div class="expand-value">{{ $history->updater?->name ?? 'System' }}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Date :</div>
                                            <div class="expand-value text-end">{{ $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="js-status-history-empty">
                            <td colspan="6" class="text-center text-muted py-4">No status updates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@pushonce('scripts')
<script>
    document.addEventListener('click', function(e) {
        if (e.target.closest('.js-status-expand')) {
            const btn = e.target.closest('.js-status-expand');
            const id = btn.getAttribute('data-id');
            const detailsRow = document.getElementById('status-details-' + id);
            const icon = btn.querySelector('i');

            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row';
                icon.classList.replace('fa-plus', 'fa-minus');
                btn.classList.add('active');
            } else {
                detailsRow.style.display = 'none';
                icon.classList.replace('fa-minus', 'fa-plus');
                btn.classList.remove('active');
            }
        }
    });
</script>
@endpushonce