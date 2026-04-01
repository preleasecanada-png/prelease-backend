@extends('layouts.master')
@section('title')
    Support Tickets
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div><h1 class="mb-3">Support Tickets</h1></div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">All Priority</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <option value="account" {{ request('category') == 'account' ? 'selected' : '' }}>Account</option>
                        <option value="payment" {{ request('category') == 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="property" {{ request('category') == 'property' ? 'selected' : '' }}>Property</option>
                        <option value="application" {{ request('category') == 'application' ? 'selected' : '' }}>Application</option>
                        <option value="lease" {{ request('category') == 'lease' ? 'selected' : '' }}>Lease</option>
                        <option value="technical" {{ request('category') == 'technical' ? 'selected' : '' }}>Technical</option>
                        <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap border-bottom w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $t)
                                    <tr>
                                        <td>{{ $t->id }}</td>
                                        <td>{{ $t->user->first_name ?? '' }} {{ $t->user->last_name ?? '' }}</td>
                                        <td>{{ Str::limit($t->subject, 30) }}</td>
                                        <td>{{ ucfirst($t->category) }}</td>
                                        <td>
                                            @if($t->priority == 'urgent')
                                                <span class="badge bg-danger">Urgent</span>
                                            @elseif($t->priority == 'high')
                                                <span class="badge bg-warning">High</span>
                                            @elseif($t->priority == 'medium')
                                                <span class="badge bg-info">Medium</span>
                                            @else
                                                <span class="badge bg-secondary">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($t->status == 'open')
                                                <span class="badge bg-primary">Open</span>
                                            @elseif($t->status == 'in_progress')
                                                <span class="badge bg-info">In Progress</span>
                                            @elseif($t->status == 'resolved')
                                                <span class="badge bg-success">Resolved</span>
                                            @else
                                                <span class="badge bg-secondary">Closed</span>
                                            @endif
                                        </td>
                                        <td>{{ $t->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('support.show', $t->id) }}" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center">No support tickets found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
