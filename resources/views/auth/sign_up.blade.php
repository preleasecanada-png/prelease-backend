@extends('auth.master')
@section('auth_content')
    <div class="container-login100">
        <div class="wrap-login100 p-6">
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-danger" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                            aria-hidden="true">×</button>{{ $error }}
                    </div>
                @endforeach
            @endif
            <form class="login100-form validate-form" method="POST" action="{{ route('sign_up_do') }}">
                @csrf
                <span class="login100-form-title">
                    Register
                </span>

                <div class="wrap-input100 validate-input">
                    <input class="input100" type="text" name="user_name" placeholder="User name">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="mdi mdi-account" aria-hidden="true"></i>
                    </span>
                </div>

                <div class="wrap-input100 validate-input mb-4" data-validate = "Valid email is required: ex@abc.xyz">
                    <input class="input100" type="text" name="email" placeholder="Email">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="zmdi zmdi-email" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="wrap-input100 validate-input" data-validate = "Password is required">
                    <input class="input100" type="password" name="password" placeholder="Password">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="zmdi zmdi-lock" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="text-end pt-1">
                    <p class="mb-0"><a href="javascript:void(0);" class="text-primary ms-1">Forgot Password?</a></p>
                </div>
                <div class="container-login100-form-btn">
                    <button type="submit" class="login100-form-btn btn-primary">Sign up</button>
                </div>
                <div class="text-center pt-3">
                    <p class="text-dark mb-0">Not a member?<a href="{{ route('sign_in') }}" class="text-primary mx-1">Sign
                            in
                            now</a></p>
                </div>
                <div class=" flex-c-m text-center mt-3">
                    <p>Or</p>
                    <div class="social-icons">
                        <ul>
                            <li><a class="btn  btn-social btn-block"><i class="fa fa-google-plus text-google-plus"></i>
                                    Sign
                                    up with Google</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
