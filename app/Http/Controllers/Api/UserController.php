<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;

use App\Services\AuthService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $authService;

    public function __construct()
    {
        $this->middleware('role:patient', [
            'only' => ['me', 'update']
        ]);

        $this->authService = new AuthService(new User());
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new UserCollection(User::paginate(4));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function register(Request $request)
    {
        // Register User
        return $this->authService->register($request);
    }

    public function login(Request $request)
    {
        // Login User
        return $this->authService->login($request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }

    public function verifyEmail(Request $request)
    {
        return $this->authService->verifyEmail($request);
    }

    public function resendOtp(Request $request)
    {
        return $this->authService->resendOtp($request);
    }

    public function forgotPassword(Request $request)
    {
        return $this->authService->forgotPassword($request);
    }

    public function resetPassword(Request $request)
    {
        return $this->authService->resetPassword($request);
    }

    /**
     * Display the specified resource.
     */
    public function me(Request $request)
    {
        return response()->json([
            "data" => new UserResource($request->user())
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate($request->user()->updateValidators());

        $patient = $request->user();

        $patient->fill($validated);

        if ($request->hasFile('photo')) {
            // First delete the old photo from cloudinary
            // Why this error: "Missing required parameter - public_id?
            // - The error is because the photo field is empty, so it's trying to delete a photo that doesn't exist

            if ($patient->photo) {
                $publicId = pathinfo($patient->photo, PATHINFO_FILENAME);
                Cloudinary::destroy($publicId);
            }

            $imageUrl = Cloudinary::upload($request->file('photo')->getRealPath(), [
                "folder" => "patients"
            ])->getSecurePath();

            $patient->photo = $imageUrl;
        }

        $patient->save();

        return response()->json([
            "data" => new UserResource($patient)
        ]);
    }

    public function delete(Request $request)
    {
        $patient = $request->user();

        if ($patient->photo) {
            $publicId = pathinfo($patient->photo, PATHINFO_FILENAME);
            Cloudinary::destroy($publicId);
        }

        $patient->delete();

        // Delete All Tokens
        $patient->tokens()->delete();

        return response()->json([
            "message" => "Patient deleted successfully"
        ]);
    }

    // Get all contacts for a user
    public function contacts(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'user_id' => $user->id,
            "data" => $user->contacts
        ]);
    }

    // Get Single Contact
    public function contact(Request $request, string $id, string $contact_id)
    {
        $user = User::findOrFail($id);
        $contact = $user->contacts()->findOrFail($contact_id);

        return response()->json([
            'user_id' => $user->id,
            "data" => $contact
        ]);
    }

    // Get falls for a user
    public function falls(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            "data" => $user->falls
        ]);
    }

    // Get Single Patient
    public function show(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        // return $request->query('deep');

        if ($request->query('deep') === 'true') {
            $user = $user->load('contacts', 'falls');
            return $user;
        }

        return response()->json([
            "data" => new UserResource($user)
        ]);
    }

    // Follow Caregiver 
    // public function follow(Request $request, $id)
    // {
    //     $caregiver = Caregiver::find($id);

    //     if (!$caregiver) {
    //         return response()->json([
    //             "errors" => [
    //                 "message" => "Caregiver not found"
    //             ]
    //         ], 404);
    //     }
    //     // Check if Caregiver already following the patient
    //     if ($request->user()->caregivers()->find($id)) {
    //         return response()->json([
    //             "errors" => [
    //                 "message" => "Caregiver already following the patient"
    //             ]
    //         ], 400);
    //     }

    //     $request->user()->caregivers()->attach($caregiver);
    //     // Follow Notification
    //     $caregiver->notify(new FollowNotification("Patient " . $request->user()->name . " is now following you"));

    //     return response()->json([
    //         "message" => "Caregiver followed successfully"
    //     ]);
    // }


    // public function unfollow(Request $request, $id)
    // {
    //     $caregiver = Caregiver::find($id);

    //     if (!$caregiver) {
    //         return response()->json([
    //             "errors" => [
    //                 "message" => "Caregiver not found"
    //             ]
    //         ], 404);
    //     }

    //     // Check if paitnet already unfollowing the caregiver
    //     if(!$request->user()->caregiver()->find($id)) {
    //         return response()->json([
    //             "errors" => [
    //                 "message" => "Caregiver already unfollowing the patient"
    //             ]
    //         ], 400);
    //     }

    //     $request->user()->patients()->detach($patient);

    //     return response()->json([
    //         "message" => "Patient unfollowed successfully"
    //     ]);
    // }
}
