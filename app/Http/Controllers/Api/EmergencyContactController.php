<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function index(Request $request) {
        return response()->json([
            "data" => EmergencyContact::all()->load('user'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            "relationship" => "required",
            'phone' => 'required',
            'email' => 'required|email',
            'address' => 'required',
        ]);

        $emergency_contact = EmergencyContact::create([
            ...$request->all(),
            "user_id" => $request->user()->id,
        ]);

        return response()->json([
            "data" => $emergency_contact,
        ], 201);
    }

    // may no emergency contact, prefer used 'id' instead
    public function show(EmergencyContact $emergency_contact)
    {
        return response()->json([
            "data" => $emergency_contact,
        ], 200);
    }

    public function update(Request $request, EmergencyContact $emergency_contact)
    {
        $request->validate([
            'name' => 'sometimes|required',
            "relationship" => "sometimes|required",
            'phone' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'address' => 'sometimes|required',
        ]);

        foreach($request->all() as $key => $value) {
            $emergency_contact->{$key} = $value;
        }

        if ($emergency_contact->isDirty()) {
            $emergency_contact->save();
        }else {
            return response()->json([
                "message" => "No changes made",
            ], 422);
        }

        return response()->json([
            "data" => $emergency_contact,
        ], 200);
    }

    public function destroy(string $id)
    {
        $emergency_contact = EmergencyContact::find($id);

        if (!$emergency_contact) {
            return response()->json([
                "message" => "Emergency contact not found",
            ], 404);
        }

        return response()->json([
            "message" => "Emergency contact deleted successfully",
        ], 200);
    }

    public function getEmergencyContactsByUserId(Request $request)
    {
        $emergency_contact = EmergencyContact::where("user_id", $request->query("user_id"))->get();
    
        if (!$emergency_contact) {
            return response()->json([
                "message" => "Emergency contacts not found",
            ], 404);
        }

        return response()->json([
            "data" => $emergency_contact,
        ], 200);
    }
}