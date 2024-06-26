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
        $receiver = $receiver::find($receiver_id);

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
        // Load User, Caregiver Details in Response
        $messages = Message::where(function ($query) use ($request, $other_id) {
            $query->where('sender_id', $request->user()->id)
                ->where('receiver_id', $other_id);
        })->orWhere(function ($query) use ($request, $other_id) {
            $query->where('sender_id', $other_id)
                ->where('receiver_id', $request->user()->id);
        })->get()->map(function ($message) {
            $sender = $message->sender_type === 'caregiver'
                ? Caregiver::find($message->sender_id, ['id', 'name', 'photo'])
                : User::find($message->sender_id, ['id', 'name', 'photo']);

            $receiver = $message->receiver_type === 'caregiver'
                ? Caregiver::find($message->receiver_id, ['id', 'name', 'photo'])
                : User::find($message->receiver_id, ['id', 'name', 'photo']);

            return [
                ...$message->toArray(),
                'sender' => $sender,
                'receiver' => $receiver,
            ];
        });

        return response()->json(['data' => $messages]);
    }

    public function latestChats(Request $request)
    {
        $currentUserId = auth()->id();
        
        $chats = DB::table('messages')
            ->select('sender_id', 'sender_type', 'receiver_id', 'receiver_type', 'message', 'created_at')
            ->where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chat) {
                // sender_id not always be like current user
                $sender = $chat->sender_type === 'caregiver'
                    ? Caregiver::find($chat->sender_id)->only(['id', 'name', 'photo'])
                    : User::find($chat->sender_id)->only(['id', 'name', 'photo']);

                $receiver = $chat->receiver_type === 'caregiver'
                    ? Caregiver::find($chat->receiver_id)->only(['id', 'name', 'photo'])
                    : User::find($chat->receiver_id)->only(['id', 'name', 'photo']);
                

                $info = auth()->id() === $chat->sender_id ? $receiver : $sender;

                return [
                    "url" => env("APP_URL") . "/api/v1/chat/{$chat->receiver_id}",
                    'message' => $chat->message,
                    "between" => [
                        'sender' => $sender,
                        'receiver' => $receiver,
                    ],
                    "info" => [...$info],
                    'created_at' => $chat->created_at,
                ];
            });
        
    return response()->json([
        'status' => 'success',
        'chats' => $chats,
    ]);
    }
}