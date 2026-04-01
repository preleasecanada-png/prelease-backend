<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RenterPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RenterPreferenceController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'preferred_city' => 'nullable|string',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'property_type' => 'nullable|string',
            'min_bedrooms' => 'nullable|integer|min:0',
            'min_bathrooms' => 'nullable|integer|min:0',
            'preferred_move_in' => 'nullable|date',
            'preferred_move_out' => 'nullable|date|after:preferred_move_in',
            'lease_duration' => 'nullable|in:3_month,6_month',
            'preferred_amenities' => 'nullable|array',
            'pets_allowed' => 'nullable|boolean',
            'max_guests' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $preference = RenterPreference::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'preferred_city', 'budget_min', 'budget_max', 'property_type',
                    'min_bedrooms', 'min_bathrooms', 'preferred_move_in', 'preferred_move_out',
                    'lease_duration', 'preferred_amenities', 'pets_allowed', 'max_guests',
                ])
            );

            return response()->json([
                'status' => 200,
                'message' => 'Preferences saved successfully!',
                'data' => $preference
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show()
    {
        try {
            $user = Auth::guard('api')->user();
            $preference = RenterPreference::where('user_id', $user->id)->first();

            return response()->json([
                'status' => 200,
                'data' => $preference
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function searchProperties(Request $request)
    {
        try {
            $query = Property::with(['propertyImages', 'amenities', 'user']);

            if ($request->city) {
                $query->where('city', 'LIKE', '%' . $request->city . '%');
            }

            if ($request->min_price) {
                $query->where('set_your_price', '>=', $request->min_price);
            }
            if ($request->max_price) {
                $query->where('set_your_price', '<=', $request->max_price);
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

            if ($request->max_guests) {
                $query->where('how_many_guests', '>=', $request->max_guests);
            }

            if ($request->amenities && is_array($request->amenities)) {
                $amenityIds = $request->amenities;
                $query->whereHas('amenities', function ($q) use ($amenityIds) {
                    $q->whereIn('amenities.id', $amenityIds);
                }, '>=', count($amenityIds));
            }

            if ($request->start_date && $request->end_date) {
                $start = date('Y-m-d', strtotime($request->start_date));
                $end = date('Y-m-d', strtotime($request->end_date));

                $query->whereDoesntHave('bookings', function ($q) use ($start, $end) {
                    $q->whereIn('status', ['pending', 'payment_pending', 'paid'])
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
            }

            $perPage = $request->per_page ?? 20;
            $properties = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'data' => $properties
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
