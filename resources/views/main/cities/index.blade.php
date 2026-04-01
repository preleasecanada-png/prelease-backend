@extends('layouts.master')
@section('title')
    Cities
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div>
                <h1 class="mb-5">Cities</h1>
            </div>
            <div>
                <a href="{{ route('cities.create') }}" class="btn btn-warning">Add City</a>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                            <table class="table table-bordered text-nowrap border-bottom dataTable no-footer w-100"
                                id="city_table" role="grid" aria-describedby="basic-datatable_info">
                                <thead class="border-top">
                                </thead>
                                <tbody class="mb-3">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
    <script>
        $('#city_table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('cities.index') }}",
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'id',
                    title: '#',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name',
                    title: 'Name',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'picture',
                    name: 'picture',
                    title: 'Picture',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'description',
                    name: 'description',
                    title: 'Description',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    title: 'Created At',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'actions',
                    name: 'actions',
                    title: '',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        $(document).on('click', '.delete-city', function() {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete this record!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#0ab39c",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    var id = $(this).data('id');
                    console.log(id);

                    $.ajax({
                        type: 'delete',
                        url: "{{ route('cities.delete') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            id: id,
                        },
                        success: function(response) {
                            $('#city_table').DataTable().row('tr').remove().draw();
                            toastr.success(response.message);
                        },
                        error: function(data) {
                            console.log(data);
                        }
                    });
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your record has been deleted.",
                        icon: "success",
                        confirmButtonColor: "#0ab39c",
                    });
                }
            });
        })
    </script>
@endsection
