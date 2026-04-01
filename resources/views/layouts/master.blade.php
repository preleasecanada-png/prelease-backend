<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- TITLE -->
    <title>PreLease | @yield('title', 'Dashboard')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('public/fav.png') }}" type="image/x-icon">

    <!-- BOOTSTRAP CSS -->
    <link id="style" href="{{ asset('build/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    {{-- custom css --}}
    <link id="style" href="{{ asset('build/assets/styles/style.css') }}" rel="stylesheet">

    {{-- datatables --}}
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/datatable2/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/datatable2/css/bootstrap4.css') }}">


    {{-- toastr --}}
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/toastr/css/min.css') }}">

    {{-- sweet alert --}}
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/sweet-alert/sweetalert.min.css') }}">
    

    {{-- select 2 --}}
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/select2/css/min.css') }}">

    {{-- dropify --}}
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/dropify/css/min.css') }}">


    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- APP CSS & APP SCSS -->
    @vite(['resources/css/app.css', 'resources/sass/app.scss'])

    @stack('style')

    @yield('styles')

</head>

<body class="app ltr sidebar-mini light-mode">

    <!-- Switcher -->
    @include('layouts.components.switcher')
    <!-- End Switcher -->

    <!-- GLOBAL-LOADER -->
    <div id="global-loader">
        <img src="{{ asset('build/assets/images/svgs/loader.svg') }}" class="loader-img" alt="Loader">
    </div>
    <!-- GLOBAL-LOADER -->

    <!-- PAGE -->
    <div class="page">

        <div class="page-main">

            <!-- App-Header -->
            @include('layouts.components.app-header')
            <!-- End App-Header -->

            <!--App-Sidebar-->
            @include('layouts.components.app-sidebar')
            <!-- End App-Sidebar-->

            <!--app-content open-->
            <div class="app-content main-content">
                <div class="side-app">
                    <div class="main-container">

                        @yield('content')

                    </div>
                </div>
                <!-- Container closed -->
            </div>
            <!-- main-content closed -->

        </div>

        <!-- Sidebar-right -->
        {{-- @include('layouts.components.sidebar-right') --}}
        <!-- End Sidebar-right -->

        <!-- Country-selector modal -->
        @include('layouts.components.modal')
        <!-- End Country-selector modal -->

        <!-- Footer opened -->
        @include('layouts.components.footer')
        <!-- End Footer -->

        @yield('modals')

    </div>
    <!-- END PAGE-->

    <!-- SCRIPTS -->
    @include('layouts.components.scripts')

    <!-- APP JS-->
    @vite('resources/js/app.js')
    <!-- END SCRIPTS -->
</body>

</html>
