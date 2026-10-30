@extends('emails.layouts.base')

@section('email_title', 'New Lead Assigned to You – ' . $leadName)

@section('header_label', 'CRM Activity')

@section('header_title')
    Lead Assigned - {{ $leadName }}
@endsection

@section('email_body')
    <p class="greeting">Hello {{ $staffName }},</p>

    <p>
        A new lead has been assigned to you on the Rising Green Energy CRM.
        Please review the details below and follow up at the earliest.
    </p>

    <div class="content-box">
        <div class="content-box-title">Lead Details</div>
        
        <div class="info-row">
            <div class="info-label">Name</div>
            <div class="info-value">{{ $leadName }}</div>
        </div>
        @if($leadEmail)
        <div class="info-row">
            <div class="info-label">Email</div>
            <div class="info-value"><a href="mailto:{{ $leadEmail }}">{{ $leadEmail }}</a></div>
        </div>
        @endif
        @if($leadPhone)
        <div class="info-row">
            <div class="info-label">Phone</div>
            <div class="info-value"><a href="tel:{{ $leadPhone }}">{{ $leadPhone }}</a></div>
        </div>
        @endif
        @if($leadCompany)
        <div class="info-row">
            <div class="info-label">Company</div>
            <div class="info-value">{{ $leadCompany }}</div>
        </div>
        @endif
        @if($leadSource)
        <div class="info-row">
            <div class="info-label">Source</div>
            <div class="info-value">{{ $leadSource }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Status</div>
            <div class="info-value"><span class="badge">{{ ucwords(str_replace('_', ' ', $leadStatus)) }}</span></div>
        </div>
        @if($leadAddress)
        <div class="info-row">
            <div class="info-label">Address</div>
            <div class="info-value">{{ $leadAddress }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Assigned By</div>
            <div class="info-value">{{ $assignedBy }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Assigned On</div>
            <div class="info-value">{{ $assignedAt }}</div>
        </div>
    </div>

    @if($leadNotes)
    <p style="margin-top: 16px;"><strong>Notes:</strong></p>
    <div style="background:#e0e7ff; border-radius: 6px; padding: 12px 16px; font-size: 14px; border-left: 3px solid #4f46e5; margin-bottom: 20px;">
        {{ $leadNotes }}
    </div>
    @endif

    <div class="btn-wrap">
        <a href="{{ $leadUrl }}" class="btn-primary">View Lead Details</a>
    </div>

@endsection
