<?php

namespace App\Exports;

use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PropertyExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Property::with(['user'])->whereHas('user', function ($q) {
            $q->where('role', '!=', 'admin');
        })->orderBy('id', 'desc')->get()->map(function ($row) {
            try {
                return [
                    "title" => $row->title,
                    "country" => $row->country,
                    'set_your_price' => $row->set_your_price,
                    'phone_no'  => $row->phone_no,
                    'city' => $row->city,
                    'how_many_bathroom' => $row->how_many_bathroom,
                    'how_many_bedrooms' => $row->how_many_bedrooms,
                    'how_many_guests' => $row->how_many_guests,
                    'host_name' => isset($row->user) && !empty($row->user) ? $row->user->first_name : '',
                    'host_email' => isset($row->user) && !empty($row->user) ? $row->user->email : '',
                    'host_phone' => isset($row->user) && !empty($row->user) ? $row->user->phone_no : '',
                ];
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', 'Something went wrong!');
            }
        });
    }
    public function headings(): array
    {
        return ["Property Title", "Country", "Price", "Phone No", 'city', 'Bath Room', 'Bed Room', 'Guest', 'Host Name', 'Host Email', 'Host Phone No'];
    }
}
