<?php

namespace App\Services;

use App\Contracts\AuthenticationInterface;

use App\Models\Caregiver;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;

use Ichtrojan\Otp\Otp;
use Illuminate\Http\Request;

use Illuminate\Foundation\Auth\User as Authenticatable;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

class AuthService implements AuthenticationInterface
{
    private $user;
    private $model;

    // TODO: prefered to accept the class name as a string
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
        $this->model = get_class($user);
    }

    public function register(Request $request)
    {
        $request->validate($this->model::validators());

        // $uploadedFileUrl = Cloudinary::upload($request->file('file')->getRealPath())->getSecurePath();
        $User = $this->model::make($request->except('photo')); // make:: return a new instance of the model

        if ($request->hasFile('photo')) {
            $imageUrl = Cloudinary::upload($request->file('photo')->getRealPath(), [
                'folder' => $this->user->role,
            ])->getSecurePath();
            $User->photo = $imageUrl;
        }

        $User->password = \Hash::make($request->password);
        $User->save();

        // Notify the caregiver to verify their email
        $User->notify(new EmailVerificationNotification());
        $data = $User->toArray();

        return response()->json(
            [
                'message' => "{$this->user->role} registered successfully. Please verify your email.",
                'email' => $User->email,
                'data' => $data,
            ],
            201,
        );
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'string',
        ]);

        // Check if the caregiver exists
        $User = $this->model::where('email', $request->email)->first();

        // Check if the caregiver exists and the password is correct
        if (!$User || !\Hash::check($request->password, $User->password)) {
            return response()->json(
                [
                    'errors' => [
                        'message' => 'The provided credentials are incorrect.',
                    ],
                ],
                401,
            );
        }

        // Check if the caregiver has verified their email
        if ($User->email_verified_at === null) {
            return response()->json(
                [
                    'errors' => [
                        'message' => 'Please verify your email.',
                    ],
                ],
                401,
            );
        }

        // Remove all tokens registered in database if exist
        $User->tokens()->delete();

        // Create token
        $token = $User->createToken($request->device_name ?? $request->userAgent(), ['*'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $User->toArray(),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        // current user
        $User = $this->user;
        $UserTable = $User->getTable();

        $request->validate([
            'email' => "required|email|exists:{$UserTable},email",
            'otp' => 'required|string|max:4',
        ]);

        if (!(new Otp())->validate($request->email, $request->otp)->status) {
            return response()->json([
                'errors' => [
                    'status_message' => 'error',
                    'status_code' => 400,
                    'message' => 'Invalid\Expired OTP, please resend another one.',
                ],
            ], 400);
        }

        // Remove All Old OTPs in database
        DB::table('otps')->where('email', $request->email)->delete();

        $this->model::where('email', $request->email)->update(['email_verified_at' => now()]);

        return response()->json([
            'status_message' => 'success',
            'status_code' => 200,
            'message' => 'Email verified, you can now login.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $User = $this->user;
        $UserTable = $User->getTable();

        $request->validate([
            'email' => "required|email|exists:{$UserTable},email",
        ]);

        $user = $this->model::where('email', $request->email)->first();
        $user->notify(new ResetPasswordNotification());

        return response()->json([
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $User = $this->user;
        $UserTable = $User->getTable();

        $request->validate([
            'email' => "required|email|exists:{$UserTable},email",
            'otp' => 'required|string|max:6',
            'password' => 'required|string|confirmed',
        ]);

        $user = $this->model::where('email', $request->email)->first();
        // Is OTP owned by the user? validate

        if (!(new Otp())->validate($request->email, $request->otp)->status) {
            return response()->json([
                'errors' => [
                    'message' => 'Invalid\Expired OTP, please resend another one.',
                ],
            ], 400);
        }

        $user->password = \Hash::make($request->password);
        $user->save();

        // You can also send a notification to the user that their password has been reset

        return response()->json([
            'message' => 'Password reset successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $User = $this->user;
        $UserTable = $User->getTable();

        $request->validate([
            'email' => "required|email|exists:{$UserTable},email",
            'type' => 'required|string|in:reset-password,verify-email',
        ]);

        $user = $this->model::where('email', $request->email)->first();

        if ($request->type == 'reset-password') {
            $user->notify(new ResetPasswordNotification());
        } else {
            $user->notify(new EmailVerificationNotification());
        }

        // Remove All Old OTPs in database
        DB::table('otps')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function getRoleAttribute()
    {
        return $this->user->role;
    }
}
