@extends('emails.layouts.base')

@section('email_title', 'Welcome to Rising Green Energy – ' . $customerName)

@section('header_title')
    Welcome to Rising Green Energy!
@endsection

@section('email_body')
    <p class="greeting">Dear {{ $customerName }},</p>

    <p>
        We are delighted to welcome you to Rising Green Energy — your partner for clean, 
        sustainable solar energy solutions. Your customer account has been successfully created.
    </p>

    <div class="content-box">
        <div class="content-box-title">Account Details</div>
        
        <div class="info-row">
            <div class="info-label">Name</div>
            <div class="info-value">{{ $customerName }}</div>
        </div>
        @if($customerEmail)
        <div class="info-row">
            <div class="info-label">Email</div>
            <div class="info-value">{{ $customerEmail }}</div>
        </div>
        @endif
        @if($customerPhone)
        <div class="info-row">
            <div class="info-label">Phone</div>
            <div class="info-value">{{ $customerPhone }}</div>
        </div>
        @endif
        @if($customerCompany)
        <div class="info-row">
            <div class="info-label">Company</div>
            <div class="info-value">{{ $customerCompany }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Joined On</div>
            <div class="info-value">{{ $createdAt }}</div>
        </div>
    </div>

    <div style="background-color: #e0e7ff; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center;">
        <p style="font-size: 15px; font-weight: 600; color: #3730a3; margin-bottom: 8px;">Why Choose Us?</p>
        <div style="font-size: 13.5px; color: #1e1b4b; opacity: 0.8;">
            Premium Solar Panels • Expert Installation • After-Sales Support
        </div>
    </div>

    <p style="font-size: 14.5px;">
        Our team will be in touch shortly to understand your solar energy requirements and provide you with the best solutions.
    </p>

@endsection
