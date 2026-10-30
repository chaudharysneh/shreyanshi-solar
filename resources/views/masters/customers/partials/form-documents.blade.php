@foreach(['solar'=>'Solar Documents','subsidy'=>'Subsidy Bond'] as $type=>$label)
<div class="tab-pane fade p-3 p-md-4" id="customer-{{ $type }}-documents" role="tabpanel" aria-labelledby="customer-{{ $type }}-documents-tab">
    <h6 class="fw-bold mb-1">{{ $label }}</h6><p class="small text-muted mb-3">Saved documents and their previous versions for this customer.</p>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>File Name</th><th>Saved</th><th>Last Updated</th><th>Versions</th><th>Actions</th></tr></thead><tbody>
    @forelse($formDocuments->where('type',$type) as $document)
        <tr><td>{{ $document->filename }}</td><td>{{ $document->created_at->format('d M Y, h:i A') }}</td><td>{{ $document->updated_at->format('d M Y, h:i A') }}</td><td>{{ $document->versions_count }}</td><td><a class="btn btn-sm btn-outline-dark-blue" href="{{ route('customers.form-documents.show',[$customer,$document->id]) }}"><i class="bi bi-clock-history me-1"></i>View &amp; History</a></td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No {{ strtolower($label) }} saved for this customer yet.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
@endforeach
