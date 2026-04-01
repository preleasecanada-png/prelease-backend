<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>

    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- TITLE -->
    <title>PreLease | Sign in</title>
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('build/assets/images/brand/favicon.ico') }}" type="image/x-icon">

    <!-- BOOTSTRAP CSS -->
    <link id="style" href="{{ asset('build/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">


    {{-- toastr --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">


    {{-- custom css --}}
    <link id="style" href="{{ asset('build/assets/styles/style.css') }}" rel="stylesheet">


    {{-- <meta name="csrf_token" content="{{ csrf_token() }}"> --}}
    <!-- APP CSS & APP SCSS -->
    @vite(['resources/css/app.css', 'resources/sass/app.scss'])


</head>

<body class="login-img">

    <!-- GLOBAL-LOADER -->
    <div id="global-loader">
        <img src="{{ asset('build/assets/images/svgs/loader.svg') }}" class="loader-img" alt="Loader">
    </div>
    <!-- GLOBAL-LOADER -->
    <!-- PAGE -->
    <div class="page bg-img">
        <div class="">
            <!-- CONTAINER OPEN -->
            <div class="col col-login mx-auto mt-7">
                <div class="text-center">
                    <a href="#!">
                        <img src="{{ asset('build/assets/images/dahboard/logo.webp') }}" class="header-brand-img-f"
                            alt="logo">
                    </a>
                </div>
            </div>
            @yield('auth_content')
            <!-- CONTAINER CLOSED -->
        </div>
    </div>
    @include('layouts.components.scripts')

    <!-- APP JS-->
    @vite('resources/js/app.js')
</body>

</html>
