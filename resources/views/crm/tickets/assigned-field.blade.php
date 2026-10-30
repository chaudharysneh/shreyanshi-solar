<div class="col-12 col-md-6">
    <label for="assigned_user_id" class="form-label fw-semibold">Assigned To</label>
    @if(auth()->user()->isAdmin())
        <select name="assigned_user_id" id="assigned_user_id" class="form-select" data-search-url="{{ route('api.users.search') }}" data-search-type="user" data-search-placeholder="-- Search User --">
            <option value="">-- Search User --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('assigned_user_id', isset($ticket) ? $ticket->assigned_user_id : auth()->id()) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    @else
        <input class="form-control" value="{{ isset($ticket) ? ($ticket->assignedUser?->name ?? 'Unassigned') : auth()->user()->name }}" readonly>
    @endif
    <div class="invalid-feedback" id="assigned_user_id-error"></div>
</div>
