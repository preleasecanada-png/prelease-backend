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
                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-hidden="true">×</button>{{ $error }}
                            </div>
                        @endforeach
                    @endif
                    <form action="{{ route('setting.profile_update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <input type="hidden" value="{{ $user->id }}" name="id">
                            <div class="col-6 mb-4">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $user->user_name }}" name="name"
                                    placeholder="Name" data-has-listeners="true">
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" placeholder="Eamil"
                                    value="{{ $user->email }}" data-has-listeners="true">
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Phone</label>
                                <input type="number" class="form-control" name="phone" placeholder="Phone"
                                    value="{{ $user->phone_no }}" data-has-listeners="true">
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Picture </label>
                                <input type="file" class="dropify" name="picture" accept=".jpg, .png, .jpeg, .webp"
                                    data-default-file="{{ asset($user->picture) }}" data-height="150">
                            </div>
                            <div class="col-12">
                                <a href="{{ url()->previous() }}" class="btn btn-primary me-4">Cancel</a>
                                <button class="btn btn-secondary" type="submit" id="property_btn">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-hidden="true">×</button>{{ $error }}
                            </div>
                        @endforeach
                    @endif
                    <form action="{{ route('setting.password_update') }}" method="POST">
                        @csrf
                        <div class="row">
                            <input type="hidden" value="{{ $user->id }}" name="id">
                            <div class="col-4 mb-4">
                                <label class="form-label">Old Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="old_password" placeholder="Old Password"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-4 mb-4">
                                <label class="form-label">New Password<span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" placeholder="New Password"
                                    value="{{ $user->email }}" data-has-listeners="true">
                            </div>
                            <div class="col-4 mb-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password"
                                    placeholder="Confirm Password" value="{{ $user->phone }}" data-has-listeners="true">
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
