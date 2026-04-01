@extends('layouts.master')
@section('title')
    Payment Details
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3>Payment {{ $payment->payment_reference }}</h3></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Renter:</strong> {{ $payment->renter->first_name ?? '' }} {{ $payment->renter->last_name ?? '' }}</div>
                        <div class="col-md-6"><strong>Landlord:</strong> {{ $payment->landlord->first_name ?? '' }} {{ $payment->landlord->last_name ?? '' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Property:</strong> {{ $payment->property->title ?? '' }}</div>
                        <div class="col-md-6"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'N/A')) }}</div>
                    </div>
                    <hr>
                    <h5>Payment Breakdown</h5>
                    <table class="table">
                        <tr><td>Rent Amount</td><td class="text-end">${{ number_format($payment->rent_amount, 2) }}</td></tr>
                        <tr><td>Support Fee ($100/month)</td><td class="text-end">${{ number_format($payment->support_fee, 2) }}</td></tr>
                        <tr><td>Commission Fee (5%)</td><td class="text-end">${{ number_format($payment->commission_fee, 2) }}</td></tr>
                        <tr><td>Insurance Fee</td><td class="text-end">${{ number_format($payment->insurance_fee, 2) }}</td></tr>
                        <tr class="fw-bold"><td>Total</td><td class="text-end">${{ number_format($payment->total_amount, 2) }}</td></tr>
                    </table>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Payment Status:</strong>
                            <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($payment->status) }}</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Landlord Payout:</strong>
                            <span class="badge bg-{{ $payment->landlord_payout_status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($payment->landlord_payout_status) }}</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Insurance Payout:</strong>
                            <span class="badge bg-{{ $payment->insurance_payout_status == 'completed' ? 'success' : ($payment->insurance_payout_status == 'not_applicable' ? 'secondary' : 'warning') }}">{{ ucfirst(str_replace('_', ' ', $payment->insurance_payout_status)) }}</span>
                        </div>
                    </div>
                    @if($payment->paid_at)
                        <p><strong>Paid At:</strong> {{ $payment->paid_at->format('M d, Y H:i') }}</p>
                    @endif
                    @if($payment->transaction_id)
                        <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($payment->status == 'completed' && $payment->landlord_payout_status == 'pending')
                <div class="card mb-3">
                    <div class="card-header"><h5>Landlord Payout</h5></div>
                    <div class="card-body">
                        <p>Amount: <strong>${{ number_format($payment->landlord_payout_amount, 2) }}</strong></p>
                        <form action="{{ route('payments.landlord_payout', $payment->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Process Landlord Payout</button>
                        </form>
                    </div>
                </div>
            @endif
            @if($payment->status == 'completed' && $payment->insurance_payout_status == 'pending')
                <div class="card">
                    <div class="card-header"><h5>Insurance Payout</h5></div>
                    <div class="card-body">
                        <p>Amount: <strong>${{ number_format($payment->insurance_payout_amount, 2) }}</strong></p>
                        <form action="{{ route('payments.insurance_payout', $payment->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-info w-100">Process Insurance Payout</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
