<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Customer $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Rising Green Energy – ' . $this->customer->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-welcome',
            with: [
                'customerName'    => $this->customer->name,
                'customerEmail'   => $this->customer->email,
                'customerPhone'   => $this->customer->phone,
                'customerCompany' => $this->customer->company_name,
                'createdAt'       => now()->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
