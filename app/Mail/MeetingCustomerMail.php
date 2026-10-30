<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingCustomerMail extends Mailable implements ShouldQueue
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
            subject: 'Your Meeting is Confirmed – ' . ($this->meeting->scheduled_at
                ? $this->meeting->scheduled_at->timezone('Asia/Kolkata')->format('d M Y')
                : 'Rising Green Energy'),
        );
    }

    public function content(): Content
    {
        $meeting = $this->meeting;

        return new Content(
            view: 'emails.meeting-customer',
            with: [
                'customerName' => $meeting->customer?->name ?? 'Valued Customer',
                'meetingTitle' => $meeting->title,
                'scheduledAt'  => $meeting->scheduled_at
                    ? $meeting->scheduled_at->timezone('Asia/Kolkata')->format('D, d M Y \a\t h:i A')
                    : '—',
                'meetingType'  => $meeting->meeting_type ?? 'General',
                'location'     => $meeting->address ?? null,
                'staffName'    => $meeting->assignedUser?->name ?? 'Our Representative',
                'agenda'       => $meeting->agenda ?? null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
