@extends('layouts.master')
@section('title')
    Profile Update
@endsection
@section('content')
    <div class="row mt-5">
        <h1 class="mb-4 m-4">Profile Update</h1>
        <div class="col-md-12">
            <div class="card form-input-elements">
                <div class="card-body">
                    <form action="{{ route('cities.create_do') }}" method="POST" id="property_form"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" placeholder="Name"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Description.." data-has-listeners="true"></textarea>
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Picture</label>
                                <input type="file" class="dropify" name="picture" accept=".jpg, .png, .jpeg, .webp"
                                    data-height="150">
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
