<?php

namespace App\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ChatNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;
    public $sender;
    public $receiver;
    public string $message;
    public $date;
    public $time;
    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        //
        $this->message = $data['message'];
        $this->sender = $data['sender'];
        $this->receiver = $data['receiver'];

        $this->date = date('Y-m-d');
        $this->time = date('H:i:s');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => $this->message,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'date' => $this->date,
            'time' => $this->time,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'date' => $this->date,
            'time' => $this->time,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('chat-channel');
    }

    public function broadcastAs()
    {
        return 'chat-event';
    }
}
