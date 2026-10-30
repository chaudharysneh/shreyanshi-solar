<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $moduleLabel;
    public string $actionLabel;
    public string $actorName;
    public string $actorEmail;
    public string $recordName;
    public array  $details;
    public ?string $entityUrl;
    public string $emailSubject;

    public function __construct(
        string  $module,
        string  $action,
        User    $actor,
        string  $recordName = 'N/A',
        array   $details = [],
        ?string $entityUrl = null
    ) {
        $this->moduleLabel  = ucfirst($module);
        $this->actionLabel  = ucfirst($action);
        $this->actorName    = $actor->name;
        $this->actorEmail   = $actor->email;
        $this->recordName   = $recordName;
        $this->details      = $details;
        $this->entityUrl    = $entityUrl;
        $this->emailSubject = "{$this->moduleLabel} - {$this->recordName}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-notification',
            with: [
                'moduleLabel'  => $this->moduleLabel,
                'actionLabel'  => $this->actionLabel,
                'actorName'    => $this->actorName,
                'actorEmail'   => $this->actorEmail,
                'recordName'   => $this->recordName,
                'details'      => $this->details,
                'entityUrl'    => $this->entityUrl,
                'actionAt'     => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'emailSubject' => $this->emailSubject,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
