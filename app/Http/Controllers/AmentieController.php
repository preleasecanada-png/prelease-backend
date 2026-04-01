<?php

namespace App\Http\Controllers;

use App\Models\Amenties;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AmentieController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $amenities = Amenties::query();
            return DataTables::of($amenities)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return ' <div class="d-flex align-items-center"><div class="user-name-avatar">' . usernameAvatar($row->name) . '</div>' . '<div class="mx-3 modal-open">' . (utf8_encode(Str::limit($row->name, 30, '...'))) . '</div>' . '</div>';
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
                    $actions .= '<a class="btn text-primary btn-sm" href="' . route('amenities.edit', ['id' => $row->id]) . '"><span class="fe fe-edit fs-18"></span></a>';
                    $actions .= '<a class="btn text-danger btn-sm amentie-place" data-id="' . $row->id . '" href="javascript:void(0);" ><span class="fe fe-trash-2 fs-18"></span></a>';
                    $actions .= '</div></div>';
                    return $actions;
                })
                ->rawColumns(['name', 'created_at', 'actions'])
                ->make(true);
        }
        return view('main.amenties.index');
    }


    public function create()
    {
        return view('main.amenties.create',);
    }

    public function create_do(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:240',
            'image' => 'required|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        $amenitie = new Amenties();
        $amenitie->name = $request->name;
        if ($request->file('image')) {
            $file_name = ImagePathName($request->file('image'), 'images/amenties/');
            $amenitie->image = $file_name;
        }
        $amenitie->save();
        return redirect()->route('amenities.index')->with('success', 'Amenitie created successfull.');
    }

    public function edit($id)
    {
        $amenitie = Amenties::find($id);
        return view('main.amenties.edit', compact('amenitie'));
    }
    public function update(Request $request)
    {
        $place = Amenties::find($request->id);
        $this->validate($request, [
            'name' => 'required',
            'image' => 'nullable|file:jpeg,png,wepb,jpg|max:10000',
        ]);
        if (empty($place->image)) {
            $this->validate($request, [
                'image' => 'required|file:jpeg,png,wepb,jpg|max:10000',
            ]);
        }
        $place->name = $request->name;
        if ($request->file('image') != '') {
            $file_name = ImagePathName($request->file('image'), 'images/amenties/');
            $place->image = $file_name;
        }
        $place->update();
        return redirect()->route('amenities.index')->with('success', 'Amenitie updated successfull.');
    }
    public function delete(Request $request)
    {
        $property = Amenties::find($request->id);
        $file_name = $property->image;
        if (file_exists($file_name)) {
            unlink($file_name);
        }
        $property->delete();
        return response()->json(['success' => 1, 'message' => 'Amenitie delete successfull']);
    }
}
