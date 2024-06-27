<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;

use App\Services\AuthService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $authService;

    public function __construct(){
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

    public function login(Request $request) {
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
    public function update(Request $request, User $user)
    {
        // Update User Profile
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            "family_name" => "sometimes|required|string|max:255",
            'email' => 'sometimes|required|email|unique:users,email',
            "date_of_birth" => "sometimes|required|date",
            'phone' => 'sometimes|required|string|max:255',
            "country" => "sometimes|required|string|max:255",
            'address' => 'sometimes|required|string|max:255',
            'photo' => 'sometimes|required|file|max:255',
        ]);

        foreach($request->all() as $key => $value) {
            if ($key !== 'photo')
                $user->{$key} = $value;
        }

        if ($request->hasFile('photo')) {
            $user->photo = $request->file('photo')->store('photos');
        }

        if ($user->isDirty()) {
            $user->save();
            return response()->json(new UserResource($user), 200);
        }else {
            return response()->json([
                "errors" => [
                    "message" => "No changes detected"
                ]
            ], 422);
        }
    }

    // Get all contacts for a user
    public function contacts(Request $request, string $id) {
        $user = User::findOrFail($id);
        return response()->json([
            'user_id' => $user->id,
            "data" => $user->contacts
        ]);
    }

    // Get Single Contact
    public function contact(Request $request, string $id, string $contact_id) {
        $user = User::findOrFail($id);
        $contact = $user->contacts()->findOrFail($contact_id);
        
        return response()->json([
            'user_id' => $user->id,
            "data" => $contact
        ]);
    }

    // Get falls for a user
    public function falls(Request $request, string $id) {
        $user = User::findOrFail($id);

        return response()->json([
            "data" => $user->falls
        ]);
    }

    // Get Single Patient
    public function show(Request $request, string $id) {
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
