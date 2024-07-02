<?php

namespace App\Http\Controllers\Api;

use App\Events\PushNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Fall;
use App\Notifications\FallDetectNotification;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Mastani\GoogleStaticMap\GoogleStaticMap as StaticMap;
use App\Services\PhoneService;

class FallController extends Controller
{
    public $phoneService;
    public function __construct()
    {
        $this->phoneService = new PhoneService();
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

        // Check if the patient's fall is already registered in the database
        $fall = Fall::where('user_id', $request->user()->id)
            ->where('location', $request->location)
            ->where('latitude', $request->latitude)
            ->where('longitude', $request->longitude);

        if ($fall->exists()) {
            // notify the patient that the fall is already registered
            // avoiding duplicate fall events
            return response()->setStatusCode(204);
        }

        // check if different coordinates if so, delete old fall event
        $fall->delete();

        // Push Fall Detect Notification Event
        event(new FallDetectNotification([
            'patient' => $request->user(),
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'severity' => $request->severity,
        ]));

        // Send SMS to all the contacts
        $contacts = $request->user()->contacts;
        foreach ($contacts as $contact) {
            // $this->phoneService->sendSMS($request->user(), $contact);
            $this->phoneService->sendToWhatsapp($request->user(), $contact);
        }

        // Store Fall Notification
        DB::insert('insert into notification (user_id, type, title, content, created_at, updated_at) values (?, ?, ?, ?, ?, ?)', [
            $request->user()->id,
            "fall",
            'Fall Detected',
            "{$request->user()->name} has fall down!",
            now(),
            now(),
        ]);

        // test (worked)
        // event(new FollowNotification("He is Following you."));


        return Fall::create([
            ...$request->all(),
            "user_id" => $request->user()->id,
            // "location" => $imageUrl
            "location" => "https://res.cloudinary.com/dpr9selqa/image/upload/v1719844313/w12hgojqlwfz2o6l58fw.png"
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