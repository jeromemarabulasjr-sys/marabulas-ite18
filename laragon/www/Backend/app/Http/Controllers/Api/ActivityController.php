<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Get recent activity logs.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 100);
        // Cap maximum at 100
        $limit = min($limit, 100);

        $activities = ActivityLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'at' => $activity->created_at->timestamp * 1000, // Convert to milliseconds for JS
                    'type' => $activity->type,
                    'madeBy' => $activity->username ? [
                        'username' => $activity->username,
                    ] : null,
                    ...$activity->details ?? [],
                ];
            });

        return response()->json($activities);
    }

    /**
     * Store a new activity log.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'details' => 'nullable|array',
        ]);

        // Get username from authenticated user or from request details
        $username = null;
        if (Auth::check()) {
            $username = Auth::user()->name;
        } elseif (isset($validated['details']['madeBy']['username'])) {
            $username = $validated['details']['madeBy']['username'];
        }

        // Get activity type ID
        $activityType = ActivityType::where('type_key', $validated['type'])->first();

        // Get user ID from username (try name, email, or email prefix)
        $userId = Auth::id();
        if (!$userId && $username) {
            $user = User::where('name', $username)->first();
            if (!$user) {
                $user = User::where('email', $username)->first();
            }
            if (!$user) {
                $user = User::where('email', 'like', $username . '@%')->first();
            }
            $userId = $user ? $user->id : null;
        }

        $activity = ActivityLog::create([
            'type' => $validated['type'],
            'activity_type_id' => $activityType ? $activityType->id : null,
            'user_id' => $userId,
            'username' => $username,
            'details' => $validated['details'] ?? [],
        ]);

        // Only trim when we exceed threshold (batch cleanup)
        // Check count every 10 inserts using random sampling
        if (rand(1, 10) === 1) {
            $this->trimOldRecords();
        }

        return response()->json($activity, 201);
    }

    /**
     * Clear all activity logs.
     */
    public function clear()
    {
        ActivityLog::truncate();
        return response()->json(['message' => 'Activity history cleared successfully']);
    }

    /**
     * Keep only the last 100 activity log records.
     * Optimized: Only runs cleanup when count exceeds threshold.
     */
    private function trimOldRecords()
    {
        $count = ActivityLog::count();
        // Only cleanup if we exceed 120 (20% buffer to avoid frequent cleanups)
        if ($count > 120) {
            // Get the ID of the 100th most recent record
            $cutoffId = ActivityLog::orderBy('created_at', 'desc')
                ->skip(100)
                ->take(1)
                ->value('id');

            if ($cutoffId) {
                // Delete all records older than the cutoff
                ActivityLog::where('id', '<', $cutoffId)->delete();
            }
        }
    }
}
