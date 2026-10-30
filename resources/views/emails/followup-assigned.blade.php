@extends('emails.layouts.base')

@section('email_title', 'New Follow-Up Assigned')

@section('header_title')
    Follow-Up Assigned - {{ $leadName }}
@endsection

@section('email_body')
    <p class="greeting">Hello {{ $staffName }},</p>

    <p>
        A new follow-up has been assigned to you by <strong>{{ $assignedBy }}</strong>.
    </p>
    
    <div class="content-box">
        <div class="content-box-title">Follow-Up Details</div>
        
        <div class="info-row">
            <div class="info-label">Lead</div>
            <div class="info-value">{{ $leadName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Purpose</div>
            <div class="info-value">{{ $purpose }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Priority</div>
            <div class="info-value">{{ ucfirst($priority) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status</div>
            <div class="info-value">{{ ucfirst($status) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Follow-Up Time</div>
            <div class="info-value">{{ $followUpAt }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Assigned On</div>
            <div class="info-value">{{ $assignedAt }}</div>
        </div>
    </div>

    <p style="margin-top: 20px;">
        Please log in to the CRM to view the lead and start working on the follow-up.
    </p>

    <div class="btn-wrap">
        <a href="{{ $followUpUrl }}" class="btn-primary">View Follow-Up in CRM</a>
    </div>
@endsection
