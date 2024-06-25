<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SentMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $receiver;
    public string $message;
    public $date;
    public $time;

    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        $this->message = $data['message'];
        $this->sender = $data['sender'];
        $this->receiver = $data['receiver'];

        $this->date = date('Y-m-d');
        $this->time = date('H:i:s');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("chat.{$this->sender->id}.{$this->receiver->id}");
    }

    public function broadcastAs(): string
    {
        return 'message-sent';
    }
}
