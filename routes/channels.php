<?php

use Illuminate\Support\Facades\Broadcast;

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('private-chat.{sender_id}.{receiver_id}', function ($user, $sender_id, $receiver_id) {
    // Implement your logic to determine if the user is authorized to listen to this channel.
    // For example, check if the user is either the patient or the caregiver involved in the chat.
    return $user->id === (int) $sender_id || $user->id === (int) $receiver_id;
});

Broadcast::channel('fallyguard-notify', function ($user) {
    return true; // You can implement your own authorization logic here if needed
});

Broadcast::channel('fall-channel', function ($user) {
    return true; // You can implement your own authorization logic here if needed
});

Broadcast::channel('follow-channel', function ($user) {
    return true; // You can implement your own authorization logic here if needed
});