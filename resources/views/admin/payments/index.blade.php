@extends('layouts.master')
@section('title')
    Payments
@endsection
@section('content')
    <div class="row row-sm mt-5">
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
                    <h6 class="text-muted">Support Fees</h6>
                    <h3>${{ number_format($stats['total_support_fees'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Commission</h6>
                    <h3>${{ number_format($stats['total_commission'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Pending Payouts</h6>
                    <h3>${{ number_format($stats['pending_payouts'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-sm">
        <div class="d-flex justify-content-between p-3">
            <h2>All Payments</h2>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
                <select name="payout_status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Payouts</option>
                    <option value="pending" {{ request('payout_status') == 'pending' ? 'selected' : '' }}>Payout Pending</option>
                    <option value="completed" {{ request('payout_status') == 'completed' ? 'selected' : '' }}>Payout Done</option>
                </select>
            </form>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap border-bottom w-100">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Renter</th>
                                    <th>Landlord</th>
                                    <th>Property</th>
                                    <th>Rent</th>
                                    <th>Fees</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payout</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $p)
                                    <tr>
                                        <td>{{ $p->payment_reference }}</td>
                                        <td>{{ $p->renter->first_name ?? '' }} {{ $p->renter->last_name ?? '' }}</td>
                                        <td>{{ $p->landlord->first_name ?? '' }} {{ $p->landlord->last_name ?? '' }}</td>
                                        <td>{{ Str::limit($p->property->title ?? '', 20) }}</td>
                                        <td>${{ number_format($p->rent_amount, 2) }}</td>
                                        <td>${{ number_format($p->support_fee + $p->commission_fee, 2) }}</td>
                                        <td><strong>${{ number_format($p->total_amount, 2) }}</strong></td>
                                        <td>
                                            @if($p->status == 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($p->status == 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($p->status == 'failed')
                                                <span class="badge bg-danger">Failed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($p->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($p->landlord_payout_status == 'completed')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif($p->landlord_payout_status == 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($p->landlord_payout_status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('payments.show', $p->id) }}" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center">No payments found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
