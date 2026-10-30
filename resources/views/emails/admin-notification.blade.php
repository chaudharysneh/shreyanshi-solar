@extends('emails.layouts.base')

@section('email_title', $emailSubject ?? 'Staff Activity Notification')

@section('header_title')
    {{ $emailSubject }}
@endsection

@section('email_body')
    <p class="greeting">Hello Admin,</p>

    <p>
        A record has been {{ strtolower($actionLabel) }} in the CRM.
    </p>
    
    <div class="content-box">
        <div class="content-box-title">Record Details</div>
        
        <div class="info-row">
            <div class="info-label">Module</div>
            <div class="info-value">{{ $moduleLabel }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Record</div>
            <div class="info-value">{{ $recordName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">
                @if(strtolower($actionLabel) === 'created')
                    Created By
                @elseif(strtolower($actionLabel) === 'deleted')
                    Deleted By
                @else
                    Updated By
                @endif
            </div>
            <div class="info-value">{{ $actorName }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">
                @if(strtolower($actionLabel) === 'created')
                    Created On
                @elseif(strtolower($actionLabel) === 'deleted')
                    Deleted On
                @else
                    Updated On
                @endif
            </div>
            <div class="info-value">{{ $actionAt }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">CRM Link</div>
            <div class="info-value"><a href="{{ url('/') }}">{{ url('/') }}</a></div>
        </div>
        
        @if(!empty($details) && isset($details['Comment']))
        <div class="info-row" style="background-color: rgba(15, 23, 42, 0.05);">
            <div class="info-label">Comment</div>
            <div class="info-value" style="font-weight: 500;">{{ $details['Comment'] }}</div>
        </div>
        @endif
    </div>

    <p style="margin-top: 20px;">
        {{ $actorName }} has {{ strtolower($actionLabel) }} a {{ strtolower($moduleLabel) }}: {{ $recordName }}.
    </p>
    <p>
        Please log in to CRM for complete details.
    </p>

    @if($entityUrl)
    <div class="btn-wrap">
        <a href="{{ $entityUrl }}" class="btn-primary">View in CRM</a>
    </div>
    @endif

@endsection
