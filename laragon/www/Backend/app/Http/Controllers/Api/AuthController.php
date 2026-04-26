<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function login(UserRequest $request) {
        $credentials = $request->only('email', 'password');
        $role = $request->input('role');

        $query = User::where('email', $credentials['email']);
        if ($role) {
            $query->where('role', $role);
        }

        $user = $query->first();
        if(!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = Str::random(80);
        $user->update(['api_token' => hash('sha256', $token)]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function signup(UserRequest $request) {
        // $credentials = $request->only('name', 'email', 'password', 'confirm_password', 'address');
        $credentials = $request->all();

        if ($credentials['password'] !== $credentials['confirm_password']) {
            throw ValidationException::withMessages([
                'password' => ['The password confirmation does not match.'],
            ]);
        }
        $token = Str::random(80);
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => 'customer',
            'address' => $request->input('address'),
            'phone_number' => $request->input('phone_number'),
            'api_token' => hash('sha256', $token),
        ]);

        return $user;
    }

    /**
     * Get all credentials for the authenticated user or all users (admin).
     */
    public function getCredentials(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'created_at')
            ->get()
            ->map(function ($user) {
                return [
                    'username' => $user->name,
                    'email' => $user->email,
                    'verified' => true, // All users in DB are considered verified
                    'createdAt' => $user->created_at->toISOString(),
                ];
            });

        return response()->json($users);
    }

    /**
     * Remove a credential (user account).
     */
    public function removeCredential(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $username = $user->name;

        // Don't allow deleting yourself
        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $user->delete();

        // Log activity
        ActivityLog::create([
            'type' => 'credential-removed',
            'username' => Auth::check() ? Auth::user()->name : null,
            'details' => [
                'username' => $username,
            ],
        ]);

        return response()->json(['message' => 'Credential removed successfully']);
    }

    /**
     * Send verification code for email verification.
     */
    public function sendVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Generate 6-digit code
        $code = random_int(100000, 999999);

        // In a real application, send email here
        // For now, just return the code (demo purposes)

        return response()->json([
            'message' => 'Verification code sent',
            'code' => $code, // Remove in production
        ]);
    }

    /**
     * Password reset request.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // In a real application, send password reset email here

        return response()->json([
            'message' => 'Password reset link sent to ' . $validated['email'],
        ]);
    }

    /**
     * Update user profile (username and/or password).
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'current_password' => 'required|string',
            'new_password' => 'nullable|string|min:6',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Check if new username already exists (excluding current user)
        if ($validated['username'] !== $user->name) {
            $usernameExists = User::where('name', $validated['username'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($usernameExists) {
                return response()->json([
                    'message' => 'Username already exists'
                ], 422);
            }
        }

        // Update username
        $user->name = $validated['username'];

        // Update password if provided
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        // Log activity
        ActivityLog::create([
            'type' => 'update',
            'username' => $user->name,
            'details' => [
                'name' => 'Profile updated',
            ],
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
