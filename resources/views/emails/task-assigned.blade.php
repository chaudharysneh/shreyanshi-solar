@extends('emails.layouts.base')

@section('email_title', 'New Task Assigned')

@section('header_title', 'New Task Assigned')

@section('email_body')
    <p class="greeting">Hello {{ $staffName }},</p>

    <p>
        A new task has been assigned to you by <strong>{{ $assignedBy }}</strong>.
    </p>
    
    <div class="content-box">
        <div class="content-box-title">Task Details</div>
        
        <div class="info-row">
            <div class="info-label">Title</div>
            <div class="info-value">{{ $taskTitle }}</div>
        </div>
        @if(!empty($customerName) && $customerName !== 'N/A')
        <div class="info-row">
            <div class="info-label">Customer</div>
            <div class="info-value">{{ $customerName }}</div>
        </div>
        @endif
        @if(!empty($projectName) && $projectName !== 'N/A')
        <div class="info-row">
            <div class="info-label">Project</div>
            <div class="info-value">{{ $projectName }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Priority</div>
            <div class="info-value">{{ ucfirst($taskPriority) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status</div>
            <div class="info-value">{{ ucwords(str_replace('_', ' ', $taskStatus)) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Due Date</div>
            <div class="info-value">{{ $dueDate }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Assigned On</div>
            <div class="info-value">{{ $assignedAt }}</div>
        </div>

        @if(!empty($taskDesc))
        <div class="info-row">
            <div class="info-label">Description</div>
            <div class="info-value">{{ $taskDesc }}</div>
        </div>
        @endif
    </div>

    <p style="margin-top: 20px;">
        Please log in to the CRM to view complete task details and start working on it.
    </p>

    <div class="btn-wrap">
        <a href="{{ $taskUrl }}" class="btn-primary">View Task in CRM</a>
    </div>
@endsection
