<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fall;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request) {
        $query = Fall::query();

        $filters = [
            'location' => function($query, $value) {
                $query->where('location', 'LIKE', '%' . $value . '%');
            },
            'start_date' => function($query, $value) use ($request) {
                $query->where('updated_at', '>=', $value);
            },
            'end_date' => function($query, $value) use ($request) {
                $query->where('updated_at', '<=', $value);
            },
            'severity' => function($query, $value) {
                $query->where('severity', $value);
            },
            'user_id' => function($query, $value) {
                $query->where('user_id', $value);
            },
            'device_id' => function($query, $value) {
                $query->where('device_id', $value);
            },
            'latitude' => function($query, $value) use ($request) { // don't understood
                // Ensure both latitude and longitude are provided
                if ($request->has('longitude') && $request->has('radius')) {
                    $longitude = $request->input('longitude');
                    $radius = $request->input('radius');
                    // Add a haversine formula for distance calculation here if needed
                    $query->whereRaw("ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) <= ?", [$longitude, $value, $radius * 1000]);
                }
            },
            "patient_name" => function($query, $value) {
                $query->whereHas('user', function($query) use ($value) {
                    $query->where('name', 'LIKE', '%' . $value . '%');
                });
            },
        ];

        // Apply the filters based on the request input
        foreach ($filters as $field => $applyFilter) {
            if ($request->has($field)) {
                $applyFilter($query, $request->input($field));
            }
        }

        // Return the results
        return response()->json([
            'data' => $query->get(),
        ]);
    }
}
