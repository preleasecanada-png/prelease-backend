@extends('layouts.master')
@section('title')
    Messages
@endsection
@section('content')
    <div class="row row-sm mt-5">
        <div class="d-flex justify-content-between p-5">
            <div>
                <h1 class="mb-5">Messages</h1>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="basic-datatable_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                            <table class="table table-bordered text-nowrap border-bottom dataTable no-footer w-100"
                                id="messageTale" role="grid" aria-describedby="basic-datatable_info">
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
        $('#messageTale').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            order: [
                [6, "desc"]
            ],
            ajax: {
                url: "{{ route('places.index') }}",
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
                    data: 'longitude',
                    name: 'longitude',
                    title: 'Longitude',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'latitude',
                    name: 'latitude',
                    title: 'Latitude',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'address',
                    name: 'address',
                    title: 'Address',
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
    </script>
@endsection
