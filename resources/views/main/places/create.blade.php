@extends('layouts.master')
@section('title')
    Create Places
@endsection
@push('style')
    <link rel="stylesheet" href="{{ asset('build/assets/plugins/quill/css/snow.css') }}">
@endpush
@section('content')
    <div class="row mt-5">
        <h1 class="mb-4 m-4">Create Places</h1>
        <div class="col-md-12">
            <div class="card form-input-elements">
                <div class="card-body">
                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-hidden="true">×</button>{{ $error }}
                            </div>
                        @endforeach
                    @endif
                    <form action="{{ route('places.create_do') }}" method="POST" id="placeForm"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Name"
                                    data-has-listeners="true" value="{{ old('name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city" id="city" class="form-control">
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}"
                                            {{ intval(old('city')) == $city->id ? 'selected' : '' }}>
                                            {{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Zip Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="zip_code" value="{{ old('zip_code') }}"
                                    placeholder="Zip Code" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Price Type <span class="text-danger">*</span></label>
                                <select name="price_type" id="price_type" class="form-control">
                                    <option>select an option</option>
                                    <option value="night" {{ old('price_type') === 'night' ? 'selected' : '' }}>Night
                                    </option>
                                    <option value="weekly" {{ old('price_type') === 'weekly' ? 'selected' : '' }}>Weekly
                                    </option>
                                    <option value="monthly" {{ old('price_type') === 'monthly' ? 'selected' : '' }}>Monthly
                                    </option>
                                    <option value="yearly" {{ old('price_type') === 'yearly' ? 'selected' : '' }}>Yearly
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="price" placeholder="Price"
                                    data-has-listeners="true" value="{{ old('price') }}">
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select name="currency" id="currency" class="form-control" data-has-listeners="true">
                                    <option value="usd">USD</option>
                                    <option value="eur">Euro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Longitude</label>
                                <input type="text" class="form-control" name="longitude" placeholder="Longitude"
                                    data-has-listeners="true" value="{{ old('longitude') }}">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Latitude</label>
                                <input type="text" class="form-control" name="latitude" value="{{ old('latitude') }}"
                                    placeholder="Latitude" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Adulats Min</label>
                                <input type="number" class="form-control" name="adulats_min"
                                    value="{{ old('adulats_min') }}" placeholder="Adulats Min" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Adulats Max</label>
                                <input type="number" class="form-control" name="adulats_max"
                                    value="{{ old('adulats_max') }}" placeholder="Adulats Max"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Children Min</label>
                                <input type="number" class="form-control" name="children_min"
                                    value="{{ old('children_min') }}" placeholder="Children Min"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Children Max</label>
                                <input type="number" class="form-control" name="children_max"
                                    value="{{ old('children_max') }}" placeholder="Children Max"
                                    data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Infant Min</label>
                                <input type="number" class="form-control" name="infant_min" placeholder="Infant Min"
                                    value="{{ old('infant_min') }}" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Infant Max</label>
                                <input type="number" class="form-control" name="infant_max" placeholder="Infant Max"
                                    value="{{ old('infant_max') }}" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Pet Min</label>
                                <input type="number" class="form-control" name="pet_min" placeholder="Pet Min"
                                    value="{{ old('pet_min') }}" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Pet Max</label>
                                <input type="number" class="form-control" name="pet_max" placeholder="Pet Max"
                                    value="{{ old('pet_max') }}" data-has-listeners="true">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Check in Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="check_in_date"
                                    placeholder="Check in Date" data-has-listeners="true"
                                    value="{{ old('check_in_date') }}">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Check out Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="check_out_date"
                                    placeholder="Check out Date" data-has-listeners="true"
                                    value="{{ old('check_out_date') }}">
                            </div>

                            <div class="col-lg-12 col-sm-12 mb-4">
                                <label class="form-label">Properties <span class="text-danger">*</span></label>
                                <select name="property" id="property_id" class="form-control">
                                    <option>select an option</option>
                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}"
                                            {{ $property->id === old('property_id') ? 'selected' : '' }}>
                                            {{ $property->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-12 col-sm-12 mb-4">
                                <label class="form-label">Amenities</label>
                                <select name="amenities_id[]" id="amenities_id" multiple="multiple"
                                    class="form-control custom-select select2">
                                    <option>select an option</option>
                                    @foreach ($amenities as $amenity)
                                        <option value="{{ $amenity->id }}"
                                            {{ in_array($amenity->id, old('amenities_id', [])) ? 'selected' : '' }}>
                                            {{ $amenity->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-4">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Address.." data-has-listeners="true">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-12 mb-4 quill_text">
                                <label class="form-label">Description</label>
                                <div id="quillEditor"></div>
                                <input type="hidden" name="description" id="description">
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Banner Picture <span class="text-danger">*</span></label>
                                <input type="file" class="dropify" name="banner_picture"
                                    accept=".jpg, .png, .jpeg, .webp" data-height="150">
                            </div>

                            <div class="col-12 mb-4">
                                <div class="form-group">
                                    <label for="gallery_images">Gallery Image</label>
                                    <div class="upload__box">
                                        <div class="upload__btn-box">
                                            <label class="upload__btn">
                                                <i class="fa fa-solid fa-image upload-icon-f"></i>
                                                <input type="file" multiple="" data-max_length="20"
                                                    class="upload__inputfile" name="gallery_images[]"
                                                    accept=".png, .jpg, .jpeg, .webp">
                                            </label>
                                        </div>
                                        <div class="upload__img-wrap"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ url()->previous() }}" class="btn btn-primary me-4">Cancel</a>
                                <button class="btn btn-secondary" type="submit">Save Changes</button>
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
                            return false
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
                                        e.target.result + ")' data-number='" + $(
                                            ".upload__img-close").length + "' data-file='" + f
                                        .name +
                                        "' class='img-bg'><div class='upload__img-close'></div></div></div>";
                                    imgWrap.append(html);
                                    iterator++;
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
        var oldValue = `{!! old('description') !!}`;
        quill.root.innerHTML = oldValue;
        $("#placeForm").on("submit", function() {
            $("#description").val(quill.root.innerHTML);
        })
    </script>
@endsection
