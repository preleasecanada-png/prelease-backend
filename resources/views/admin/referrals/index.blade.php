@extends('layouts.master')
@section('title')
    Referral Program
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="p-3"><h1>Referral Program</h1></div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Referrals</h6>
                    <h3>{{ $stats['total_referrals'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Completed</h6>
                    <h3>{{ $stats['completed'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Pending</h6>
                    <h3>{{ $stats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Paid</h6>
                    <h3>${{ number_format($stats['total_paid'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap border-bottom w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Referrer</th>
                                    <th>Referred User</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($referrals as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td>{{ $r->referrer->first_name ?? '' }} {{ $r->referrer->last_name ?? '' }}</td>
                                        <td>{{ $r->referred ? ($r->referred->first_name . ' ' . $r->referred->last_name) : '—' }}</td>
                                        <td><code>{{ $r->referral_code }}</code></td>
                                        <td>
                                            @if($r->status == 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($r->status == 'registered')
                                                <span class="badge bg-info">Registered</span>
                                            @elseif($r->status == 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($r->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $r->remuneration_amount ? '$' . number_format($r->remuneration_amount, 2) : '—' }}</td>
                                        <td>{{ $r->remuneration_paid ? 'Yes' : 'No' }}</td>
                                        <td>
                                            @if($r->status == 'registered')
                                                <form action="{{ route('referrals.complete', $r->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="number" name="remuneration_amount" step="0.01" min="0" placeholder="Amount" class="form-control form-control-sm d-inline-block" style="width:100px" required>
                                                    <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                                </form>
                                            @elseif($r->status == 'completed' && !$r->remuneration_paid)
                                                <form action="{{ route('referrals.pay', $r->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">Pay</button>
                                                </form>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center">No referrals found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $referrals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
