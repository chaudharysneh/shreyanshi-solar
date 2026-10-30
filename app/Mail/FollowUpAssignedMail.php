<?php

namespace App\Mail;

use App\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public FollowUp $followUp;

    public function __construct(FollowUp $followUp)
    {
        $this->followUp = $followUp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Follow-Up Assigned to You – ' . ($this->followUp->lead?->name ?? 'Lead'),
        );
    }

    public function content(): Content
    {
        $followUp   = $this->followUp;
        $assignedBy = auth()->user()?->name ?? 'System / Admin';

        return new Content(
            view: 'emails.followup-assigned',
            with: [
                'staffName'   => $followUp->assignedUser?->name ?? 'Staff',
                'leadName'    => $followUp->lead?->name ?? 'Unknown Lead',
                'purpose'     => $followUp->purpose,
                'status'      => $followUp->status,
                'priority'    => $followUp->priority,
                'followUpAt'  => $followUp->follow_up_at ? \Carbon\Carbon::parse($followUp->follow_up_at)->format('d M Y, h:i A') : 'N/A',
                'assignedBy'  => $assignedBy,
                'assignedAt'  => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'followUpUrl' => url('/crm/leads/' . $followUp->lead_id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
