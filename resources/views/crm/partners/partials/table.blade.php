<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 responsive-table" id="partnersTable">
        <thead><tr><th class="ps-4">Partner</th><th>Contact</th><th>Commission</th><th>Assigned To</th><th>Leads</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
        <tbody>
        @forelse($partners as $partner)
            <tr>
                <td class="ps-4"><a class="fw-semibold" href="{{ route('partners.show',$partner) }}">{{ $partner->name }}</a><div class="small text-muted">{{ $partner->partner_code }} @if($partner->company_name)· {{ $partner->company_name }}@endif</div></td>
                <td><div>{{ $partner->phone }}</div><div class="small text-muted">{{ $partner->email ?: '—' }}</div></td>
                <td><div class="fw-semibold">{{ number_format($partner->commission_value,2) }}{{ $partner->commission_type === 'percent' ? '%' : ' (fixed)' }}</div><div class="small text-muted">{{ str_replace('_',' ',ucfirst($partner->commission_basis)) }}</div></td>
                <td>{{ $partner->assignedUser?->name ?: '—' }}</td><td>{{ $partner->leads_count }}</td>
                <td><span class="badge rounded-pill {{ $partner->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($partner->status) }}</span></td>
                <td class="text-end pe-4 text-nowrap">
                    <a href="{{ route('partners.show',$partner) }}" class="btn crm-action-btn btn-sm" aria-label="View {{ $partner->name }}" title="View"><i class="bi bi-eye"></i></a>
                    @if(auth()->user()->hasMatrixPermission('edit_partners'))<a href="{{ route('partners.edit',$partner) }}" class="btn crm-action-btn btn-sm" aria-label="Edit {{ $partner->name }}" title="Edit"><i class="bi bi-pencil"></i></a>@endif
                    @if(auth()->user()->hasMatrixPermission('delete_partners'))<form method="POST" action="{{ route('partners.destroy',$partner) }}" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn crm-action-btn btn-sm text-danger" aria-label="Delete {{ $partner->name }}" title="Delete"><i class="bi bi-trash"></i></button></form>@endif
                </td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-people fs-2 d-block mb-2"></i>No partners found.</td></tr>@endforelse
        </tbody>
    </table>
</div>
<div class="px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3"><span class="small text-muted">Showing {{ $partners->firstItem() ?? 0 }} to {{ $partners->lastItem() ?? 0 }} of {{ $partners->total() }} partners</span>{{ $partners->links('pagination::bootstrap-5') }}</div>
