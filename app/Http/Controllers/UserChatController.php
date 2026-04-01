<?php

namespace App\Http\Controllers;

use App\Models\UserChat;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserChatController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $property = UserChat::query();
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
        return view('main.all_hosts.index');
    }
}
