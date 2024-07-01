<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\FollowNotification;
use Illuminate\Http\Request;

use App\Http\Resources\Caregiver\CaregiverResource;
use App\Models\Caregiver;
use App\Models\User;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

use App\Services\AuthService;

class CaregiverController extends Controller
{

    private $authService;

    public function __construct()
    {
        $this->middleware('role:caregiver', ['except' => ['register', 'login', 'verifyEmail', 'resendOtp', 'forgotPassword', 'resetPassword']]);
        $this->middleware('verified', ['except' => ['register', 'login', 'verifyEmail', 'resendOtp', 'forgotPassword', 'resetPassword']]);

        // $this->authService = new AuthService(Caregiver::class);
        $this->authService = new AuthService(new Caregiver());
    }

    // ============================= Caregiver =============================
    // Get all caregivers
    public function index()
    {
        return CaregiverResource::collection(Caregiver::all());
    }

    public function register(Request $request)
    {
        // Register Caregiver
        return $this->authService->register($request);
    }

    public function login(Request $request)
    {
        // Login Caregiver - Validate request
        return $this->authService->login($request);
    }

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

    public function me(Request $request)
    {
        if ($request->query('deep') === 'true')
            $request->user()->load('patients');

        return response()->json([
            "data" => new CaregiverResource($request->user())
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate($request->user()->updateValidators());

        $caregiver = $request->user();

        $caregiver->fill($validated);

        if ($request->hasFile('photo')) {
            // First delete the old photo from cloudinary
            // Why this error: "Missing required parameter - public_id?
            // - The error is because the photo field is empty, so it's trying to delete a photo that doesn't exist

            if ($caregiver->photo) {
                $publicId = pathinfo($caregiver->photo, PATHINFO_FILENAME);
                Cloudinary::destroy($publicId);
            }

            $imageUrl = Cloudinary::upload($request->file('photo')->getRealPath(), [
                "folder" => "caregivers"
            ])->getSecurePath();
            $caregiver->photo = $imageUrl;
        }

        $caregiver->save();

        return response()->json([
            "data" => new CaregiverResource($caregiver)
        ]);
    }

    public function delete(Request $request)
    {
        $caregiver = $request->user();

        if ($caregiver->photo) {
            $publicId = pathinfo($caregiver->photo, PATHINFO_FILENAME);
            Cloudinary::destroy($publicId);
        }

        $caregiver->delete();

        // Delete All Tokens
        $caregiver->tokens()->delete();

        return response()->json([
            "message" => "Caregiver deleted successfully"
        ]);
    }

    // ============================= Relationships =============================

    // Get all patients which caregiver is following
    public function patients(Request $request)
    {
        return response()->json([
            "data" => $request->user()->patients
        ]);
    }

    // Get a specific patient which caregiver is following
    public function patient(Request $request, $id)
    {
        $patient = $request->user()->patients()->find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }

        return response()->json([
            "data" => $patient
        ]);
    }

    // Get all contacts for a specific patient
    public function contacts(Request $request, $id)
    {
        $patient = $request->user()->patients()->find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }

        return response()->json([
            "data" => $patient->contacts
        ]);
    }

    // Get a specific contact for a specific patient
    public function contact(Request $request, $id, $contact_id)
    {
        $patient = $request->user()->patients()->find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }

        $contact = $patient->contacts()->find($contact_id);

        if (!$contact) {
            return response()->json([
                "errors" => [
                    "message" => "Contact not found"
                ]
            ], 404);
        }

        return response()->json([
            "data" => $contact
        ]);
    }

    // Get all falls for a specific patient
    public function falls(Request $request, $id)
    {
        $patient = $request->user()->patients()->find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }

        return response()->json([
            "data" => $patient->falls()->orderBy('created_at', 'desc')->get()
        ]);
    }


    // ============================= Follow System =============================
    // Follow System
    public function follow(Request $request, $id)
    {
        $patient = User::find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }
        // Check if Caregiver already following the patient
        if ($request->user()->patients()->find($id)) {
            return response()->json([
                "errors" => [
                    "message" => "Caregiver already following the patient"
                ]
            ], 400);
        }

        $request->user()->patients()->attach($patient);

        // Follow Notification
        // $patient->notify(new FollowNotification("Caregiver " . $request->user()->name . " is now following you"));

        return response()->json([
            "message" => "Patient followed successfully"
        ]);
    }


    // Unfollow System
    public function unfollow(Request $request, $id)
    {
        $patient = User::find($id);

        if (!$patient) {
            return response()->json([
                "errors" => [
                    "message" => "Patient not found"
                ]
            ], 404);
        }

        // Check if Caregiver already unfollowing the patient
        if (!$request->user()->patients()->find($id)) {
            return response()->json([
                "errors" => [
                    "message" => "Caregiver already unfollowing the patient"
                ]
            ], 400);
        }

        $request->user()->patients()->detach($patient);
        // Unfollow Notification
        // $patient->notify(new FollowNotification("Caregiver " . $request->user()->name . " is now unfollowing you"));

        return response()->json([
            "message" => "Patient unfollowed successfully"
        ]);
    }
}
