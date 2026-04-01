@extends('layouts.master')
@section('title')
    Ticket #{{ $ticket->id }}
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3>{{ $ticket->subject }}</h3></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>From:</strong> {{ $ticket->user->first_name ?? '' }} {{ $ticket->user->last_name ?? '' }} ({{ $ticket->user->email ?? '' }})</div>
                        <div class="col-md-3"><strong>Role:</strong> {{ ucfirst($ticket->user->role ?? '') }}</div>
                        <div class="col-md-3"><strong>Created:</strong> {{ $ticket->created_at->format('M d, Y H:i') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Category:</strong> {{ ucfirst($ticket->category) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Priority:</strong>
                            <span class="badge bg-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'secondary')) }}">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Status:</strong>
                            <span class="badge bg-{{ $ticket->status == 'open' ? 'primary' : ($ticket->status == 'in_progress' ? 'info' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </div>
                    </div>
                    <hr>
                    <h5>Message</h5>
                    <div class="p-3 bg-light rounded mb-3">
                        {{ $ticket->message }}
                    </div>
                    @if($ticket->admin_response)
                        <h5>Admin Response</h5>
                        <div class="p-3 bg-light rounded border-start border-success border-3">
                            {{ $ticket->admin_response }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4>Respond</h4></div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('support.respond', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Response</label>
                            <textarea name="admin_response" class="form-control" rows="6" required>{{ $ticket->admin_response }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Response</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
