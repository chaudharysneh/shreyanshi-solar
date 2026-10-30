@extends('emails.layouts.base')

@section('email_title', 'Meeting Scheduled – ' . $meetingTitle)

@section('header_label', 'CRM Activity')

@section('header_title')
    Meeting - {{ $meetingTitle }}
@endsection

@section('email_body')
    <p class="greeting">Hello {{ $staffName }},</p>

    <p>
        A meeting has been scheduled and assigned to you. Please review the details below and prepare accordingly.
    </p>

    <div class="content-box">
        <div class="content-box-title">Meeting Information</div>
        
        <div class="info-row">
            <div class="info-label">Title</div>
            <div class="info-value">{{ $meetingTitle }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Customer</div>
            <div class="info-value">{{ $customerName }}</div>
        </div>
        @if($customerPhone)
        <div class="info-row">
            <div class="info-label">Customer Phone</div>
            <div class="info-value"><a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a></div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Date &amp; Time</div>
            <div class="info-value"><strong style="color: #4f46e5;">{{ $scheduledAt }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Type</div>
            <div class="info-value"><span class="badge">{{ ucfirst($meetingType) }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Status</div>
            <div class="info-value"><span class="badge">{{ ucfirst($meetingStatus) }}</span></div>
        </div>
        @if($location)
        <div class="info-row">
            <div class="info-label">Location / Link</div>
            <div class="info-value">{{ $location }}</div>
        </div>
        @endif
    </div>

    @if($agenda)
    <p style="margin-top: 16px;"><strong>Agenda:</strong></p>
    <div style="background:#e0e7ff; border-radius: 6px; padding: 12px 16px; font-size: 14px; border-left: 3px solid #4f46e5; margin-bottom: 20px;">
        {{ $agenda }}
    </div>
    @endif

    <div style="font-size: 13px; opacity: 0.8; margin-top: 24px;">
        📌 <strong>Reminder:</strong> Please be punctual and prepared with all relevant solar project details for this customer.
    </div>

    <div class="btn-wrap">
        <a href="{{ $meetingUrl }}" class="btn-primary">View Meeting Details</a>
    </div>

@endsection
