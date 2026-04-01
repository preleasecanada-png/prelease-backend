@extends('emails.layout')

@section('header-subtitle', 'Lease Reminder')

@section('content')
    <h1>Hi {{ $recipientName }},</h1>

    @if($reminderType === 'signing')
        <p>Your lease agreement for <span class="highlight">{{ $propertyTitle }}</span> is awaiting your signature.</p>
    @elseif($reminderType === 'expiring')
        <p>Your lease for <span class="highlight">{{ $propertyTitle }}</span> is expiring soon.</p>
    @elseif($reminderType === 'active')
        <p>Your lease agreement for <span class="highlight">{{ $propertyTitle }}</span> is now active.</p>
    @endif

    <div class="info-box">
        <table width="100%" cellpadding="4" cellspacing="0">
            <tr><td style="color:#666;font-size:14px;">Property</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $propertyTitle }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Lease Type</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ str_replace('_', ' ', ucfirst($leaseType)) }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Start Date</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $startDate }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">End Date</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $endDate }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Monthly Rent</td><td style="color:#D80621;font-weight:700;font-size:14px;" align="right">${{ number_format($monthlyRent, 2) }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Status</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">
                @if($reminderType === 'signing')
                    <span class="status-badge status-pending">Pending Signature</span>
                @elseif($reminderType === 'expiring')
                    <span class="status-badge status-rejected">Expiring Soon</span>
                @elseif($reminderType === 'active')
                    <span class="status-badge status-approved">Active</span>
                @endif
            </td></tr>
        </table>
    </div>

    @if($reminderType === 'signing')
        <p>Please sign your lease agreement as soon as possible to secure your rental.</p>
    @elseif($reminderType === 'expiring')
        <p>Your lease will expire on <strong>{{ $endDate }}</strong>. Please contact your {{ $recipientRole === 'renter' ? 'landlord' : 'renter' }} to discuss renewal options.</p>
    @endif

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/leases" class="email-btn-dark">View Lease</a>
    </div>
@endsection
