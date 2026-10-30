<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCustomFields;
use App\Traits\OwnedByUser;

class Task extends Model
{
    use HasFactory, HasCustomFields, OwnedByUser;

    protected $fillable = [
        'invoice_id',
        'workflow_steps',
        'title',
        'description',
        'related_type',
        'related_id',
        'estimate_id',
        'project_id',
        'assigned_user_id',
        'user_id',
        'due_date',
        'priority',
        'status',
        'task_type',
    ];

    protected $casts = [
        'due_date' => 'date',
        'workflow_steps' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            $task->status = static::normalizeStatusValue($task->status);
            if ($task->workflow_steps) {
                $task->task_type = 'solar_workflow';
                if ($task->exists && ($task->isDirty('estimate_id') || ($task->getOriginal('invoice_id') && $task->isDirty('invoice_id')))) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['invoice_id' => 'An installation workflow must remain linked to its original invoice.']);
                }
                $activeIds = Stage::pluck('id')->all();
                $steps = collect($task->workflow_steps)->whereIn('stage_id', $activeIds);
                if ($steps->isNotEmpty()) {
                    $expected = $steps->every(fn ($step) => $step['status'] === 'completed') ? 'completed'
                        : ($steps->contains(fn ($step) => $step['status'] !== 'pending') ? 'in_progress' : 'pending');
                    if ($task->status !== $expected) throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Update the workflow stages from the task details page. Task status follows stage progress.']);
                }
            }
            static::syncOwnedUserFromAssignee($task);
        });
    }

    public static function normalizeStatusValue(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'inprogress' => 'in_progress',
            default => $normalized,
        };
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'related_id');
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
    }

    public function workflowEvents()
    {
        return $this->hasMany(TaskWorkflowEvent::class)->orderByDesc('id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }
}
