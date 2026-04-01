<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AllHostController extends Controller
{

    public function index()
    {
        if (request()->ajax()) {
            $hosts = User::where('role', '!=', 'admin')->select('*');
            return DataTables::of($hosts)
                ->addIndexColumn()
                ->editColumn('first_name', function ($row) {
                    return ' <div class="d-flex align-items-center"><div class="user-name-avatar">' . usernameAvatar($row->first_name) . '</div>' . '<div class="mx-3 modal-open">' . (utf8_encode(Str::limit($row->first_name, 30, '...'))) . '</div>' . '</div>';
                })
                ->editColumn('last_name', function ($row) {
                    return $row->last_name;
                })
                ->editColumn('gender', function ($row) {
                    return $row->gender;
                })
                // ->editColumn('price', function ($row) {
                //     return $row->set_your_price;
                // })
                // ->editColumn('guest', function ($row) {
                //     return $row->how_many_guests;
                // })
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
                    $actions .= '<a class="btn text-primary btn-sm" href="' . route('all.hosts.view', ['id' => $row->id]) . '">
                    
                    <span>
                    <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 576 512"><path fill="#D80621" d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
                    </span>
                    </a>';
                    $actions .= '</div>
                        </div>';
                    return $actions;
                })
                ->rawColumns(['first_name', 'created_at', 'actions', 'last_name'])
                ->make(true);
        }
        return view('main.all_hosts.index');
    }
    public function view($id)
    {
        $user = User::where('id', $id)->first();
        return view('main.all_hosts.view', compact('user'));
    }
}
