@extends('emails.layouts.base')

@section('email_title', 'Your Solar Project is Complete – Rising Green Energy')

@section('header_label', 'Project Status')

@section('header_title')
    Project Completed Successfully!
@endsection

@section('email_body')
    <p class="greeting">Dear {{ $customerName }},</p>

    <p>
        We are thrilled to announce that your solar energy project has been
        successfully completed! Welcome to the world of clean, sustainable solar energy.
    </p>

    <div class="content-box">
        <div class="content-box-title">Project Details</div>
        
        <div class="info-row">
            <div class="info-label">Project Name</div>
            <div class="info-value">{{ $projectName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Customer</div>
            <div class="info-value">{{ $customerName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status</div>
            <div class="info-value"><span class="badge">Completed</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Completed On</div>
            <div class="info-value"><strong style="color: #4f46e5;">{{ $completedAt }}</strong></div>
        </div>
        @if($projectDescription)
        <div class="info-row">
            <div class="info-label">Description</div>
            <div class="info-value">{{ $projectDescription }}</div>
        </div>
        @endif
    </div>

    <div style="background-color: #e0e7ff; border-radius: 8px; padding: 24px; margin: 24px 0; text-align: center;">
        <p style="font-size: 18px; font-weight: 700; color: #3730a3; margin-bottom: 8px;">You are now Solar Powered!</p>
        <p style="font-size: 13.5px; color: #1e1b4b; opacity: 0.8; line-height: 1.6;">
            Congratulations on taking this important step towards clean energy.<br>
            Every unit of solar energy you generate contributes to a greener planet.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 20px 0;">
        <div style="text-align: center; padding: 16px; border-radius: 10px; border: 1px solid #e5e7eb;">
            <div style="font-size: 24px; margin-bottom: 6px;">⚡</div>
            <div style="font-size: 12px; font-weight: 600; color: #4f46e5;">Clean Energy</div>
            <div style="font-size: 11px; opacity: 0.7;">Every day</div>
        </div>
        <div style="text-align: center; padding: 16px; border-radius: 10px; border: 1px solid #e5e7eb;">
            <div style="font-size: 24px; margin-bottom: 6px;">💰</div>
            <div style="font-size: 12px; font-weight: 600; color: #1a7a3c;">Bill Savings</div>
            <div style="font-size: 11px; color: #6b7280;">Monthly</div>
        </div>
        <div style="text-align: center; padding: 16px; border-radius: 10px; border: 1px solid #e5e7eb;">
            <div style="font-size: 24px; margin-bottom: 6px;">🌍</div>
            <div style="font-size: 12px; font-weight: 600; color: #4f46e5;">CO₂ Reduced</div>
            <div style="font-size: 11px; opacity: 0.7;">Yearly</div>
        </div>
    </div>

    <p style="font-size: 14.5px;">
        Our after-sales support team is always here for you. If you experience any issues or have questions
        about your solar system, please do not hesitate to reach out to us.
    </p>

    <div class="btn-wrap">
        <a href="{{ $projectUrl }}" class="btn-primary">View Project Details</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 15px; font-weight: 600; color: #3730a3; text-align: center; margin-bottom: 8px;">
        Thank You for Choosing Rising Green Energy!
    </p>
    <p style="font-size: 13.5px; opacity: 0.7; text-align: center;">
        We truly appreciate your trust in us. Your satisfaction is our greatest achievement.
    </p>
@endsection
