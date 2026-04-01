@extends('layouts.master')
@section('title')
    Amenties
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div>
                <h1 class="mb-5">Amenties</h1>
            </div>
            <div>
                <a href="{{ route('amenities.create') }}" class="btn btn-warning">Add Amentie</a>
            </div>

        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                            <table class="table table-bordered text-nowrap border-bottom dataTable no-footer w-100"
                                id="amentie_table" role="grid" aria-describedby="basic-datatable_info">
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
        $('#amentie_table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            order: [
                [2, "desc"]
            ],
            ajax: {
                url: "{{ route('amenities.index') }}",
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

        $(document).on('click', '.amentie-place', function() {
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
                        url: "{{ route('amenities.delete') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            id: id,
                        },
                        success: function(response) {
                            $('#amentie_table').DataTable().row('tr').remove().draw();
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
