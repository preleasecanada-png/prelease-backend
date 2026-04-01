<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('private-chat.{userId}', function ($user, $userId) {
//     return (int) $user->id === (int) $userId;
// });

// // Or for the specific channel 'private-chat.4':
// Broadcast::channel('private-chat.4', function ($user) {
//     return auth()->check() && auth()->user()->id === 4; // Example: Only user with ID 4 can subscribe
// });


Broadcast::channel('chat', function ($user) {
    return true;
});
Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});
