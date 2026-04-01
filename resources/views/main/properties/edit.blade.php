@extends('layouts.master')
@section('title')
    Edit Property
@endsection
@section('content')
    <div class="row mt-5">
        <h1 class="mb-4 m-4">Edit Property</h1>
        <div class="col-md-12">
            <div class="card form-input-elements">
                <div class="card-body">
                    <form action="{{ route('properties.update') }}" method="POST" id="property_form"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <input type="hidden" value="{{ $property->id }}" name="id">
                            <div class="col-md-6 col-lg-6 col-sm-12 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name"
                                    placeholder="Apartment Name etc..." value="{{ $property->title }}"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-md-6 col-lg-6 col-sm-12 mb-3">
                                <label class="form-label">Bed Room</label>
                                <input type="number" class="form-control" min="0" name="bedroom"
                                    placeholder="bed room.." value="{{ $property->how_many_bedrooms }}"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-md-6 col-lg-6 col-sm-12 mb-3">
                                <label class="form-label">Bath Room</label>
                                <input type="number" class="form-control" min="0" name="bath_room"
                                    placeholder="bath room.." value="{{ $property->how_many_bedrooms }}">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="price" placeholder="Price"
                                    data-has-listeners="true" value="{{ $property->set_your_price }}">
                            </div>

                            <div class="col-lg-12 col-sm-12 mb-4">
                                <label class="form-label">Amenities</label>
                                <select name="amenities_id[]" id="amenities_id" multiple="multiple"
                                    class="form-control custom-select select2">
                                    <option>select an option</option>
                                    @foreach ($amenities as $amenity)
                                        <option value="{{ $amenity->id }}"
                                            {{ in_array($amenity->id, $amenitieGuest) ? 'selected' : '' }}>
                                            {{ $amenity->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 col-lg-12 col-sm-12 mb-3">
                                <label class="form-label">Bath Room No</label>
                                <input type="number" class="form-control" min="0" name="bath_room_no"
                                    placeholder="Bath_room_no" data-has-listeners="true"
                                    value="{{ $property->how_many_bathroom }}">
                            </div>

                            <div class="col-md-12 col-lg-12 col-sm-12 mb-5">
                                <label class="form-label">Guest</label>
                                <input type="number" class="form-control" min="0" name="guest"
                                    placeholder="Guestss" data-has-listeners="true"
                                    value="{{ $property->how_many_guests }}">
                            </div>


                            <div class="col-md-6 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" placeholder="Country"
                                    value="{{ $property->country }}">
                            </div>

                            <div class="col-md-6 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" placeholder="City"
                                    value="{{ $property->city }}">
                            </div>


                            <div class="col-md-6 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" placeholder="Postal Code"
                                    value="{{ $property->postal_code }}">
                            </div>

                            <div class="col-md-6 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" placeholder="State"
                                    value="{{ $property->state }}">
                            </div>
                            <div class="col-md-5 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">Price</label>
                                <input type="text" class="form-control" name="set_your_price" placeholder="Price"
                                    value="{{ $property->set_your_price }}">
                            </div>

                            <div class="col-md-5 col-lg-6 col-sm-12 mb-5">
                                <label class="form-label">Guest Service Fee</label>
                                <input type="text" class="form-control" name="guest_service_fee"
                                    placeholder="Guest Service Fee" value="{{ $property->guest_service_fee }}">
                            </div>

                            <div class="col-12 mb-4">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Address.." data-has-listeners="true">{{ $property->street_address }}</textarea>
                            </div>
                            <div class="col-12 mb-4 quill_text">
                                <label class="form-label">Description</label>
                                <div id="quillEditor"></div>
                                <input type="hidden" name="description" id="description">
                            </div>

                            <div class="col-12 mb-4">
                                <div class="form-group">
                                    <label for="gallery_image">Gallery Image</label>
                                    <div class="upload__box">
                                        <div class="upload__btn-box">
                                            <label class="upload__btn">
                                                <i class="fa fa-solid fa-image upload-icon-f"></i>
                                                <input type="file" multiple="" data-max_length="20"
                                                    class="upload__inputfile" name="gallery_image[]"
                                                    accept=".png, .jpg, .jpeg, .webp">
                                            </label>
                                        </div>

                                        @php
                                            $galley_images = $property->propertyImages;
                                        @endphp

                                        @if ($galley_images)
                                            <div class="upload__img-wrap sortable">
                                                @foreach ($galley_images as $galley_image)
                                                    <div class='upload__img-box'
                                                        id="upload_image{{ $galley_image->id }}">
                                                        <div style='background-image: url("{{ asset($galley_image->original) }} ")'
                                                            class='img-bg' data-file="existing_{{ $galley_image->id }}">
                                                            <div class='upload__img-close gallery-image-delete'
                                                                data-id={{ $galley_image->id }}></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <input type="hidden" id="imageOrder" name="image_order" value="">
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mx-4">
                                    <a href="{{ url()->previous() }}" class="btn btn-primary me-4">Cancel</a>
                                    <button class="btn btn-secondary" type="submit" id="property_btn">Save
                                        Changes</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            ImgUpload();
            $(".sortable").sortable();
            $('form').on('submit', function() {
                updateImageOrder();
            });
        });

        function ImgUpload() {
            var imgWrap = "";
            var imgArray = [];
            $('.upload__inputfile').each(function() {
                $(this).on('change', function(e) {
                    imgWrap = $(this).closest('.upload__box').find('.upload__img-wrap');
                    var maxLength = $(this).attr('data-max_length');

                    var files = e.target.files;
                    var filesArr = Array.prototype.slice.call(files);
                    var iterator = 0;
                    filesArr.forEach(function(f, index) {
                        var allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
                        if (!allowedTypes.includes(f.type)) {
                            toastr.error(
                                "Invalid file type! Only .jpg, .jpeg, .png, and .webp files are allowed."
                            );
                            return false;
                        }
                        if (imgArray.length > maxLength) {
                            return false;
                        } else {
                            var len = 0;
                            for (var i = 0; i < imgArray.length; i++) {
                                if (imgArray[i] !== undefined) {
                                    len++;
                                }
                            }
                            if (len > maxLength) {
                                return false;
                            } else {
                                imgArray.push(f);

                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    var html =
                                        "<div class='upload__img-box'><div style='background-image: url(" +
                                        e.target.result + ")' data-file='" + f.name +
                                        "' class='img-bg'><div class='upload__img-close'></div></div></div>";
                                    imgWrap.append(html);
                                    iterator++;
                                    $(".sortable").sortable();
                                }
                                reader.readAsDataURL(f);
                            }
                        }
                    });
                });
            });
            $('body').on('click', ".upload__img-close", function(e) {
                var file = $(this).parent().data("file");
                for (var i = 0; i < imgArray.length; i++) {
                    if (imgArray[i].name === file) {
                        imgArray.splice(i, 1);
                        break;
                    }
                }
                if (confirm("Are you sure you want to delete?")) {
                    $(this).parent().parent().remove();
                }
            });
        }

        function updateImageOrder() {
            var sortedImages = $(".sortable .img-bg").map(function() {
                return $(this).data('file') || $(this).parent().attr(
                    'id');
            }).get();

            $('#imageOrder').val(JSON.stringify(sortedImages));
        }
    </script>
    <script>
        $(document).on('click', '.gallery-image-delete', function() {
            let id = $(this).data('id');
            console.log(id);
            let parent = $(this).closest('#upload_image' + id);
            if (confirm("Are you sure you want to delete?")) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('properties.delete_property_images') }}",
                    data: {
                        id: id
                    },
                    success: function(response) {
                        if (response.status == true) {
                            parent.remove();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }
        })
    </script>

    <script src="{{ asset('build/assets/plugins/quill/quill.min.js') }}"></script>
    <script>
        var toolbarOptions = [
            [{
                'header': [1, 2, 3, 4, 5, 6]
            }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{
                'header': 2
            }],
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            [{
                'script': 'sub'
            }, {
                'script': 'super'
            }],
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }],
            [{
                'direction': 'rtl'
            }],
            ['link', 'image'],
            [{
                'color': []
            }, {
                'background': []
            }],
            [{
                'align': []
            }],
        ];
        const quill = new Quill('#quillEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',
            placeholder: 'Description...',
        });
        var oldValue = `{!! $property->description !!}`;
        quill.root.innerHTML = oldValue;
        $("#property_form").on("submit", function() {
            $("#description").val(quill.root.innerHTML);
        })
    </script>
@endsection
