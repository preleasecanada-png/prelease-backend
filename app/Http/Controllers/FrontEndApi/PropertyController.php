<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Models\Property;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Amenties;
use App\Models\Countrie;
use App\Models\PropertyGuestAmenities;
use App\Models\PropertyImages;
use App\Models\WishList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    protected function store(Request $request)
    {
        // $validation = Validator::make($request->all(), [
        //     'title' => 'required',
        //     'description' => 'required',
        // ]);
        // if ($validation->fails()) {
        //     $errors = $validation->errors();
        //     return response()->json(['errors' => $errors], 422);
        // }
        try {
            $property_id = null;
            $property = new Property();
            $property->title = $request->title;
            $property->slug = Str::slug($request->title, '-');
            $property->user_id = $request->user_id;
            $property->describe_your_place = $request->describe_your_place;
            $property->country = $request->country;
            $property->street_address = $request->address;
            $property->city = $request->city;
            $property->postal_code = $request->postal;
            $property->state = $request->province;
            $property->how_many_guests = $request->how_many_guests;
            $property->how_many_bedrooms = $request->how_many_bedrooms;
            $property->how_many_bathroom = $request->how_many_bathroom;
            $property->bathroom_avaiable_private_and_attached = $request->bathroom_avaiable_private_and_attached;
            $property->bathroom_avaiable_dedicated = $request->bathroom_avaiable_dedicated;
            $property->bathroom_avaiable_shared = $request->bathroom_avaiable_shared;
            $property->who_else_there = $request->who_else_there;
            $property->confirm_reservation = $request->reservation_type;
            $property->set_your_price = $request->set_your_price;
            $property->guest_service_fee = $request->guest_service_fee;
            // $property->new_listing_promotion = $request->new_listing_promotion;
            $property->new_listing_promotion = ($request->new_listing_promotion == 1) ? $request->new_listing_promotion_value : null;
            // $property->monthly_discount = $request->monthly_discount;
            $property->monthly_discount = ($request->monthly_discount == 1) ? $request->monthly_discount_value : null;
            // $property->yearly_discount = $request->yearly_discount;
            $property->yearly_discount = ($request->yearly_discount == 1) ? $request->yearly_discount_value : null;
            $property->description = $request->description;
            $property->street_address = $request->address;
            $property->street = $request->street;
            $property->address_line_2 = $request->apt;
            if ($property->save()) {
                $property_id = $property->id;
                $AllAmenities = $request->amenities;
                $amenityIds = collect($AllAmenities)->pluck('id')->toArray();
                $property->amenities()->sync($amenityIds);
            }
            if ($request->file('property_images')) {
                foreach ($request->file('property_images') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $baseName = time() . rand() . '.' . $extension;
                    $file->move(public_path('images/place_gallery_images'), $baseName);
                    $fileName = 'images/place_gallery_images/' . $baseName;
                    Log::info($extension);
                    PropertyImages::create([
                        'property_id' => $property_id,
                        'original' => $fileName,
                        'extension' => $extension ?? 'png',
                    ]);
                }
            }
            return response()->json([
                'message' => 'Property created successfully',
                'status' => 200
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th,
                'status' => 404
            ]);
        }
    }

    // protected function lists()
    // {
    //     try {
    //         $properties = Property::with(['propertyImages', 'amenities' , 'user'])->get();
    //         return response()->json(['status' => 200, 'data' => $properties]);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'error' => $th,
    //             'status' => 404
    //         ]);
    //     }
    // }
    protected function lists(Request $request)
    {
        try {

            $query = Property::with(['propertyImages', 'amenities', 'user']);

            $totalGuests = ($request->adults ?? 0) + ($request->children ?? 0);

            if ($totalGuests > 0) {
                $query->where('guest', '>=', $totalGuests);
            }

            if ($request->start_date && $request->end_date) {

                $start = date('Y-m-d', strtotime($request->start_date));
                $end = date('Y-m-d', strtotime($request->end_date));

                $query->whereDoesntHave('bookings', function ($q) use ($start, $end) {

                    $q->whereIn('status', [
                        'pending',
                        'payment_pending',
                        'paid',
                    ])

                    ->where(function ($dateQuery) use ($start, $end) {
                        $dateQuery->whereBetween('move_in_date', [$start, $end])
                            ->orWhereBetween('move_out_date', [$start, $end])
                            ->orWhere(function ($inner) use ($start, $end) {
                                $inner->where('move_in_date', '<=', $start)
                                    ->where('move_out_date', '>=', $end);
                            });
                    });
                });
            }

            if ($request->country) {
                $region = strtolower($request->country);

                $regionCountries = [
                    'asia' => ['Pakistan - PK','India - IN','Bangladesh - BD','China - CN'],
                    'europe' => ['Germany - DE','France - FR','UK - GB'],
                    'africa' => ['Egypt - EG','South Africa - ZA'],
                    'canada' => ['Canada - CA'],
                    'united states' => ['United States - US'],
                    'united arab emirates' => ['United Arab Emirates - AE'],
                ];

                if (isset($regionCountries[$region])) {
                    $query->whereIn('country', $regionCountries[$region]);
                }
            }

            if ($request->city) {
                $query->where('city', 'LIKE', '%' . $request->city . '%');
            }

            if ($request->min_price) {
                $query->whereRaw('CAST(set_your_price AS DECIMAL(10,2)) >= ?', [$request->min_price]);
            }
            if ($request->max_price) {
                $query->whereRaw('CAST(set_your_price AS DECIMAL(10,2)) <= ?', [$request->max_price]);
            }

            if ($request->min_bedrooms) {
                $query->where('how_many_bedrooms', '>=', $request->min_bedrooms);
            }
            if ($request->min_bathrooms) {
                $query->where('how_many_bathroom', '>=', $request->min_bathrooms);
            }

            if ($request->property_type) {
                $query->where('describe_your_place', $request->property_type);
            }

            if ($request->sort_by) {
                switch ($request->sort_by) {
                    case 'price_asc':
                        $query->orderByRaw('CAST(set_your_price AS DECIMAL(10,2)) ASC');
                        break;
                    case 'price_desc':
                        $query->orderByRaw('CAST(set_your_price AS DECIMAL(10,2)) DESC');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    default:
                        $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $properties = $query->get();

            return response()->json([
                'status' => 200,
                'data' => $properties
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'error' => $th->getMessage()
            ]);
        }
    }
    protected function wish_lists()
    {
        try {
            $wishLists = WishList::with(['property.propertyImages', 'user'])->get();
            return response()->json(['status' => 200, 'data' => $wishLists]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th,
                'status' => 404
            ]);
        }
    }
    protected function wish_list_store(Request $request)
    {
        try {
            $property_id = $request->property_id;
            $user_id = $request->user_id;
            if (!$property_id || !$user_id) {
                return response()->json(['status' => 422, 'message' => 'Missing property_id or user_id']);
            }
            $wishlistExists = WishList::where('property_id', $property_id)
                ->where('user_id', $user_id)
                ->first();
            if ($wishlistExists) {
                return response()->json(['status' => 409, 'message' => 'This property is already in your wish list.']);
            }
            $wishList = new WishList();
            $wishList->property_id = $property_id;
            $wishList->user_id = $user_id;
            $wishList->save();
            return response()->json(['status' => 200, 'message' => 'Wish list created successfully!']);
        } catch (\Throwable $th) {
            Log::error('WishList Store Error: ' . $th->getMessage());
            return response()->json(['status' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    protected function wish_list_delete(Request $request)
    {
        try {
            $id = $request->id;
            $wishList = WishList::findOrFail($id);
            $wishList->delete();
            return response()->json(['status' => 200, 'message' => 'Wish list deleted successfully!']);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th,
                'status' => 404
            ]);
        }
    }

    protected function property_detail($slug , $id)
    {
        try {
            // $placeDetail = Place::with(['placeImages', 'user', 'property'])->where('slug', $slug)->first();
            $user = Auth::guard('api')->user();
            $userId = $user?->id;
            \Log::info("Fetching property detail for slug: $slug, id: $id, userId: $userId");
            $placeDetail = Property::with([
                'propertyImages', 
                'amenities', 
                'user',
                'bookings' => function ($query) use ($userId) {
                    $query->where('renter_id', $userId)
                        ->whereIn('status', ['pending', 'negotiating', 'payment_pending'])
                        ->latest()
                        ->limit(1);
                }
            ])->where('slug', $slug)
            ->where('id', $id)
            ->first();
            // $placeDetail = Property::with(['propertyImages', 'amenities' , 'user'])->where('slug', $slug)->where('id', $id)->first();
            $amenities_id = explode(',', $placeDetail->amenities_id);
            $amenities = Amenties::whereIn('id', $amenities_id)->get();
            return response()->json(['placeDetail' => $placeDetail, 'amenities' =>  $amenities, 'messsage' => 'Place detail Fetch Records.', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }

    public function getCountries()
    {
        try {
        $countries = Countrie::orderBy('name')->get(['id', 'name', 'code']);
            return response()->json(['status' => 200, 'data' => $countries]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 404, 'error' => $th->getMessage()]);
        }
    }

    public function myProperties()
    {
        try {
            $user = Auth::user();
            $properties = Property::with(['propertyImages', 'amenities'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 200, 'data' => $properties]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $property = Property::where('user_id', $user->id)->findOrFail($id);

            $fields = [
                'title', 'description', 'set_your_price', 'how_many_guests',
                'how_many_bedrooms', 'how_many_bathroom', 'country', 'city',
                'state', 'street_address', 'postal_code', 'describe_your_place',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $property->$field = $request->$field;
                }
            }

            if ($request->has('title')) {
                $property->slug = Str::slug($request->title, '-');
            }

            $property->save();

            if ($request->file('property_images')) {
                foreach ($request->file('property_images') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $baseName = time() . rand() . '.' . $extension;
                    $file->move(public_path('images/place_gallery_images'), $baseName);
                    $fileName = 'images/place_gallery_images/' . $baseName;
                    PropertyImages::create([
                        'property_id' => $property->id,
                        'original' => $fileName,
                        'extension' => $extension ?? 'png',
                    ]);
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Property updated successfully',
                'data' => $property->load(['propertyImages', 'amenities']),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $property = Property::where('user_id', $user->id)->findOrFail($id);
            $property->propertyImages()->delete();
            $property->delete();

            return response()->json(['status' => 200, 'message' => 'Property deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function deleteImage($imageId)
    {
        try {
            $user = Auth::user();
            $image = PropertyImages::whereHas('property', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->findOrFail($imageId);

            $filePath = public_path($image->original);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $image->delete();

            return response()->json(['status' => 200, 'message' => 'Image deleted']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
