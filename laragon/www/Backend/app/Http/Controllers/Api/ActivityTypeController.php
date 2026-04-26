<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\Request;

class ActivityTypeController extends Controller
{
    /**
     * Get all activity types as a map (type_key => display_name).
     */
    public function index()
    {
        $types = ActivityType::getTypeMap();
        return response()->json($types);
    }

    /**
     * Get a single activity type by key.
     */
    public function show(string $typeKey)
    {
        $type = ActivityType::where('type_key', $typeKey)->first();

        if (!$type) {
            return response()->json(['display_name' => $typeKey]);
        }

        return response()->json([
            'type_key' => $type->type_key,
            'display_name' => $type->display_name,
        ]);
    }

    /**
     * Store a new activity type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_key' => 'required|string|max:50|unique:activity_types,type_key',
            'display_name' => 'required|string|max:100',
        ]);

        $type = ActivityType::create($validated);

        return response()->json($type, 201);
    }

    /**
     * Update an existing activity type.
     */
    public function update(Request $request, string $typeKey)
    {
        $type = ActivityType::where('type_key', $typeKey)->first();

        if (!$type) {
            return response()->json(['message' => 'Activity type not found'], 404);
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    /**
     * Delete an activity type.
     */
    public function destroy(string $typeKey)
    {
        $type = ActivityType::where('type_key', $typeKey)->first();

        if (!$type) {
            return response()->json(['message' => 'Activity type not found'], 404);
        }

        $type->delete();

        return response()->json(['message' => 'Activity type deleted successfully']);
    }
}
