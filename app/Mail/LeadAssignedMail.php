<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Lead $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Lead Assigned to You – ' . $this->lead->name,
        );
    }

    public function content(): Content
    {
        $lead       = $this->lead;
        $assignedBy = $lead->creator?->name ?? auth()->user()?->name ?? 'Admin';

        return new Content(
            view: 'emails.lead-assigned',
            with: [
                'staffName'   => $lead->assignedUser?->name ?? 'Staff',
                'leadName'    => $lead->name,
                'leadEmail'   => $lead->email,
                'leadPhone'   => $lead->phone,
                'leadCompany' => $lead->company_name,
                'leadSource'  => $lead->leadSource?->name ?? $lead->source ?? null,
                'leadStatus'  => $lead->status ?? 'new',
                'leadAddress' => $lead->address,
                'leadNotes'   => $lead->notes,
                'assignedBy'  => $assignedBy,
                'assignedAt'  => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'leadUrl'     => url('/crm/leads/' . $lead->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
