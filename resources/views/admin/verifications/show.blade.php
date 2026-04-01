@extends('layouts.master')
@section('title')
    Verification Details
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3>Verification #{{ $verification->id }}</h3></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>User:</strong> {{ $verification->user->first_name }} {{ $verification->user->last_name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> {{ $verification->user->email }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $verification->verification_type)) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Document Type:</strong> {{ $verification->document_type }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            @if($verification->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($verification->status == 'under_review')
                                <span class="badge bg-info">Under Review</span>
                            @elseif($verification->status == 'verified')
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Submitted:</strong> {{ $verification->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    @if($verification->document_path)
                        <div class="mb-3">
                            <strong>Document:</strong>
                            <a href="{{ asset($verification->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                        </div>
                    @endif
                    @if($verification->admin_notes)
                        <div class="mb-3">
                            <strong>Admin Notes:</strong>
                            <p>{{ $verification->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4>Update Status</h4></div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('verifications.update_status', $verification->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="under_review" {{ $verification->status == 'under_review' ? 'selected' : '' }}>Under Review</option>
                                <option value="verified" {{ $verification->status == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ $verification->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="4">{{ $verification->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
