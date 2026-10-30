<?php
namespace App\Services;

use App\Models\{Stage, Task, TaskWorkflowEvent};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

class TaskWorkflowService
{
    private const TYPES = ['Document Verification'=>'document-verification','Registration'=>'registration','Meter Charge'=>'feasibility','Finance'=>'finance','Waiting Floor'=>'waiting-floor','Dispatch'=>'dispatch','Installations'=>'installations','Intimation'=>'intimation','Net Metering'=>'net-metering','Subsidy'=>'subsidy'];

    public function options(Task $task, array $step): array
    {
        $name = Stage::find($step['stage_id'])?->name ?? $step['name'];
        $type = self::TYPES[$name] ?? self::TYPES[$step['name']] ?? null;
        if (!$type) return collect(['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed'])->map(fn ($label,$key)=>['value'=>$key,'label'=>$label,'key'=>$key,'type'=>null])->values()->all();
        $customer = $task->invoice?->customer ?? $task->customer;
        $master = match ($type) { 'finance'=>$customer?->finance_id, 'installations'=>$customer?->installation_master_id, default=>null };
        $catalog = app(WorkflowStageCatalog::class);
        return $catalog->stages($catalog->context($type, $master ? (int) $master : null))->map(fn ($row)=>[
            'value'=>$type.':'.($master ?: 0).':'.$row->id, 'label'=>$row->name,
            'key'=>in_array($row->system_key,['pending','completed'],true) ? $row->system_key : 'in_progress', 'type'=>$type,
        ])->all();
    }

    public function steps(Task $task)
    {
        $active = Stage::pluck('id')->all();
        return collect($task->workflow_steps ?? [])->whereIn('stage_id',$active)->map(function ($step) use ($task) {
            $step['options'] = $this->options($task,$step);
            $current = collect($step['options'])->firstWhere('value',$step['workflow_status'] ?? null)
                ?? (isset($step['workflow_status']) ? null : collect($step['options'])->firstWhere('key',$step['status']));
            $step['selected_status'] = $current['value'] ?? ($step['workflow_status'] ?? '');
            $step['status_label'] = $current['label'] ?? ($step['workflow_status_label'] ?? ucwords(str_replace('_',' ',$step['status'])));
            $step['saved_status_unavailable'] = $current === null;
            return $step;
        })->values();
    }

    public function update(Task $task, array $data, array $files, int $userId): void
    {
        $stored = [];
        try {
            DB::transaction(function () use ($task,$data,$files,$userId,&$stored) {
                $task = Task::whereKey($task->id)->lockForUpdate()->firstOrFail();
                $steps = $task->workflow_steps ?? [];
                $active = Stage::pluck('id')->all();
                $index = collect($steps)->search(fn ($step)=>(int)$step['stage_id']===(int)$data['stage_id'] && in_array($step['stage_id'],$active));
                if ($index === false) throw ValidationException::withMessages(['stage_id'=>'This stage is unavailable.']);
                $old = $steps[$index];
                $choice = collect($this->options($task,$old))->firstWhere('value',$data['status']);
                if (!$choice) throw ValidationException::withMessages(['status'=>'Select an active status configured for this workflow.']);
                foreach ($steps as $i=>$step) {
                    if ($i<$index && in_array($step['stage_id'],$active) && $step['status']!=='completed') throw ValidationException::withMessages(['status'=>'Complete the earlier stages first.']);
                    if ($i>$index && in_array($step['stage_id'],$active) && $step['status']!=='pending' && $choice['key']!=='completed') throw ValidationException::withMessages(['status'=>'Reopen later stages before reopening this stage.']);
                }
                $parse = function ($value, $previous) {
                    if (!$value) return $previous;
                    if ($previous && Carbon::parse($previous)->timezone('Asia/Kolkata')->format('Y-m-d\TH:i') === $value) return $previous;
                    return Carbon::createFromFormat('!Y-m-d\TH:i',$value,'Asia/Kolkata')->utc()->toIso8601String();
                };
                $checkIn = $parse($data['check_in_at'] ?? null, $old['check_in_at'] ?? null);
                $checkOut = $parse($data['check_out_at'] ?? null, $old['check_out_at'] ?? null);
                if ($choice['key']!=='pending' && !$checkIn) $checkIn=now()->utc()->toIso8601String();
                if ($choice['key']==='completed' && !$checkOut) $checkOut=now()->utc()->toIso8601String();
                if ($checkOut && !$checkIn) throw ValidationException::withMessages(['check_out_at'=>'Record check-in before check-out.']);
                if ($checkOut && Carbon::parse($checkOut)->lt(Carbon::parse($checkIn))) throw ValidationException::withMessages(['check_out_at'=>'Check-out cannot be earlier than check-in.']);
                if (($checkIn && Carbon::parse($checkIn)->isFuture()) || ($checkOut && Carbon::parse($checkOut)->isFuture())) throw ValidationException::withMessages(['check_in_at'=>'Visit times cannot be in the future.']);
                $note=trim($data['note'] ?? ''); $location=trim($data['location'] ?? '') ?: ($old['location'] ?? '');
                $attachments=[];
                foreach ($files as $file) {
                    $path=$file->store('task-workflow/'.$task->id,'local');
                    if (!$path) throw new \RuntimeException('Unable to store the attachment. Please try again.');
                    $stored[]=$path;
                    $attachments[]=['name'=>$file->getClientOriginalName(),'path'=>$path,'mime'=>$file->getMimeType(),'size'=>$file->getSize()];
                }
                $task->workflowEvents()->create([
                    'stage_id'=>$old['stage_id'],'stage_name'=>$old['name'],'workflow_type'=>$choice['type'],
                    'previous_status'=>$old['workflow_status_label'] ?? ucwords(str_replace('_',' ',$old['status'])),
                    'status'=>$choice['label'],'status_key'=>$choice['value'],'note'=>$note ?: null,'location'=>$location ?: null,
                    'check_in_at'=>$checkIn ? Carbon::parse($checkIn) : null,'check_out_at'=>$checkOut ? Carbon::parse($checkOut) : null,
                    'attachments'=>$attachments,'updated_by'=>$userId,
                ]);
                $steps[$index]=array_merge($old,[
                    'status'=>$choice['key'],'workflow_status'=>$choice['value'],'workflow_status_label'=>$choice['label'],
                    'check_in_at'=>$checkIn,'check_out_at'=>$checkOut,'note'=>$note ?: ($old['note'] ?? null),
                    'location'=>$location ?: ($old['location'] ?? null),'updated_at'=>now()->utc()->toIso8601String(),'updated_by'=>$userId,
                ]);
                $visible=collect($steps)->whereIn('stage_id',$active);
                $overall=$visible->every(fn ($step)=>$step['status']==='completed') ? 'completed' : ($visible->contains(fn ($step)=>$step['status']!=='pending') ? 'in_progress' : 'pending');
                $task->update(['workflow_steps'=>$steps,'status'=>$overall]);
                $task->statusHistories()->create(['status'=>$overall,'comment'=>$old['name'].': '.$choice['label'].($note ? ' - '.$note : ''),'updated_by'=>$userId]);
            });
        } catch (\Throwable $error) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $error;
        }
    }
}
