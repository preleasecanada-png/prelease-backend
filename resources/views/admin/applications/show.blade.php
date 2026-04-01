@extends('layouts.master')
@section('title')
    Application #{{ $application->id }}
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3>Application #{{ $application->id }}</h3></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Renter:</strong> {{ $application->renter->first_name ?? '' }} {{ $application->renter->last_name ?? '' }}</div>
                        <div class="col-md-6"><strong>Landlord:</strong> {{ $application->landlord->first_name ?? '' }} {{ $application->landlord->last_name ?? '' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Property:</strong> {{ $application->property->title ?? '' }}</div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <span class="badge bg-{{ $application->status == 'approved' ? 'success' : ($application->status == 'rejected' ? 'danger' : 'info') }}">
                                {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                            </span>
                        </div>
                    </div>
                    <hr>
                    <h5>Application Details</h5>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Employment:</strong> {{ $application->employment_status }}</div>
                        <div class="col-md-6"><strong>Monthly Income:</strong> ${{ number_format($application->monthly_income, 2) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Occupants:</strong> {{ $application->number_of_occupants }}</div>
                        <div class="col-md-6"><strong>Pets:</strong> {{ $application->has_pets ? 'Yes - ' . $application->pet_details : 'No' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Desired Move-in:</strong> {{ $application->desired_move_in ? $application->desired_move_in->format('M d, Y') : '—' }}</div>
                        <div class="col-md-6"><strong>Lease Duration:</strong> {{ str_replace('_', ' ', $application->desired_lease_duration) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-12"><strong>Current Address:</strong> {{ $application->current_address }}</div>
                    </div>
                    @if($application->reason_for_moving)
                        <div class="mb-2"><strong>Reason for Moving:</strong> {{ $application->reason_for_moving }}</div>
                    @endif
                    @if($application->cover_letter)
                        <div class="mb-2"><strong>Cover Letter:</strong><br>{{ $application->cover_letter }}</div>
                    @endif
                    <hr>
                    <h5>References</h5>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Ref 1:</strong> {{ $application->reference_name_1 ?? '—' }}</div>
                        <div class="col-md-4">{{ $application->reference_phone_1 ?? '' }}</div>
                        <div class="col-md-4">{{ $application->reference_email_1 ?? '' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Ref 2:</strong> {{ $application->reference_name_2 ?? '—' }}</div>
                        <div class="col-md-4">{{ $application->reference_phone_2 ?? '' }}</div>
                        <div class="col-md-4">{{ $application->reference_email_2 ?? '' }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4>Uploaded Documents</h4></div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @forelse($application->documents as $doc)
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div>
                                <strong>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</strong>
                                <br><small class="text-muted">{{ $doc->file_name }} ({{ $doc->file_extension }})</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $doc->verification_status == 'verified' ? 'success' : ($doc->verification_status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($doc->verification_status) }}
                                </span>
                                <a href="{{ asset($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                <form action="{{ route('rental.applications.verify_document', $doc->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="verification_status" value="verified">
                                    <button class="btn btn-sm btn-success">Verify</button>
                                </form>
                                <form action="{{ route('rental.applications.verify_document', $doc->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="verification_status" value="rejected">
                                    <button class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @if($application->landlord_notes)
                <div class="card mb-3">
                    <div class="card-header"><h5>Landlord Notes</h5></div>
                    <div class="card-body">{{ $application->landlord_notes }}</div>
                </div>
            @endif
            @if($application->rejection_reason)
                <div class="card mb-3">
                    <div class="card-header"><h5>Rejection Reason</h5></div>
                    <div class="card-body text-danger">{{ $application->rejection_reason }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection
