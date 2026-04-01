<!-- BACK-TO-TOP -->
<a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>

<!-- JQUERY JS -->
<script src="{{ asset('build/assets/plugins/jquery/jquery.min.js') }}"></script>

<!-- BOOTSTRAP JS -->
<script src="{{ asset('build/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
<script src="{{ asset('build/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>

<!-- SIDE-MENU JS -->
<script src="{{ asset('build/assets/plugins/sidemenu/sidemenu.js') }}"></script>

<!-- STICKY js -->
@vite('resources/assets/js/sticky.js')

<!-- SIDEBAR JS -->
<script src="{{ asset('build/assets/plugins/sidebar/sidebar.js') }}"></script>

<!-- Perfect SCROLLBAR JS-->
<script src="{{ asset('build/assets/plugins/p-scroll/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('build/assets/plugins/p-scroll/pscroll.js') }}"></script>
<script src="{{ asset('build/assets/plugins/p-scroll/pscroll-1.js') }}"></script>


{{-- select 2 --}}
<script src="{{ asset('build/assets/plugins/select2/select2.full.min.js') }}"></script>

<!-- FILE UPLOADES JS -->
<script src="{{ asset('build/assets/plugins/dropify/js/min.js') }}"></script>

{{-- datatables --}}
<script src="{{ asset('build/assets/plugins/datatable2/js/popper.min.js') }}"></script>
<script src="{{ asset('build/assets/plugins/datatable2/js/bootstrap/min.js') }}"></script>
<script src="{{ asset('build/assets/plugins/datatable2/dataTables.js') }}"></script>
<script src="{{ asset('build/assets/plugins/datatable2/js/bootstrap/bootstrap4.js') }}"></script>

{{-- toastr --}}
<script src="{{ asset('build/assets/plugins/toastr/js/min.js') }}"></script>

{{-- sweet alert --}}
<script src="{{ asset('build/assets/plugins/sweet-alert/sweetalert.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $('.dropify').dropify({
            imgFileExtensions: ['png', 'jpg', 'jpeg', 'webp'],
        });
    });
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-bottom-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }
    @if (Session::has('success'))
        toastr.success("{{ Session::get('success') }}");
    @endif
    @if (Session::has('warning'))
        toastr.warning("{{ Session::get('warning') }}");
    @endif
    @if (Session::has('error'))
        toastr.error("{{ Session::get('error') }}");
    @endif

    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@yield('scripts')

<!-- SWITCHER JS -->
@vite('resources/assets/switcher/js/switcher.js')
