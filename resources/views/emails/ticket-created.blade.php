@extends('emails.layouts.base')

@section('email_title', 'Support Ticket Created - ' . $ticket->ticket_no)

@section('header_title')
    New Support Ticket Created
@endsection

@section('email_body')
    <p class="greeting">Hello {{ $ticket->customer->name ?? 'there' }},</p>

    <p>A new support ticket has been created with the following details:</p>

    <div class="content-box">
        <div class="content-box-title">Ticket Details</div>
        
        <div class="info-row">
            <div class="info-label">Customer Name</div>
            <div class="info-value">{{ $ticket->customer->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Ticket Name</div>
            <div class="info-value">{{ $ticket->ticket_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Priority</div>
            <div class="info-value">
                <strong style="color: {{ match($ticket->priority) {
                    'Urgent' => '#dc2626',
                    'High'   => '#ea580c',
                    'Medium' => '#d97706',
                    'Low'    => '#059669',
                    default  => '#6b7280'
                } }};">{{ $ticket->priority }}</strong>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Current Status</div>
            <div class="info-value">{{ $ticket->status }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Created At</div>
            <div class="info-value">{{ $ticket->created_at?->format('d M, Y h:i A') }}</div>
        </div>
    </div>

    @if($ticket->description)
    <p style="margin-top: 16px;"><strong>Description:</strong></p>
    <div style="background:#e0e7ff; border-radius: 6px; padding: 12px 16px; font-size: 14px; border-left: 3px solid #4f46e5; margin-bottom: 20px;">
        {!! nl2br(e($ticket->description)) !!}
    </div>
    @endif

    <div style="font-size: 13.5px; opacity: 0.7; margin-top: 24px;">
        Our support team will review your ticket and get back to you shortly.
    </div>
@endsection