<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\LeaseAgreement;
use App\Models\Property;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $role = $request->role ?? 'reviewer';

            if ($role === 'reviewer') {
                $reviews = Review::with(['property.propertyImages', 'reviewee'])
                    ->where('reviewer_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            } else {
                $reviews = Review::with(['property.propertyImages', 'reviewer'])
                    ->where('reviewee_id', $user->id)
                    ->where('status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            }

            return response()->json(['status' => 200, 'data' => $reviews]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'lease_agreement_id' => 'required|exists:lease_agreements,id',
            'review_type' => 'required|in:renter_to_landlord,landlord_to_renter,renter_to_property',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'location_rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();

            $lease = LeaseAgreement::where(function ($q) use ($user) {
                $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
            })
                ->whereIn('status', ['active', 'expired', 'terminated'])
                ->findOrFail($request->lease_agreement_id);

            $existing = Review::where('property_id', $request->property_id)
                ->where('reviewer_id', $user->id)
                ->where('review_type', $request->review_type)
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 409,
                    'message' => 'You have already submitted a review of this type for this property.'
                ]);
            }

            if ($request->review_type === 'renter_to_landlord' || $request->review_type === 'renter_to_property') {
                if ($user->id !== $lease->renter_id) {
                    return response()->json(['status' => 403, 'message' => 'Only renters can submit this review type.']);
                }
                $revieweeId = $lease->landlord_id;
            } else {
                if ($user->id !== $lease->landlord_id) {
                    return response()->json(['status' => 403, 'message' => 'Only landlords can submit this review type.']);
                }
                $revieweeId = $lease->renter_id;
            }

            $review = Review::create([
                'property_id' => $request->property_id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $revieweeId,
                'lease_agreement_id' => $lease->id,
                'review_type' => $request->review_type,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'cleanliness_rating' => $request->cleanliness_rating,
                'communication_rating' => $request->communication_rating,
                'value_rating' => $request->value_rating,
                'location_rating' => $request->location_rating,
                'status' => 'published',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Review submitted successfully!',
                'data' => $review->load(['property', 'reviewer', 'reviewee'])
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function propertyReviews($propertyId)
    {
        try {
            $reviews = Review::with(['reviewer'])
                ->where('property_id', $propertyId)
                ->where('status', 'published')
                ->whereIn('review_type', ['renter_to_property', 'renter_to_landlord'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $stats = Review::where('property_id', $propertyId)
                ->where('status', 'published')
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    ROUND(AVG(rating), 1) as avg_rating,
                    ROUND(AVG(cleanliness_rating), 1) as avg_cleanliness,
                    ROUND(AVG(communication_rating), 1) as avg_communication,
                    ROUND(AVG(value_rating), 1) as avg_value,
                    ROUND(AVG(location_rating), 1) as avg_location
                ')
                ->first();

            return response()->json([
                'status' => 200,
                'data' => $reviews,
                'stats' => $stats
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function userReviews($userId)
    {
        try {
            $reviews = Review::with(['reviewer', 'property.propertyImages'])
                ->where('reviewee_id', $userId)
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $avgRating = Review::where('reviewee_id', $userId)
                ->where('status', 'published')
                ->avg('rating');

            return response()->json([
                'status' => 200,
                'data' => $reviews,
                'avg_rating' => round($avgRating, 1)
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
