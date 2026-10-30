                <div class="task-flow-event">
                    <div class="d-flex justify-content-between flex-wrap gap-2"><strong>{{ $entry->status }}</strong><time>{{ $entry->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }} IST</time></div>
                    <div class="small text-muted mt-1">{{ $entry->updater?->name ?? 'System' }} &middot; {{ $entry->previous_status ?: 'Initial state' }} &rarr; {{ $entry->status }}</div>
                    @if($entry->note)<p class="small mt-2 mb-2 text-break">{{ $entry->note }}</p>@endif
                    <div class="task-flow-event-times"><span>Check-in: {{ $formatTime($entry->check_in_at) }}</span><span>Check-out: {{ $formatTime($entry->check_out_at) }}</span></div>
                    @if($entry->location)<div class="small text-muted mt-2"><i class="bi bi-geo-alt me-1"></i>{{ $entry->location }}</div>@endif
                    @if($entry->attachments)<div class="task-flow-event-files mt-2">@foreach($entry->attachments as $file)<a href="{{ route('tasks.workflow-attachment', ['task'=>$task->id,'event'=>$entry->id,'attachment'=>$loop->index]) }}"><i class="bi {{ str_starts_with($file['mime'], 'image/') ? 'bi-image' : 'bi-file-earmark-text' }} me-1"></i>{{ $file['name'] }}<span>{{ number_format($file['size']/1024, 0) }} KB</span></a>@endforeach</div>@endif
                </div>
