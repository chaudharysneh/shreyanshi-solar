<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Your Solar Project is Complete – Rising Green Energy',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-completed',
            with: [
                'customerName'       => $this->project->customer?->name ?? 'Valued Customer',
                'projectName'        => $this->project->name,
                'projectDescription' => $this->project->description ?? null,
                'completedAt'        => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
