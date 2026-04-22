<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Events\ChatEvent;
use App\Events\SimpleAlertEvent;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserChatController extends Controller
{
    public function user_chat(Request $request)
    {
        \Log::info('UserChatController@user_chat called with data: ', [$request->all()]);
        $userChat = new UserChat();
        if ($request->type == 'text') {
            $userChat->sender_id = $request->sender_id;
            $userChat->received_id = $request->received_id;
            $userChat->message = $request->message;
            $userChat->type = $request->type;
            $userChat->save();
        } else {
            $userChat->sender_id = $request->sender_id;
            $userChat->received_id = $request->received_id;
            if ($request->hasFile('voice')) {
                $file = $request->file('voice');
                $filename = 'images/message-voices/' . uniqid('voice_') . '.' . $file->getClientOriginalExtension();
                $file->move('images/message-voices/', $filename);
                $userChat->message = $filename;
                $userChat->type = 'voice';
                $userChat->save();
            }
        }
        $user  = $userChat;
        try { broadcast(new ChatEvent($user))->toOthers(); } catch (\Throwable $e) { \Log::warning('Broadcast failed: '.$e->getMessage()); }
        return response()->json(['status' => 200, 'message' => 'User message send successfully!', 'data' => $userChat]);
    }

    public function send_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'received_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $userChat = new UserChat();
            $userChat->sender_id = Auth::id();
            $userChat->received_id = $request->received_id;
            $userChat->message = $request->message;
            $userChat->type = 'text';
            $userChat->save();

            try { broadcast(new ChatEvent($userChat))->toOthers(); } catch (\Throwable $e) { \Log::warning('Broadcast failed: '.$e->getMessage()); }

            return response()->json([
                'status' => 200,
                'message' => 'User message sent successfully!',
                'data' => $userChat
            ]);

        } catch (\Exception $e) {

            \Log::error('Chat send error: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getChats(Request $request)
    {
        $me      = $request->user()->id;
        $other   = $request->query('user_id');

        $chats = UserChat::where(function($q) use ($me, $other) {
                    $q->where('sender_id', $me)->where('received_id', $other);
                })
                ->orWhere(function($q) use ($me, $other) {
                    $q->where('sender_id', $other)->where('received_id', $me);
                })
                ->orderBy('created_at')
                ->get();

        return response()->json(['status' => 200, 'data' => $chats]);
    }

    public function reserve(Request $request)
    {
        try{

            $renterId = Auth::id();
            $startDate = Carbon::parse($request->start_date);
            $duration  = (int) $request->tenure;
            $endDate = $startDate->copy()->addMonths($duration);
            
            $booking = Booking::updateOrCreate(
                [
                    'property_id' => $request->property_id,
                    'renter_id'   => $renterId,
                    'status'      => ['pending', 'negotiating', 'payment_pending'],
                ],
                [
                    'landlord_id'   => $request->landlord_id,
                    'move_in_date'  => $request->start_date,
                    'move_out_date' => $endDate,
                    'duration'      => $request->tenure,
                    'guests'        => $request->guests,
                    'adult_count'   => $request->adult_count,
                    'child_count'   => $request->child_count,
                    'pets_count'    => $request->pets_count,
                    'infront_count' => $request->infront_count,
                    'price_agreed'  => 0,
                    'status'        => 'negotiating',
                ]
            );

            $existing = UserChat::where('sender_id', $renterId)
                        ->where('received_id', $request->landlord_id)
                        ->where('type', 'reservation_request')
                        ->first();

            if (! $existing) {
                $chat = UserChat::create([
                    'sender_id'   => $renterId,
                    'received_id' => $request->landlord_id,
                    'message'     => "Hi, I want to book this property from {$startDate->toDateString()} to {$endDate->toDateString()}.",
                    'type'        => 'reservation_request',
                ]);

                try { broadcast(new ChatEvent($chat))->toOthers(); } catch (\Throwable $e) { \Log::warning('Broadcast failed: '.$e->getMessage()); }
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Reservation created (pending)',
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            \Log::error('Reservation error: '.$e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong' . $e->getMessage()
            ], 500);
        }
    }
    // public function user_chat(Request $request)
    // {
    //     $userChat = new UserChat();
    //     $userChat->sender_id = $request->sender_id;
    //     $userChat->received_id = $request->received_id;
    //     $userChat->type = $request->type;
    //     switch ($request->type) {
    //         case 'text':
    //             $userChat->message = $request->message;
    //             break;
    //         case 'voice':
    //             if ($request->hasFile('voice')) {
    //                 $file = $request->file('voice');
    //                 $filename = 'images/message-voices/' . uniqid('voice_') . '.' . $file->getClientOriginalExtension();
    //                 $file->move('images/message-voices/', $filename);
    //                 $userChat->message = $filename;
    //             }
    //             break;
    //         case 'photo':
    //             if ($request->hasFile('file')) {
    //                 $file = $request->file('file');
    //                 $filename = 'images/message-photos/' . uniqid('photo_') . '.' . $file->getClientOriginalExtension();
    //                 $file->move('images/message-photos/', $filename);
    //                 $userChat->message = $filename;
    //             }
    //             break;
    //         case 'document':
    //             if ($request->hasFile('file')) {
    //                 $file = $request->file('file');
    //                 $filename = 'images/message-docs/' . uniqid('doc_') . '.' . $file->getClientOriginalExtension();
    //                 $file->move('images/message-docs/', $filename);
    //                 $userChat->message = $filename;
    //             }
    //             break;
    //         case 'location':
    //             $userChat->message = $request->message;
    //             break;
    //         case 'contact':
    //             if ($request->hasFile('file')) {
    //                 $file = $request->file('file');
    //                 $filename = 'images/message-contacts/' . uniqid('contact_') . '.' . $file->getClientOriginalExtension();
    //                 $file->move('images/message-contacts/', $filename);
    //                 $userChat->message = $filename;
    //             }
    //             break;
    //     }
    //     $userChat->save();

    //     broadcast(new ChatEvent($userChat))->toOthers();
    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'User message sent successfully!',
    //         'data' => $userChat
    //     ]);
    // }
    /**
     * Get unread message count for the authenticated user
     */
    public function unreadCount()
    {
        $count = UserChat::where('received_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['status' => 200, 'count' => $count]);
    }

    /**
     * Mark messages from a specific user as read
     */
    public function markRead(Request $request)
    {
        $peerId = $request->user_id;
        UserChat::where('sender_id', $peerId)
            ->where('received_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 200, 'message' => 'Messages marked as read']);
    }

    /**
     * Get conversations list with last message and unread count per conversation
     */
    public function conversations()
    {
        $me = Auth::id();

        // Get all distinct users I've chatted with
        $senderIds = UserChat::where('received_id', $me)->distinct()->pluck('sender_id');
        $receiverIds = UserChat::where('sender_id', $me)->distinct()->pluck('received_id');
        $peerIds = $senderIds->merge($receiverIds)->unique()->values();

        $conversations = [];
        
        // Get pinned users for this user
        $pinnedUsers = \App\Models\UserPinnedChat::where('user_id', $me)->pluck('pinned_user_id')->toArray();

        foreach ($peerIds as $peerId) {
            $lastMessage = UserChat::where(function ($q) use ($me, $peerId) {
                $q->where('sender_id', $me)->where('received_id', $peerId);
            })->orWhere(function ($q) use ($me, $peerId) {
                $q->where('sender_id', $peerId)->where('received_id', $me);
            })->orderBy('created_at', 'desc')->first();

            $unread = UserChat::where('sender_id', $peerId)
                ->where('received_id', $me)
                ->whereNull('read_at')
                ->count();

            $user = User::select('id', 'first_name', 'last_name', 'email', 'picture', 'role')
                ->find($peerId);

            if ($user && $lastMessage) {
                $conversations[] = [
                    'user' => $user,
                    'last_message' => $lastMessage,
                    'unread_count' => $unread,
                    'is_pinned' => in_array($peerId, $pinnedUsers)
                ];
            }
        }

        // Sort by pinned first, then last message date descending
        usort($conversations, function ($a, $b) {
            if ($a['is_pinned'] && !$b['is_pinned']) return -1;
            if (!$a['is_pinned'] && $b['is_pinned']) return 1;
            return strtotime($b['last_message']['created_at']) - strtotime($a['last_message']['created_at']);
        });

        return response()->json(['status' => 200, 'data' => $conversations]);
    }

    public function markUnread(Request $request)
    {
        $peerId = $request->user_id;
        
        // Mark the last message as unread by setting read_at to null
        $lastMessage = UserChat::where('sender_id', $peerId)
            ->where('received_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastMessage) {
            $lastMessage->read_at = null;
            $lastMessage->save();
        }

        return response()->json(['status' => 200, 'message' => 'Conversation marked as unread']);
    }
    
    public function pinConversation(Request $request)
    {
        $me = Auth::id();
        $peerId = $request->user_id;
        
        $pinned = \App\Models\UserPinnedChat::where('user_id', $me)
                                            ->where('pinned_user_id', $peerId)
                                            ->first();
                                            
        if ($pinned) {
            $pinned->delete();
            return response()->json(['status' => 200, 'message' => 'Conversation unpinned']);
        } else {
            \App\Models\UserPinnedChat::create([
                'user_id' => $me,
                'pinned_user_id' => $peerId
            ]);
            return response()->json(['status' => 200, 'message' => 'Conversation pinned']);
        }
    }
    
    public function deleteConversation(Request $request)
    {
        $me = Auth::id();
        $peerId = $request->user_id;
        
        // Hard delete all messages between these two users
        UserChat::where(function ($q) use ($me, $peerId) {
            $q->where('sender_id', $me)->where('received_id', $peerId);
        })->orWhere(function ($q) use ($me, $peerId) {
            $q->where('sender_id', $peerId)->where('received_id', $me);
        })->delete();
        
        return response()->json(['status' => 200, 'message' => 'Conversation deleted']);
    }

    protected function users()
    {
        try {
            $users = User::where('role', '!=', 'admin')->get();
            return response()->json(['status' => 200, 'data' => $users]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 404, 'error' => $th->getMessage()]);
        }
    }
    protected function user_detail($id)
    {
        try {
            $me = Auth::id();
            $user = User::where('id', $id)->first();
            if (!$user) {
                return response()->json(['status' => 404, 'message' => 'User not found']);
            }

            // Only load messages between the authenticated user and this user
            $user->load([
                'receivedMessages' => function ($q) use ($me) {
                    $q->where('sender_id', $me)->orderBy('created_at');
                },
                'senderMessages' => function ($q) use ($me) {
                    $q->where('received_id', $me)->orderBy('created_at');
                }
            ]);

            return response()->json(['status' => 200, 'data' => $user]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 404, 'error' => $th->getMessage()]);
        }
    }
}
