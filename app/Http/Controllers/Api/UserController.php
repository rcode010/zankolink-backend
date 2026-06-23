<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource with filters and pagination.
     */
    public function index(Request $request)
    {

        $users = QueryBuilder::for(User::class)
            ->allowedFilters(['role_scope_type'])
            ->latest()
            ->paginate($request->query('per_page', 10));

        return $this->ok(
            'Users retrieved successfully.',
            $users
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->ok(
            'User retrieved successfully.',
            $user
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        // Directly update user details (Profile photo logic completely removed)
        $user->update($validated);

        return $this->ok(
            'Profile updated successfully',
            $user->fresh()
        );
    }

    /**
     * Activate a user account.
     */
    public function activate(User $user)
    {
        $user->update(['is_active' => true]);

        return $this->ok(
            'User activated successfully',
            $user->fresh()
        );
    }

    /**
     * Deactivate a user account.
     */
    public function deactivate(User $user)
    {
        $user->update(['is_active' => false]);

        return $this->ok(
            'User deactivated successfully',
            $user->fresh()
        );
    }
}
