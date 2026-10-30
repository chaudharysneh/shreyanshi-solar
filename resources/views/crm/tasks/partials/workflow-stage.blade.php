@php
    $events = $task->workflowEvents->where('stage_id', $step['stage_id']);
    $formatTime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Not recorded';
    $inputTime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->timezone('Asia/Kolkata')->format('Y-m-d\TH:i') : '';
@endphp
<div class="col-12">
<article id="task-stage-{{ $step['stage_id'] }}" class="task-flow-stage {{ $locked ? 'is-locked' : '' }}">
    <header class="task-flow-stage-header">
        <div class="d-flex align-items-center gap-3"><span class="task-flow-number">@if($step['status'] === 'completed')<i class="bi bi-check-lg"></i>@else{{ $stepNumber }}@endif</span><div><div class="task-flow-eyebrow">Workflow stage {{ $stepNumber }}</div><h6 class="fw-bold mb-0">{{ $step['name'] }}</h6></div></div>
        <span class="badge bg-{{ $shade }}-subtle text-{{ $shade }} rounded-pill px-3 py-2">{{ $step['status_label'] }}</span>
    </header>
    <div class="task-flow-body">
        <div class="task-flow-help mb-3">Times are shown in IST. The first active update records check-in, and completion records check-out unless you enter times below.</div>
        <div class="task-flow-time-grid">
            <div><span class="task-flow-label"><i class="bi bi-box-arrow-in-right me-1"></i>Check-in</span><strong>{{ $formatTime($step['check_in_at'] ?? null) }}</strong></div>
            <div><span class="task-flow-label"><i class="bi bi-box-arrow-right me-1"></i>Check-out</span><strong>{{ $formatTime($step['check_out_at'] ?? null) }}</strong></div>
            <div><span class="task-flow-label"><i class="bi bi-paperclip me-1"></i>Attachments</span><strong>{{ $events->sum(fn ($entry) => count($entry->attachments ?? [])) }} files</strong></div>
        </div>
        @if(!empty($step['note']))<div class="task-flow-current-note"><span class="task-flow-label">Latest remark</span><p class="mb-0 mt-1">{{ $step['note'] }}</p></div>@endif
        @if(!empty($step['location']))<div class="small text-muted mt-2"><i class="bi bi-geo-alt me-1"></i>{{ $step['location'] }}</div>@endif
        @if(auth()->user()->hasMatrixPermission('edit_tasks') && auth()->user()->can('update', $task))
            @if($locked)
                <div class="task-flow-locked"><i class="bi bi-lock me-2"></i>Complete earlier stages to begin this stage.</div>
            @else
            <form class="task-workflow-form mt-3" enctype="multipart/form-data" data-url="{{ url('/api/tasks/'.$task->id.'/workflow-step') }}">
                <input type="hidden" name="stage_id" value="{{ $step['stage_id'] }}">
                <div class="row g-3">
                    <div class="col-lg-4"><label class="form-label small fw-semibold" for="flow-status-{{ $step['stage_id'] }}">Workflow status</label><select id="flow-status-{{ $step['stage_id'] }}" name="status" class="form-select" data-workflow-status required>
                        @if($step['saved_status_unavailable'])<option value="" selected disabled>{{ $step['status_label'] }} (no longer active)</option>@endif
                        @foreach($step['options'] as $option)<option value="{{ $option['value'] }}" @selected($step['selected_status'] === $option['value'])>{{ $option['label'] }}</option>@endforeach
                    </select><div class="task-flow-help">Uses the statuses configured in Workflow.</div></div>
                    <div class="col-lg-4"><label class="form-label small fw-semibold" for="flow-in-{{ $step['stage_id'] }}">Check-in time <span class="text-muted">(IST)</span></label><div class="input-group"><input type="datetime-local" id="flow-in-{{ $step['stage_id'] }}" name="check_in_at" class="form-control" value="{{ $inputTime($step['check_in_at'] ?? null) }}"><button type="button" class="btn btn-outline-dark-blue" data-flow-now="check_in_at">Now</button></div></div>
                    <div class="col-lg-4"><label class="form-label small fw-semibold" for="flow-out-{{ $step['stage_id'] }}">Check-out time <span class="text-muted">(IST)</span></label><div class="input-group"><input type="datetime-local" id="flow-out-{{ $step['stage_id'] }}" name="check_out_at" class="form-control" value="{{ $inputTime($step['check_out_at'] ?? null) }}"><button type="button" class="btn btn-outline-dark-blue" data-flow-now="check_out_at">Now</button></div></div>
                    <div class="col-md-7"><label class="form-label small fw-semibold" for="flow-note-{{ $step['stage_id'] }}">Update / work performed</label><textarea id="flow-note-{{ $step['stage_id'] }}" name="note" class="form-control" rows="3" maxlength="2000" placeholder="Describe the work, findings or reason for this update"></textarea></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold" for="flow-files-{{ $step['stage_id'] }}">Photos & documents</label><div class="task-flow-upload"><input id="flow-files-{{ $step['stage_id'] }}" type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv"><div class="task-flow-help mt-2">Select up to 10 files, 10 MB each. Existing files stay in stage history.</div><div class="task-flow-file-list small mt-2" aria-live="polite"></div></div></div>
                    <div class="col-md-8"><label class="form-label small fw-semibold" for="flow-location-{{ $step['stage_id'] }}">Location / site details <span class="text-muted">(optional)</span></label><input id="flow-location-{{ $step['stage_id'] }}" name="location" class="form-control" maxlength="1000" placeholder="Site address, roof area or visit location"></div>
                    <div class="col-md-4 d-flex align-items-end"><button class="btn btn-dark-blue w-100" type="submit" data-flow-save><i class="bi bi-check2-circle me-2"></i>Save stage update</button></div>
                </div>
                <div class="workflow-error small text-danger mt-2" role="alert"></div>
            </form>
            @endif
        @endif
        <details class="task-flow-history mt-3">
            <summary><span><i class="bi bi-clock-history me-2"></i>Stage activity</span><span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $events->count() }} updates</span></summary>
            <div class="task-flow-events">
                @forelse($events as $entry)
                @include('crm.tasks.partials.workflow-event')
                @empty<div class="small text-muted py-3">Detailed activity will appear here after the next stage update. Existing stage progress is preserved.</div>@endforelse
            </div>
        </details>
    </div>
</article>
</div>
