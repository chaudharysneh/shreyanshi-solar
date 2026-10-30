<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstimateViewMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Estimate $estimate;

    public function __construct(Estimate $estimate)
    {
        $this->estimate = $estimate;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Solar Estimate is Ready – ' . ($this->estimate->estimate_no ?? 'EST-' . $this->estimate->estimate_id),
        );
    }

    public function content(): Content
    {
        $estimate   = $this->estimate;
        $estimateNo = $estimate->estimate_no ?? ('EST-' . $estimate->estimate_id);

        return new Content(
            view: 'emails.estimate-view',
            with: [
                'customerName'  => $estimate->customer?->name ?? 'Valued Customer',
                'estimateNo'    => $estimateNo,
                'estimateName'  => $estimate->estimate_name ?? '—',
                'estimateType'  => $estimate->type ?? null,
                'quantity'      => $estimate->quantity ?? null,
                'totalAmount'   => $estimate->price ?? null,
                'preparedBy'    => $estimate->creator?->name ?? 'Rising Green Energy Team',
                'estimateDate'  => $estimate->estimate_date
                    ? \Carbon\Carbon::parse($estimate->estimate_date)->format('d M Y')
                    : now()->timezone('Asia/Kolkata')->format('d M Y'),
                'estimateUrl'   => url('/crm/estimates/' . $estimate->estimate_id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
