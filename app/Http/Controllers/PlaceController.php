<?php

namespace App\Http\Controllers;

use App\Models\Amenties;
use App\Models\City;
use App\Models\Place;
use App\Models\PlaceImages;
use App\Models\Property;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PlaceController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $property = Place::query();
            return DataTables::of($property)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return ' <div class="d-flex align-items-center"><div class="user-name-avatar">' . usernameAvatar($row->name) . '</div>' . '<div class="mx-3 modal-open">' . (utf8_encode(Str::limit($row->name, 30, '...'))) . '</div>' . '</div>';
                })
                ->editColumn('longitude', function ($row) {
                    return $row->longitude;
                })
                ->editColumn('latitude', function ($row) {
                    return $row->latitude;
                })
                ->editColumn('address', function ($row) {
                    return $row->address;
                })
                ->editColumn('picture', function ($row) {
                    $url = asset($row->picture);
                    return '<div class="d-flex justify-content-center align-items-center"><img class="table_image" src=' . ($url) . '><div>';
                })
                ->editColumn('created_at', function ($row) {
                    return '<div class="text-nowrap">' . date('jS M, Y h:i a', strtotime($row->created_at)) . '</div>';
                })
                ->filterColumn('created_at', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword . "%"])
                        ->orWhereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword])
                        ->orWhereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword]);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '';
                    $actions .= '<a class="btn text-primary btn-sm" href="' . route('places.edit', ['id' => $row->id]) . '"><span class="fe fe-edit fs-18"></span></a>';
                    $actions .= '<a class="btn text-danger btn-sm delete-place" data-id="' . $row->id . '" href="javascript:void(0);" ><span class="fe fe-trash-2 fs-18"></span></a>';
                    $actions .= '</div>
                        </div>';
                    return $actions;
                })
                ->rawColumns(['name', 'created_at', 'actions', 'picture', 'address', 'latitude', 'longitude'])
                ->make(true);
        }
        return view('main.places.index');
    }
    public function create()
    {
        $cities = City::orderBy('id', 'DESC')->get();
        $properties = Property::orderBy('id', 'DESC')->get();
        $amenities = Amenties::orderBy('id', 'DESC')->get();
        return view('main.places.create', compact('cities', 'properties', 'amenities'));
    }
    public function create_do(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'price' => 'required',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'property' => 'required',
            'currency' => 'required',
            'banner_picture' => 'required|file:jpeg,png,wepb,jpg|max:10000',
            'check_in_date' => 'required',
            'check_out_date' => 'required',
            'price_type' => 'required',
            'gallery_images.*' => 'nullable|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        $place = new Place();
        $place->name = $request->name;
        $place->city_id = $request->city;
        $place->slug = Str::slug($request->name, '-');
        $place->uuid = Str::uuid()->toString();
        $place->zip_code = $request->zip_code;
        $place->created_by = Auth::user()->id;
        $place->price_type = $request->price_type;
        $place->price = $request->price;
        $place->longitude = $request->longitude;
        $place->latitude = $request->latitude;
        $place->adulats_min = $request->adulats_min;
        $place->adulats_max = $request->adulats_max;
        $place->children_min = $request->children_min;
        $place->children_max = $request->children_max;
        $place->infant_min = $request->infant_min;
        $place->infant_max = $request->infant_max;
        $place->pets_min = $request->pet_min;
        $place->pets_max = $request->pet_max;
        $place->check_in_date = $request->check_in_date;
        $place->check_out_date = $request->check_out_date;
        $place->property_id = $request->property_id;
        $place->amenities_id = implode(',', $request->amenities_id);
        $place->address = $request->address;
        $place->description = $request->description;
        if ($request->file('banner_picture')) {
            $file_name = ImagePathName($request->file('banner_picture'), 'images/places_banner_picture/');
            $place->picture = $file_name;
        }
        if ($place->save()) {
            if ($request->file('gallery_images')) {
                propertyImages('images/places_gallery_images/', $request->file('gallery_images'), $place->id);
            }
        }
        return redirect()->route('places.index')->with('success', 'Place created successfull.');
    }
    public function edit($id)
    {
        $place = Place::find($id);
        $cities = City::orderBy('id', 'DESC')->get();
        $properties = Property::orderBy('id', 'DESC')->get();
        $amenities = Amenties::orderBy('id', 'DESC')->get();
        return view('main.places.edit', compact('cities', 'properties', 'amenities', 'place'));
    }
    public function update(Request $request)
    {
        $place = Place::find($request->id);
        $this->validate($request, [
            'name' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'price' => 'required',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'property' => 'required',
            'banner_picture' => 'nullable|file:jpeg,png,wepb,jpg|max:10000',
            // 'banner_picture' => 'required|file:jpeg,png,wepb,jpg|max:10000',
            'check_in_date' => 'required',
            'currency' => 'required',
            'check_out_date' => 'required',
            'price_type' => 'required',
            'gallery_images.*' => 'nullable|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        if (empty($place->picture)) {
            $this->validate($request, [
                'banner_picture' => 'required|file:jpeg,png,wepb,jpg|max:10000',
            ]);
        }
        $place->name = $request->name;
        $place->city_id = $request->city;
        $place->slug = Str::slug($request->name, '-');
        $place->uuid = Str::uuid()->toString();
        $place->zip_code = $request->zip_code;
        $place->created_by = Auth::user()->id;
        $place->price_type = $request->price_type;
        $place->price = $request->price;
        $place->longitude = $request->longitude;
        $place->latitude = $request->latitude;
        $place->adulats_min = $request->adulats_min;
        $place->adulats_max = $request->adulats_max;
        $place->children_min = $request->children_min;
        $place->children_max = $request->children_max;
        $place->infant_min = $request->infant_min;
        $place->infant_max = $request->infant_max;
        $place->pets_min = $request->pet_min;
        $place->pets_max = $request->pet_max;
        $place->check_in_date = $request->check_in_date;
        $place->check_out_date = $request->check_out_date;
        $place->property_id = $request->property_id;
        $place->amenities_id = implode(',', $request->amenities_id);
        $place->address = $request->address;
        $place->description = $request->description;
        if ($request->file('banner_picture') != '') {
            $file_name = ImagePathName($request->file('banner_picture'), 'images/places_banner_picture/');
            $place->picture = $file_name;
        }
        if ($place->update()) {
            if ($request->file('gallery_images')) {
                propertyImages('images/places_gallery_images/', $request->file('gallery_images'), $place->id);
            }
        }
        return redirect()->route('places.index')->with('success', 'Place updated successfull.');
    }
    public function delete(Request $request)
    {
        $property = Place::find($request->id);
        $file_name = $property->picture;
        if (file_exists($file_name)) {
            unlink($file_name);
        }
        $property->delete();
        return response()->json(['success' => 1, 'message' => 'Place delete successfull']);
    }
    public function placeImages(Request $request)
    {
        $placeImages = PlaceImages::where('id', $request->id)->first();
        $placeImage = $placeImages->original;
        if ($placeImages) {
            if (file_exists($placeImage)) {
                unlink($placeImage);
            }
            $placeImages->delete();
        }
        return response()->json(['status' => true]);
    }
}
