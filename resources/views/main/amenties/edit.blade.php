@extends('layouts.master')
@section('title')
    Edit Amentie
@endsection
@section('content')
    <div class="row mt-5">
        <h1 class="mb-4 m-4">Edit Amentie</h1>
        <div class="col-md-12">
            <div class="card form-input-elements">
                <div class="card-body">
                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-hidden="true">×</button>{{ $error }}
                            </div>
                        @endforeach
                    @endif
                    <form action="{{ route('amenities.update') }}" method="POST" id="placeForm"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-12 mb-3">
                                <input type="hidden" value="{{ $amenitie->id }}" name="id" id="id">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Name"
                                    data-has-listeners="true" value="{{ $amenitie->name }}">
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Picture <span class="text-danger">*</span></label>
                                <input type="file" class="dropify" name="image" accept=".jpg, .png, .jpeg, .webp"
                                    data-height="150" data-default-file="{{ asset($amenitie->image) }}">
                            </div>
                            <div class="col-md-6">
                                <a href="{{ url()->previous() }}" class="btn btn-primary me-4">Cancel</a>
                                <button class="btn btn-secondary" type="submit">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
