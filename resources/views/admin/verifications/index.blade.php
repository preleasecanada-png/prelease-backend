@extends('layouts.master')
@section('title')
    User Verifications
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div>
                <h1 class="mb-3">User Verifications</h1>
            </div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                        <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="identity" {{ request('type') == 'identity' ? 'selected' : '' }}>Identity</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income</option>
                        <option value="address" {{ request('type') == 'address' ? 'selected' : '' }}>Address</option>
                        <option value="landlord_ownership" {{ request('type') == 'landlord_ownership' ? 'selected' : '' }}>Landlord Ownership</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap border-bottom w-100">
                            <thead class="border-top">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Type</th>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($verifications as $v)
                                    <tr>
                                        <td>{{ $v->id }}</td>
                                        <td>{{ $v->user->first_name ?? '' }} {{ $v->user->last_name ?? '' }}</td>
                                        <td>{{ ucfirst($v->user->role ?? '') }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $v->verification_type)) }}</td>
                                        <td>{{ $v->document_type }}</td>
                                        <td>
                                            @if($v->status == 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($v->status == 'under_review')
                                                <span class="badge bg-info">Under Review</span>
                                            @elseif($v->status == 'verified')
                                                <span class="badge bg-success">Verified</span>
                                            @else
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td>{{ $v->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('verifications.show', $v->id) }}" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center">No verifications found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $verifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
