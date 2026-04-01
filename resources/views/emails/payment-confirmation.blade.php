@extends('emails.layout')

@section('header-subtitle', 'Payment Confirmation')

@section('content')
    <h1>Hi {{ $recipientName }},</h1>

    @if($recipientRole === 'renter')
        <p>Your payment for <span class="highlight">{{ $propertyTitle }}</span> has been confirmed.</p>
    @else
        <p>A rent payment has been received for your property <span class="highlight">{{ $propertyTitle }}</span>.</p>
    @endif

    <div class="info-box">
        <table width="100%" cellpadding="4" cellspacing="0">
            <tr><td style="color:#666;font-size:14px;">Reference</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $paymentReference }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Property</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $propertyTitle }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Rent Amount</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">${{ number_format($rentAmount, 2) }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Support Fee</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">${{ number_format($supportFee, 2) }}</td></tr>
            @if($insuranceFee > 0)
                <tr><td style="color:#666;font-size:14px;">Insurance Fee</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">${{ number_format($insuranceFee, 2) }}</td></tr>
            @endif
            <tr><td colspan="2" style="border-top:1px solid #ddd;"></td></tr>
            <tr><td style="color:#000;font-size:15px;font-weight:600;">Total</td><td style="color:#D80621;font-weight:700;font-size:15px;" align="right">${{ number_format($totalAmount, 2) }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Payment Method</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ ucfirst(str_replace('_', ' ', $paymentMethod ?? 'N/A')) }}</td></tr>
            <tr><td style="color:#666;font-size:14px;">Date</td><td style="color:#000;font-weight:600;font-size:14px;" align="right">{{ $paidAt }}</td></tr>
        </table>
    </div>

    @if($recipientRole === 'landlord')
        <p>The payout to your account will be processed within 2-3 business days.</p>
    @endif

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/payments" class="email-btn-dark">View Payments</a>
    </div>
@endsection
