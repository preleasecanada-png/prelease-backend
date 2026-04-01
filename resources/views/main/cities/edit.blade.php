@extends('layouts.master')
@section('title')
    Update City
@endsection
@section('content')
    <div class="row mt-5">
        <h1 class="mb-4 m-4">Update City</h1>
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
                    <form action="{{ route('cities.update') }}" method="POST" id="property_form"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <input type="hidden" value="{{ $city->id }}" name="id">
                            <div class="col-12 mb-4">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Name"
                                    data-has-listeners="true" value="{{ $city->name }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Description.." data-has-listeners="true">{{ $city->description }}</textarea>
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Picture <span class="text-danger">*</span></label>
                                <input type="file" class="dropify" name="picture" accept=".jpg, .png, .jpeg, .webp"
                                    data-height="150" data-default-file="{{ asset($city->picture) }}">
                            </div>
                            <div class="col-12">
                                <a href="{{ url()->previous() }}" class="btn btn-primary me-4">Cancel</a>
                                <button class="btn btn-secondary" type="submit" id="property_btn">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
