<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingStaffMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Meeting $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Meeting Scheduled – ' . $this->meeting->title,
        );
    }

    public function content(): Content
    {
        $meeting = $this->meeting;

        return new Content(
            view: 'emails.meeting-staff',
            with: [
                'staffName'     => $meeting->assignedUser?->name ?? 'Staff',
                'meetingTitle'  => $meeting->title,
                'customerName'  => $meeting->customer?->name ?? '—',
                'customerPhone' => $meeting->customer?->phone ?? null,
                'scheduledAt'   => $meeting->scheduled_at
                    ? $meeting->scheduled_at->timezone('Asia/Kolkata')->format('D, d M Y \a\t h:i A')
                    : '—',
                'meetingType'   => $meeting->meeting_type ?? 'General',
                'meetingStatus' => $meeting->status ?? 'Scheduled',
                'location'      => $meeting->address ?? null,
                'agenda'        => $meeting->agenda ?? null,
                'meetingUrl'    => url('/crm/meetings/' . $meeting->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
