<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\RentalApplication;
use App\Models\Property;
use App\Models\WishList;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'status' => 200,
                'data' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notification->update(['is_read' => true]);

            return response()->json(['status' => 200, 'message' => 'Notification marked as read']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['status' => 200, 'message' => 'All notifications marked as read']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function unreadCount()
    {
        try {
            $user = Auth::user();
            $count = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json(['status' => 200, 'count' => $count]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->delete();

            return response()->json(['status' => 200, 'message' => 'Notification deleted']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Trigger proactive notifications for the authenticated user
     */
    public function proactive()
    {
        try {
            $user = Auth::user();
            $created = [];

            if ($user->role === 'host') {
                $created = array_merge($created, $this->remindPendingApplications($user));
                $created = array_merge($created, $this->remindOpenMaintenance($user));
            } else {
                $created = array_merge($created, $this->alertSimilarProperties($user));
            }

            return response()->json([
                'status' => 200,
                'notifications_created' => count($created),
                'data' => $created,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    private function remindPendingApplications($user)
    {
        $created = [];
        $pending = RentalApplication::with('property')
            ->where('landlord_id', $user->id)
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        foreach ($pending as $app) {
            $exists = Notification::where('user_id', $user->id)
                ->where('type', 'application_reminder')
                ->where('link', '/applications')
                ->where('message', 'like', "%#{$app->id}%")
                ->where('created_at', '>=', now()->subDays(3))
                ->exists();

            if (!$exists) {
                $n = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Pending Application',
                    'message' => "You have a pending application #{$app->id} for \"{$app->property->title}\" waiting for review.",
                    'type' => 'application_reminder',
                    'link' => '/applications',
                    'is_read' => false,
                ]);
                $created[] = $n;
            }
        }
        return $created;
    }

    private function remindOpenMaintenance($user)
    {
        $created = [];
        $open = MaintenanceRequest::where('landlord_id', $user->id)
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(48))
            ->get();

        foreach ($open as $req) {
            $exists = Notification::where('user_id', $user->id)
                ->where('type', 'maintenance_reminder')
                ->where('message', 'like', "%#{$req->id}%")
                ->where('created_at', '>=', now()->subDays(3))
                ->exists();

            if (!$exists) {
                $n = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Maintenance Needs Attention',
                    'message' => "Maintenance request #{$req->id} \"{$req->title}\" has been pending for over 48 hours.",
                    'type' => 'maintenance_reminder',
                    'link' => '/maintenance',
                    'is_read' => false,
                ]);
                $created[] = $n;
            }
        }
        return $created;
    }

    private function alertSimilarProperties($user)
    {
        $created = [];
        $wishlistPropertyIds = WishList::where('user_id', $user->id)->pluck('property_id');
        if ($wishlistPropertyIds->isEmpty()) return $created;

        $wishedCities = Property::whereIn('id', $wishlistPropertyIds)->pluck('city')->unique()->filter();
        if ($wishedCities->isEmpty()) return $created;

        $newProperties = Property::whereIn('city', $wishedCities)
            ->whereNotIn('id', $wishlistPropertyIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->take(3)
            ->get();

        foreach ($newProperties as $prop) {
            $exists = Notification::where('user_id', $user->id)
                ->where('type', 'similar_property')
                ->where('message', 'like', "%{$prop->id}%")
                ->exists();

            if (!$exists) {
                $n = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'New Property in Your Area',
                    'message' => "A new property \"{$prop->title}\" is now available in {$prop->city} — similar to your wishlist!",
                    'type' => 'similar_property',
                    'link' => "/property-detail/{$prop->slug}/{$prop->id}",
                    'is_read' => false,
                ]);
                $created[] = $n;
            }
        }
        return $created;
    }
}
