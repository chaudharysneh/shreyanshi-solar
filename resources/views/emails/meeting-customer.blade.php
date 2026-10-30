@extends('emails.layouts.base')

@section('email_title', 'Your Meeting is Scheduled – Rising Green Energy')

@section('header_title')
    Your Meeting is Confirmed!
@endsection

@section('email_body')
    <p class="greeting">Dear {{ $customerName }},</p>

    <p>
        Great news! A meeting has been scheduled for you with our solar energy expert.
        We look forward to discussing your solar requirements in detail.
    </p>

    <div class="content-box">
        <div class="content-box-title">Meeting Information</div>
        
        <div class="info-row">
            <div class="info-label">Title</div>
            <div class="info-value">{{ $meetingTitle }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date &amp; Time</div>
            <div class="info-value"><strong style="color: #4f46e5;">{{ $scheduledAt }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Type</div>
            <div class="info-value"><span class="badge">{{ ucfirst($meetingType) }}</span></div>
        </div>
        @if($location)
        <div class="info-row">
            <div class="info-label">Location / Link</div>
            <div class="info-value">{{ $location }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Our Representative</div>
            <div class="info-value">{{ $staffName }}</div>
        </div>
    </div>

    @if($agenda)
    <p style="margin-top: 16px;"><strong>Meeting Agenda:</strong></p>
    <div style="background:#e0e7ff; border-radius: 6px; padding: 12px 16px; font-size: 14px; border-left: 3px solid #4f46e5; margin-bottom: 20px;">
        {{ $agenda }}
    </div>
    @endif

    <div style="background-color: #e0e7ff; border-radius: 8px; padding: 20px; margin: 24px 0;">
        <p style="font-size: 15px; font-weight: 600; color: #3730a3; margin-bottom: 8px;">What to Expect:</p>
        <ul style="font-size: 13.5px; opacity: 0.8; padding-left: 20px; line-height: 1.8;">
            <li>Discussion of your energy needs and consumption</li>
            <li>Site assessment details (if applicable)</li>
            <li>Solar system sizing and recommendations</li>
            <li>Subsidy &amp; financing options overview</li>
        </ul>
    </div>

    <p style="font-size: 14.5px;">
        If you need to reschedule or have any questions before the meeting, please don't hesitate to contact us.
    </p>

@endsection
