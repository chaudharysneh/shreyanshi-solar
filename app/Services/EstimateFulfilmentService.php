<?php
namespace App\Services;

use App\Models\{Estimate, Deal, Task, Stage, Status};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EstimateFulfilmentService
{
    public function ensure(Estimate $estimate): Task
    {
        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::whereKey($estimate->getKey())->lockForUpdate()->firstOrFail();
            $assignee = $estimate->user_id ?: $estimate->created_by ?: auth()->id();
            $status = Status::where('type', 'deal')->where('is_active', true)->whereRaw('LOWER(name) = ?', ['won/confirm'])->first()
                ?: Status::firstOrCreate(['type' => 'deal', 'name' => 'Won/Confirm'], ['is_active' => true]);
            $deal = Deal::firstOrCreate(['estimate_id' => $estimate->getKey()], [
                'customer_id' => $estimate->customer_id, 'title' => $estimate->estimate_name,
                'amount' => $estimate->amount, 'currency_id' => \App\Support\DocumentCurrency::idForCode($estimate->currency ?: \App\Support\DocumentCurrency::defaultCode()),
                'assigned_user_id' => $assignee, 'user_id' => $assignee, 'created_by' => auth()->id() ?: $assignee,
                'status_id' => $status->id, 'stage_id' => Stage::orderBy('sort_order')->orderBy('id')->value('id'),
                'probability' => 100,
            ]);
            $invoiceId = \App\Models\Invoice::where('estimate_id', $estimate->getKey())->latest('id')->value('id');
            if ($invoiceId && $deal->invoice_id != $invoiceId) $deal->update(['invoice_id' => $invoiceId]);
            $task = Task::firstOrCreate(['estimate_id' => $estimate->getKey(), 'task_type' => 'solar_workflow'], [
                'invoice_id' => $invoiceId,
                'title' => 'Solar installation - '.(\App\Models\Invoice::find($invoiceId)?->invoice_name ?: 'Invoice'),
                'description' => 'Installation workflow for the linked invoice. Complete the stages in order.',
                'related_type' => 'customer', 'related_id' => $estimate->customer_id,
                'assigned_user_id' => $assignee, 'user_id' => $assignee, 'status' => 'pending', 'priority' => 'medium',
                'workflow_steps' => Stage::orderBy('sort_order')->orderBy('id')->get()->map(fn ($stage) => [
                    'stage_id' => $stage->id, 'name' => $stage->name, 'status' => 'pending',
                ])->all(),
            ]);
            if ($invoiceId && (int) $task->invoice_id !== (int) $invoiceId) {
                Task::whereKey($task->id)->update(['invoice_id' => $invoiceId]);
                $task->refresh();
            }
            if (str_starts_with($task->title, 'Solar installation ? ')) $task->update(['title' => 'Solar installation - '.$estimate->estimate_name]);
            if (!$task->statusHistories()->exists()) $task->statusHistories()->create([
                'status' => $task->status, 'comment' => 'Installation workflow initialized for this invoice.',
                'updated_by' => auth()->id() ?: $assignee,
            ]);
            return $task;
        });
    }

    public function updateStep(Task $task, int $stageId, string $status, ?string $note, int $userId): void
    {
        DB::transaction(function () use ($task, $stageId, $status, $note, $userId) {
            $task = Task::whereKey($task->id)->lockForUpdate()->firstOrFail();
            $steps = $task->workflow_steps ?? [];
            $activeIds = Stage::pluck('id')->all();
            $index = collect($steps)->search(fn ($step) => (int) $step['stage_id'] === $stageId && in_array($stageId, $activeIds));
            if ($index === false) throw ValidationException::withMessages(['stage_id' => 'This stage is unavailable.']);
            foreach ($steps as $i => $step) {
                if ($i < $index && in_array($step['stage_id'], $activeIds) && $step['status'] !== 'completed') {
                    throw ValidationException::withMessages(['status' => 'Complete the previous stages first.']);
                }
                if ($i > $index && in_array($step['stage_id'], $activeIds) && $step['status'] !== 'pending' && $status !== 'completed') {
                    throw ValidationException::withMessages(['status' => 'Reopen later stages first.']);
                }
            }
            if ($steps[$index]['status'] === $status && !$note) return;
            $steps[$index]['status'] = $status;
            $steps[$index]['updated_at'] = now()->toIso8601String();
            $steps[$index]['updated_by'] = $userId;
            $steps[$index]['note'] = $note;
            $visible = collect($steps)->whereIn('stage_id', $activeIds);
            $overall = $visible->every(fn ($step) => $step['status'] === 'completed') ? 'completed'
                : ($visible->contains(fn ($step) => $step['status'] !== 'pending') ? 'in_progress' : 'pending');
            $task->update(['workflow_steps' => $steps, 'status' => $overall]);
            $task->statusHistories()->create(['status' => $overall, 'comment' => $steps[$index]['name'].': '.ucwords(str_replace('_', ' ', $status)).($note ? ' - '.$note : ''), 'updated_by' => $userId]);
        });
    }
}
