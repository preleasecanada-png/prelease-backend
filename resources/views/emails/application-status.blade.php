@extends('emails.layout')

@section('header-subtitle', 'Application Update')

@section('content')
    <h1>Hi {{ $recipientName }},</h1>

    @if($recipientRole === 'renter')
        <p>Your rental application for <span class="highlight">{{ $propertyTitle }}</span> has been updated.</p>
    @else
        <p>A rental application for your property <span class="highlight">{{ $propertyTitle }}</span> has been updated.</p>
    @endif

    <div style="text-align: center; margin: 20px 0;">
        @switch($status)
            @case('submitted')
                <span class="status-badge status-pending">Submitted</span>
                @break
            @case('under_review')
                <span class="status-badge status-pending">Under Review</span>
                @break
            @case('approved')
                <span class="status-badge status-approved">Approved</span>
                @break
            @case('rejected')
                <span class="status-badge status-rejected">Rejected</span>
                @break
            @case('withdrawn')
                <span class="status-badge status-rejected">Withdrawn</span>
                @break
            @default
                <span class="status-badge status-pending">{{ ucfirst($status) }}</span>
        @endswitch
    </div>

    <div class="info-box">
        <table width="100%" cellpadding="4" cellspacing="0">
            <tr><td style="color:#666;font-size:14px;">Property</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $propertyTitle }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Application ID</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">#{{ $applicationId }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Status</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ ucfirst(str_replace('_', ' ', $status)) }}</td></tr>
            @if($status === 'approved')
                <tr><td style="color:#666;font-size:14px;">Next Step</td><td style="color:#2e7d32;font-weight:600;font-size:14px;" align="right">Lease Agreement</td></tr>
            @endif
        </table>
    </div>

    @if($status === 'approved')
        <p>Congratulations! The next step is to review and sign your lease agreement.</p>
    @elseif($status === 'rejected' && $rejectionReason)
        <p><strong>Reason:</strong> {{ $rejectionReason }}</p>
    @endif

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/applications" class="email-btn-dark">View Application</a>
    </div>
@endsection
