<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaregiverResource;
use App\Models\Caregiver;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use App\Rules\GenderValidateRule;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;

class CaregiverController extends Controller
{
    public function __construct(){
        $this->middleware('role:caregiver', ['except' => ['register', 'login']]);    
        $this->middleware('check.token:Caregiver', ['only' => ['register', 'login']]);
        $this->middleware('verified', ['except' => ['register']]);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:caregivers,email',
            // checks if the password contains at least one lowercase letter, one uppercase letter, and one number
            'password' => 'required|string|big_password|min:8',
            "date_of_birth" => "sometimes|required|date",
            'phone' => 'required|string|regex:/^01[0-2]{1}[0-9]{8}$/',
            "gender" => ["required", new GenderValidateRule],
            "country" => "sometimes|required|string|max:255",
            'address' => 'sometimes|required|string|max:255',
            'photo' => 'sometimes|required|file|max:255',
        ]);

        // $uploadedFileUrl = Cloudinary::upload($request->file('file')->getRealPath())->getSecurePath();
        $caregiver = Caregiver::make($request->except('photo'));

        if ($request->hasFile('photo')) {
            $imageUrl = Cloudinary::upload($request->file('photo')->getRealPath())->getSecurePath();
            $caregiver->photo = $imageUrl;
        }

        $caregiver->password = \Hash::make($request->password);
        // $caregiver->save();

        // Notify the caregiver to verify their email
        $caregiver->notify(new EmailVerificationNotification());

        return response()->json([
            "message" => "Caregiver registered successfully. Please verify your email.",
            "data" => new CaregiverResource($caregiver)
        ], 201);
    }

    public function login(Request $request) {
        
        // Login Caregiver - Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string'
        ]);


        // Check if the caregiver exists
        $caregiver = Caregiver::where('email', $request->email)->first();

        // Check if the caregiver exists and the password is correct
        if (!$caregiver || !\Hash::check($request->password, $caregiver->password)) {
            return response()->json([
                "errors" => [
                    'message' => 'The provided credentials are incorrect.'
                ]
            ], 401);
        }

        // Check if the caregiver has verified their email
        if ($caregiver->email_verified_at === null) {
            return response()->json([
                "errors" => [
                    'message' => 'Please verify your email.'
                ]
            ], 401);
        }

        // Create token
        $token = $caregiver->createToken($request->device_name, ['*'])->plainTextToken;

        return response()->json([
            "token" => $token,
            "user" => new CaregiverResource($caregiver)
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            "message" => "Logged out"
        ]);
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
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:caregivers,email,' . $request->user()->id,
            'password' => 'sometimes|required|string|big_password|min:8',
            "date_of_birth" => "sometimes|required|date",
            'phone' => 'sometimes|required|string|regex:/^01[0-2]{1}[0-9]{8}$/',
            "photo" => "sometimes|required|file"
        ]);

        $caregiver = $request->user();

        $caregiver->fill($request->except('photo'));

        if ($request->hasFile('photo')) {
            // First delete the old photo from cloudinary
            Cloudinary::destroy($caregiver->photo);
            $imageUrl = Cloudinary::upload($request->file('photo')->getRealPath())->getSecurePath();
            $caregiver->photo = $imageUrl;
        }

        $caregiver->save();

        return response()->json([
            "data" => new CaregiverResource($caregiver)
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
        if(!$request->user()->patients()->find($id)) {
            return response()->json([
                "errors" => [
                    "message" => "Caregiver already unfollowing the patient"
                ]
            ], 400);
        }
            
        $request->user()->patients()->detach($patient);

        return response()->json([
            "message" => "Patient unfollowed successfully"
        ]);
    }
}
