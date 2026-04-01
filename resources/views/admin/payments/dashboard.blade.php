@extends('layouts.master')
@section('title')
    Financial Dashboard
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="p-3"><h1>Financial Dashboard</h1></div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Payments</h6>
                    <h3>{{ $stats['total_payments'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Completed</h6>
                    <h3>{{ $stats['completed_payments'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3>${{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Prelease Earnings</h6>
                    <h3>${{ number_format($stats['prelease_earnings'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Pending Payments</h6>
                    <h3>{{ $stats['pending_payments'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Pending Landlord Payouts</h6>
                    <h3>{{ $stats['pending_landlord_payouts'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Active Leases</h6>
                    <h3>{{ $stats['active_leases'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-sm mt-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header"><h4>Recent Payments</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Renter</th>
                                    <th>Property</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayments as $p)
                                    <tr>
                                        <td><a href="{{ route('payments.show', $p->id) }}">{{ $p->payment_reference }}</a></td>
                                        <td>{{ $p->renter->first_name ?? '' }} {{ $p->renter->last_name ?? '' }}</td>
                                        <td>{{ Str::limit($p->property->title ?? '', 25) }}</td>
                                        <td>${{ number_format($p->total_amount, 2) }}</td>
                                        <td><span class="badge bg-{{ $p->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($p->status) }}</span></td>
                                        <td>{{ $p->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
