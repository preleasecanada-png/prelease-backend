@extends('layouts.master')
@section('title')
    Rental Applications
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div><h1 class="mb-3">Rental Applications</h1></div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="withdrawn" {{ request('status') == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
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
                                    <th>Renter</th>
                                    <th>Landlord</th>
                                    <th>Property</th>
                                    <th>Lease Duration</th>
                                    <th>Income</th>
                                    <th>Documents</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                    <tr>
                                        <td>{{ $app->id }}</td>
                                        <td>{{ $app->renter->first_name ?? '' }} {{ $app->renter->last_name ?? '' }}</td>
                                        <td>{{ $app->landlord->first_name ?? '' }} {{ $app->landlord->last_name ?? '' }}</td>
                                        <td>{{ Str::limit($app->property->title ?? '', 20) }}</td>
                                        <td>{{ str_replace('_', ' ', $app->desired_lease_duration) }}</td>
                                        <td>${{ number_format($app->monthly_income, 2) }}</td>
                                        <td>{{ $app->documents->count() }}</td>
                                        <td>
                                            @if($app->status == 'submitted')
                                                <span class="badge bg-primary">Submitted</span>
                                            @elseif($app->status == 'under_review')
                                                <span class="badge bg-info">Under Review</span>
                                            @elseif($app->status == 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($app->status == 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($app->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $app->submitted_at ? $app->submitted_at->format('M d, Y') : '—' }}</td>
                                        <td>
                                            <a href="{{ route('rental.applications.show', $app->id) }}" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center">No applications found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $applications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
