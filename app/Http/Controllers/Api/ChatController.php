<?php

namespace App\Http\Controllers\Api;

use App\Events\SentMessage;
use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $receiver_id)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        // Check if Receiver exists
        $receiver = $request->user()->role === 'caregiver' ? User::class : Caregiver::class;
        $receiver = $receiver::find($receiver_id, [
            'id',
            'name',
            'photo',
        ]);
        
        if (!$receiver) {
            return response()->json(['message' => 'Receiver not found'], 404);
        }

        $message = Message::create([
            'message' => $validated['message'],
            'sender_id' => $request->user()->id,
            'sender_type' => $request->user()->role,
            'receiver_id' => +$receiver_id,
            'receiver_type' => $request->user()->role === 'caregiver' ? 'patient' : 'caregiver',
        ]);

        // Optionally, broadcast the message event
        broadcast(new SentMessage([
            ...$message->toArray(),
            'sender' => $request->user(),
            'receiver' => $receiver,

        ]))->toOthers();

        return response()->json(['message' => [
            ...$message->toArray(),
            'sender' => $request->user(),
            'receiver' => $receiver,
        ]], 201);
    }

    public function getMessagesOfOtherUser(Request $request, $other_id)
    {
        $messages = Message::where(function ($query) use ($request, $other_id) {
            $query->where('sender_id', $request->user()->id)
                ->where('receiver_id', $other_id);
        })->orWhere(function ($query) use ($request, $other_id) {
            $query->where('sender_id', $other_id)
                ->where('receiver_id', $request->user()->id);
        })->get();

        return response()->json(['data' => $messages]);
    }

    public function latestChats(Request $request)
    {
        $chats = Message::where('receiver_id', $request->user()->id)
            ->orWhere('sender_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $chats]);
    }
}