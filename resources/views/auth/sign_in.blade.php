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
            <form method="POST" action="{{ route('sign.in.do') }}">
                @csrf
                <span class="login100-form-title">
                    Login
                </span>
                <div class="wrap-input100 validate-input mb-4">
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
                    <button type="submit" class="login100-form-btn btn-primary">Login</button>
                </div>
                <div class="text-center pt-3">
                    <p class="text-dark mb-0">Not a member?<a href="" class="text-primary mx-1">Sign
                            UP now</a></p>
                </div>
            </form>
        </div>
    </div>
@endsection