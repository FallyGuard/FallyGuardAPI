<?php

namespace App\Http\Controllers\Api;

use App\Events\PushNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Fall;
use App\Notifications\FallDetectNotification;
use App\Notifications\FollowNotification;
use Illuminate\Http\Request; 

use Mastani\GoogleStaticMap\GoogleStaticMap as StaticMap;


class FallController extends Controller
{
    public function __construct(){
        $this->middleware('role:patient', ['except' => ['index', 'show', "user"]]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $falls = Fall::all();

        if ($request->query('deep') === 'true') {
            $falls->load("user");
        }

        return response()->json([
            "data" => $falls,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            "severity" => "required|string|in:danger,info,ok",
        ]);

        $map = new StaticMap();
        $map->setCenter("{$request->latitude},{$request->longitude}");
        $map->setZoom(15);
        $map->setSize(640, 480);

        // Add a marker (optional)
        $map->addMarker($request->latitude, $request->longitude, color: 'red');

        $imageUrl = $map->make();

        // Push Fall Detect Notification Event
        event(new FallDetectNotification([
            'patient' => $request->user(),
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'severity' => $request->severity,
        ]));
        
        // test (worked)
        // event(new FollowNotification("He is Following you."));
        
        return Fall::create([
            ...$request->all(),
            "user_id" => $request->user()->id,
            // "location" => $imageUrl
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $fall = Fall::find($id);

        if ($request->query('deep') === 'true') {
            $fall->load("user");
        }

        return response()->json([
            "data" => $fall,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // TODO: Implement update method
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Fall::destroy($id);

        return response()->json([
            "message" => "Fall Event deleted successfully",
        ]);
    }

    // Get User
    public function user(Request $request, string $id)
    {
        $fall = Fall::find($id);

        return response()->json([
            "data" => $fall->user,
        ]);
    }
}

// 