<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $plainPassword;

    public function __construct(User $user, string $plainPassword)
    {
        $this->user          = $user;
        $this->plainPassword = $plainPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Rising Green Energy – Your Account is Ready',
        );
    }

    public function content(): Content
    {
        $role = $this->user->roles()->pluck('name')
            ->map(fn($r) => ucfirst($r))->implode(', ') ?: 'Staff';

        return new Content(
            view: 'emails.staff-created',
            with: [
                'userName'      => $this->user->name,
                'userEmail'     => $this->user->email,
                'userPhone'     => $this->user->phone ?? '—',
                'userRole'      => $role,
                'plainPassword' => $this->plainPassword,
                'loginUrl'      => url('/login'),
                'createdAt'     => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
