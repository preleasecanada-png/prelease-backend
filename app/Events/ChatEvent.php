<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $receiverId;

    public function __construct($user)
    {
        $this->user = $user;
    }

    // public function broadcastOn()
    // {
    //     return new PrivateChannel('chat');
    // }
    // public function broadcastWith()
    // {
    //     return [
    //         'message' => $this->user,
    //     ];
    // }
    public function broadcastOn()
    {
        return [
            new PrivateChannel('chat.' . $this->user->received_id),
            new Channel('chat-notify.' . $this->user->received_id),
        ];
    }

    public function broadcastWith()
    {
        return [
        'message' => [
            'id'          => $this->user->id,
            'sender_id'   => $this->user->sender_id,
            'received_id' => $this->user->received_id,
            'type'        => $this->user->type,
            'text'        => $this->user->message,
            'created_at'  => $this->user->created_at,
        ],
    ];
    }
}
