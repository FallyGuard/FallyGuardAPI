<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $user = Socialite::driver('google')->stateless()->user();

        $userExisted = Caregiver::where('provider_id', $user->id)->first();

        if( $userExisted ) {
            $token = $userExisted->createToken($request->userAgent(), ['*'])->plainTextToken;

            return response()->json([
                'message' => 'Logged in',
                'status' => true,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        }else {
            $newUser = Caregiver::create([
                'name' => $user->name,
                'phone' => "+20", // How to get the phone number from google?
                'email' => $user->email,
                'password' => Hash::make($user->id),
                'provider_id' => $user->id,
                'provider' => 'google',
            ]);

            $token = $newUser->createToken($request->userAgent(), ['*'])->plainTextToken;

            // verify the user
            $newUser->markEmailAsVerified();

            return response()->json([
                'message' => 'Logged in',
                'status' => true,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        }
    }
}