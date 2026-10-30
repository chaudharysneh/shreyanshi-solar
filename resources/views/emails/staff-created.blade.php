@extends('emails.layouts.base')

@section('email_title', 'Welcome to Rising Green Energy')

@section('header_title')
    Welcome to the Team!
@endsection

@section('email_body')
    <p class="greeting">Hello {{ $userName }},</p>

    <p>
        Your staff account has been successfully created on the Rising Green Energy CRM.
        You can now log in and start managing leads, customers, estimates, and more.
    </p>

    <div class="content-box">
        <div class="content-box-title">Account Details &amp; Login Credentials</div>
        
        <div class="info-row">
            <div class="info-label">Name</div>
            <div class="info-value">{{ $userName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email (ID)</div>
            <div class="info-value"><a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></div>
        </div>
        <div class="info-row">
            <div class="info-label">Created On</div>
            <div class="info-value">{{ $createdAt }}</div>
        </div>
        
        <!-- Login Credentials Section -->
        <div class="info-row" style="background-color: rgba(79, 70, 229, 0.03); border-top: 2px dashed #e5e7eb;">
            <div class="info-label" style="color: #4f46e5; padding-top: 20px;">Login URL</div>
            <div class="info-value" style="padding-top: 20px;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></div>
        </div>
        <div class="info-row" style="background-color: rgba(79, 70, 229, 0.03);">
            <div class="info-label">Password</div>
            <div class="info-value"><strong>{{ $plainPassword }}</strong></div>
        </div>
    </div>

@endsection
