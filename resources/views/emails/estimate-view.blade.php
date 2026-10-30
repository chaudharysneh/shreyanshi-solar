@extends('emails.layouts.base')

@section('email_title', 'Your Solar Estimate is Ready – ' . $estimateNo)

@section('header_title')
    Your Solar Estimate is Ready!
@endsection

@section('email_body')
    <p class="greeting">Dear {{ $customerName }},</p>

    <p>
        We are pleased to inform you that your solar energy estimate has been prepared by our team.
        Please review the details below and view the full estimate using the link provided.
    </p>

    <div class="content-box">
        <div class="content-box-title">Estimate Details</div>
        
        <div class="info-row">
            <div class="info-label">Estimate No</div>
            <div class="info-value">{{ $estimateNo }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Estimate Name</div>
            <div class="info-value">{{ $estimateName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Customer Name</div>
            <div class="info-value">{{ $customerName }}</div>
        </div>
        @if($estimateType)
        <div class="info-row">
            <div class="info-label">System Type</div>
            <div class="info-value"><span class="badge">{{ ucwords($estimateType) }}</span></div>
        </div>
        @endif
        @if($quantity)
        <div class="info-row">
            <div class="info-label">System Size</div>
            <div class="info-value">{{ $quantity }} kW</div>
        </div>
        @endif
        @if($totalAmount)
        <div class="info-row">
            <div class="info-label">Estimated Amount</div>
            <div class="info-value"><strong style="color: #4f46e5;">₹ {{ number_format($totalAmount, 2) }}</strong></div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Prepared By</div>
            <div class="info-value">{{ $preparedBy }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date</div>
            <div class="info-value">{{ $estimateDate }}</div>
        </div>
    </div>

    <div style="background-color: #e0e7ff; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center;">
        <p style="font-size: 15px; font-weight: 600; color: #3730a3; margin-bottom: 8px;">Your Solar Investment Benefits</p>
        <div style="font-size: 13.5px; color: #1e1b4b; opacity: 0.8; line-height: 1.8;">
            Reduce electricity bills significantly • Reduce carbon footprint • Government subsidies available
        </div>
    </div>

    <div class="btn-wrap">
        <a href="{{ $estimateUrl }}" class="btn-primary">View Full Estimate</a>
    </div>

    <div style="font-size: 13px; opacity: 0.8; margin-top: 24px;">
        📌 <strong>Note:</strong> This estimate is subject to final site inspection and confirmation.
        Our representative will contact you shortly to discuss further.
    </div>

@endsection
