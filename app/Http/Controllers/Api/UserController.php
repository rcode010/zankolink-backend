<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Fetch all users with optional filtering based on role (scoped by role)
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role if specified in query parameters (e.g., ?role=admin)
        if ($request->has('role')) {
            $query->where('role_scope_type', $request->role);
        }

        $users = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    /**
     * GET /api/users/{id}
     * Retrieve the profile details of a single user
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
    /**
     * PATCH /api/users/{id}
     * Update profile details and handle profile photo uploads
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile photo upload if provided
        if ($request->hasFile('profile_photo')) {
            // Delete old photo from storage if it exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        // Update name and phone fields if present in the request
        $user->update(array_filter($validated, function ($key) {
            return $key !== 'profile_photo';
        }, ARRAY_FILTER_USE_KEY));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * POST /api/users/{id}/activate
     * Activate a user account (set is_active to true)
     */
    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => $user
        ]);
    }

    /**
     * POST /api/users/{id}/deactivate
     * Deactivate a user account (set is_active to false)
     */
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully',
            'data' => $user
        ]);
    }
}
