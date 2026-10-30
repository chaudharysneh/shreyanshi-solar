<div class="d-flex align-items-start gap-2 lead-status-master"
    data-url="{{ url('/api/masters/lead-statuses') }}"
    data-can-manage="{{ auth()->user()->isAdmin() ? '1' : '0' }}">
    <div class="flex-grow-1" style="min-width: 0">
        <select name="status" id="status"
            class="form-select @error('status') is-invalid @enderror {{ isset($lead) ? 'js-status-comment-trigger' : '' }}"
            {{ isset($lead) ? 'required' : '' }}>
            @if(isset($lead))<option value="">Select Status</option>@endif
            @foreach ($leadStatuses as $status)
                <option value="{{ $status->code }}" data-master-id="{{ $status->id }}"
                    @selected(old('status', isset($lead) ? $lead->status : 'new') === $status->code)>{{ $status->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
    </div>
    @if(auth()->user()->isAdmin())
        <button type="button" class="btn btn-dark-blue lead-status-add" aria-label="Add lead status" title="Add lead status"><i class="bi bi-plus-lg"></i></button>
    @endif
</div>
