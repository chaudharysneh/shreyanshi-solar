<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Task Assigned to You – ' . $this->task->title,
        );
    }

    public function content(): Content
    {
        $task       = $this->task;
        $assignedBy = auth()->user()?->name ?? 'System / Admin';
        
        $customer = $task->customer ?: $task->project?->customer;
        $customerName = $customer?->name ?: 'N/A';
        $projectName = $task->project?->name ?: 'N/A';

        return new Content(
            view: 'emails.task-assigned',
            with: [
                'staffName'   => $task->assignedUser?->name ?? 'Staff',
                'taskTitle'   => $task->title,
                'taskDesc'    => $task->description,
                'customerName'=> $customerName,
                'projectName' => $projectName,
                'taskPriority'=> $task->priority ?? 'medium',
                'taskStatus'  => $task->status ?? 'pending',
                'dueDate'     => $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'N/A',
                'assignedBy'  => $assignedBy,
                'assignedAt'  => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'taskUrl'     => url('/tasks'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
