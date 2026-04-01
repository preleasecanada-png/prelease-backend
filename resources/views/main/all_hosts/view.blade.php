@extends('layouts.master')
@section('title')
    Host View
@endsection
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="h-100">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="align-items-center d-flex mb-3">
                            <h1 class="mb-0 flex-grow-1">Host Details</h1>
                        </div>
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="card main-card-body">
                                    <table class="table table-bordered modal-table table-nowrap"
                                        style="width:100% !important">
                                        @if (isset($user->id))
                                            <tr>
                                                <th scope="col">Id</th>
                                                <td>#{{ $user->id }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->first_name))
                                            <tr>
                                                <th scope="col">First Name</th>
                                                <td>{{ $user->first_name }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->last_name))
                                            <tr>
                                                <th scope="col">Last Name</th>
                                                <td>{{ $user->last_name }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->date_of_birth))
                                            <tr>
                                                <th scope="col">Date Of Birth</th>
                                                <td>{{ date('jS M, Y h:i a', strtotime($user->date_of_birth)) }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->bio))
                                            <tr>
                                                <th scope="col">Bio</th>
                                                <td>{{ $user->bio }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->gender))
                                        <tr>
                                            <th scope="col">Gender</th>
                                            <td>{{ $user->gender }}</td>
                                        </tr>
                                    @endif
                                        @if (isset($user->role))
                                            <tr>
                                                <th scope="col">Role</th>
                                                <td>{{ $user->role }}</td>
                                            </tr>
                                        @endif
                                        @if (isset($user->created_at))
                                            <tr>
                                                <th scope="col">Date and time</th>
                                                <td>{{ date('jS M, Y h:i a', strtotime($user->created_at)) }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
