<?php

namespace App\Http\Controllers;

use App\Exports\PropertyExport;
use App\Models\Amenties;
use App\Models\Property;
use App\Models\PropertyGuestAmenities;
use App\Models\PropertyImages;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PropertyController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $property = Property::query();
            return DataTables::of($property)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return ' <div class="d-flex align-items-center"><div class="user-name-avatar">' . usernameAvatar($row->title) . '</div>' . '<div class="mx-3 modal-open">' . (utf8_encode(Str::limit($row->title, 30, '...'))) . '</div>' . '</div>';
                })
                ->editColumn('bedroom', function ($row) {
                    return $row->how_many_bedrooms;
                })
                ->editColumn('bath_room', function ($row) {
                    return $row->how_many_bathroom;
                })
                ->editColumn('price', function ($row) {
                    return $row->set_your_price;
                })
                ->editColumn('guest', function ($row) {
                    return $row->how_many_guests;
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
                    $actions .= '<a class="btn text-primary btn-sm" href="' . route('properties.edit', ['id' => $row->id]) . '"><span class="fe fe-edit fs-18"></span></a>';
                    $actions .= '<a class="btn text-danger btn-sm delete-property" data-id="' . $row->id . '" href="javascript:void(0);" ><span class="fe fe-trash-2 fs-18"></span></a>';
                    $actions .= '</div>
                        </div>';
                    return $actions;
                })
                ->rawColumns(['name', 'created_at', 'actions', 'guest'])
                ->make(true);
        }
        return view('main.properties.index');
    }
    public function create()
    {
        $amenities = Amenties::orderBy('id', 'desc')->get();
        return view('main.properties.create', compact('amenities'));
    }
    public function create_do(Request $request)
    {
        $property = new Property();
        $property->title = $request->name;
        $property->how_many_bedrooms = $request->bedroom;
        $property->how_many_bedrooms = $request->bed_rooms;
        $property->how_many_bathroom = $request->bath_room_no;
        $property->how_many_guests = $request->guest;
        $property->user_id = Auth::user()->id;
        $property->description = $request->description;
        $property->country = $request->country;
        $property->city = $request->city;
        $property->postal_code = $request->postal_codes;
        $property->state = $request->state;
        $property->set_your_price = $request->set_your_price;
        $property->guest_service_fee = $request->guest_service_fee;
        $property->street_address = $request->address;
        $property->save();
        $property_id = $property->id;
        if ($request->has('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $extension = $file->getClientOriginalExtension();
                $fileName = 'images/property_gallery_images/' . time() . rand() . '.' . $extension;
                $file->move(public_path('images/property_gallery_images'), $fileName);
                PropertyImages::create([
                    'property_id' => $property_id,
                    'original' => $fileName,
                    'extension' => $extension ?? 'png',
                ]);
            }
        }
        return redirect()->route('properties.index')->with('success', 'Property created successfull.');
    }
    public function edit($id)
    {
        $property = Property::find($id);
        $amenities = Amenties::orderBy('id', 'desc')->get();
        $amenitieGuest = PropertyGuestAmenities::where('property_id', $id)->pluck('amenity_id')->toArray();
        return view('main.properties.edit', compact('property', 'amenitieGuest', 'amenities'));
    }
    public function update(Request $request)
    {
        $property = Property::find($request->id);
        $property->title = $request->name;
        $property->how_many_bedrooms = $request->bedroom;
        $property->how_many_bedrooms = $request->bed_rooms;
        $property->how_many_bathroom = $request->bath_room_no;
        $property->how_many_guests = $request->guest;
        $property->user_id = Auth::user()->id;
        $property->description = $request->description;
        $property->country = $request->country;
        $property->city = $request->city;
        $property->postal_code = $request->postal_codes;
        $property->state = $request->state;
        $property->set_your_price = $request->set_your_price;
        $property->guest_service_fee = $request->guest_service_fee;
        $property->street_address = $request->address;
        $property->save();
        $property_id = $property->id;
        if ($request->has('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $extension = $file->getClientOriginalExtension();
                $fileName = 'images/property_gallery_images/' . time() . rand() . '.' . $extension;
                $file->move(public_path('images/property_gallery_images'), $fileName);
                PropertyImages::create([
                    'property_id' => $property_id,
                    'original' => $fileName,
                    'extension' => $extension ?? 'png',
                ]);
            }
        }
        $property->update();
        return redirect()->route('properties.index')->with('success', 'Property updated successfull.');
    }
    public function delete(Request $request)
    {
        try {
            $property = Property::find($request->id);
            if (!empty($property)) {
                $property->delete();
            }
            return response()->json(['success' => 1, 'message' => 'Property delete successfull']);
        } catch (\Throwable $th) {
            return response()->json(['success' => 0, 'error' => $th->getMessage()]);
        }
    }
    public function export()
    {
        try {
            return Excel::download(new PropertyExport, 'property.csv');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }
    public function delete_property_images(Request $request)
    {
        $gallery_image_delete = PropertyImages::where('id', $request->id)->first();
        if ($gallery_image_delete) {
            $galleryImage = $gallery_image_delete->original;
            if (file_exists($galleryImage)) {
                unlink($galleryImage);
            }
            $gallery_image_delete->delete();
        }
        return response()->json(['status' => true]);
    }
}
