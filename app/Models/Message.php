<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    // public function sender()
    // {
    //     return $this->morphTo();
    // }

    // public function receiver()
    // {
    //     return $this->morphTo();
    // }

    public function toArray() {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'sender_id' => +$this->sender_id,
            'sender_type' => $this->sender_type,
            'receiver_id' => +$this->receiver_id,
            'receiver_type' => $this->receiver_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
