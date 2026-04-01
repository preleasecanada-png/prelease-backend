<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CityController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $property = City::orderBy('id','DESC');
            return DataTables::of($property)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return ' <div class="d-flex align-items-center"><div class="user-name-avatar">' . usernameAvatar($row->name) . '</div>' . '<div class="mx-3 modal-open">' . (utf8_encode(Str::limit($row->name, 30, '...'))) . '</div>' . '</div>';
                })
                ->editColumn('description', function ($row) {
                    return Str::limit($row->description, 100, '....');
                })
                ->editColumn('created_at', function ($row) {
                    return '<div class="text-nowrap">' . date('jS M, Y h:i a', strtotime($row->created_at)) . '</div>';
                })
                ->editColumn('picture', function ($row) {
                    $url = asset($row->picture);
                    return '<div class="d-flex justify-content-center align-items-center"><img class="table_image" src=' . ($url) . '><div>';
                })
                ->filterColumn('created_at', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword . "%"])
                        ->orWhereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword])
                        ->orWhereRaw("DATE_FORMAT(created_at, '%D %b, %Y %h:%i:%s %p') LIKE ?", ["%" . $keyword]);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '';
                    $actions .= '<a class="btn text-primary btn-sm" href="' . route('cities.edit', ['id' => $row->id]) . '"><span class="fe fe-edit fs-18"></span></a>';
                    $actions .= '<a class="btn text-danger btn-sm delete-city" data-id="' . $row->id . '" href="javascript:void(0);" ><span class="fe fe-trash-2 fs-18"></span></a>';
                    $actions .= '</div>
                        </div>';
                    return $actions;
                })
                ->rawColumns(['name', 'created_at', 'actions', 'picture'])
                ->make(true);
        }
        return view('main.cities.index');
    }
    public function create()
    {
        return view('main.cities.create');
    }
    public function create_do(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'picture' => 'required|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        $city = new City();
        $city->name = $request->name;
        $city->slug = Str::slug($request->name, '-');
        $city->description = $request->description;
        if ($request->file('picture')) {
            $file_name = ImagePathName($request->file('picture'), 'images/cities/');
            $city->picture = $file_name;
        }
        $city->save();
        return redirect()->route('cities.index')->with('success', 'City created successfull.');
    }
    public function edit($id)
    {
        $city = City::find($id);
        return view('main.cities.edit', compact('city'));
    }
    public function update(Request $request)
    {
        $city = City::find($request->id);
        $this->validate($request, [
            'name' => 'required',
            'picture' => 'nullable|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        if (empty($city->picture)) {
            $this->validate($request, [
                'picture' => 'required|file:jpeg,png,wepb,jpg|max:10000',
            ]);
        }
        $city->name = $request->name;
        $city->slug = Str::slug($request->name, '-');
        $city->description = $request->description;
        if ($request->file('picture')) {
            $file_name = ImagePathName($request->file('picture'), 'images/cities/');
            $city->picture = $file_name;
        }
        $city->update();
        return redirect()->route('cities.index')->with('success', 'City updated successfull.');
    }
    public function delete(Request $request)
    {
        $property = City::find($request->id);
        $file_name = $property->banner_image;
        if (file_exists($file_name)) {
            unlink($file_name);
        }
        $property->delete();
        return response()->json(['success' => 1, 'message' => 'Property delete successfull']);
    }
}
