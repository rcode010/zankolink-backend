<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SuperiorRoleService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource with filters and pagination.
     */
    /**
     * Display a listing of the resource with filters and pagination.
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::class)
//            ->allowedFilters('role_scope_type')
            ->latest()
            ->paginate($request->query('per_page', 10));

        return $this->ok(
            'Users retrieved successfully.',
            UserResource::collection($users->load(['userScopes.role:id,name', 'roles:id,name']))->resolve()
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->ok(
            'User retrieved successfully.',
            (new UserResource($user->load(['userScopes.role:id,name', 'roles:id,name'])))->resolve()
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

        $user->update($validated);

        return $this->ok(
            'Profile updated successfully',
            (new UserResource($user->fresh()))->resolve()

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
            (new UserResource($user->fresh()))->resolve()
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
            (new UserResource($user->fresh()))->resolve()
        );
    }

    public function superiorRole(Request $request, SuperiorRoleService $superiorRoleService)
    {
        $user = $request->user();
        $higherRoleUsers = DB::transaction(function () use ($user, $superiorRoleService) {

            $higherRoleUsers = $superiorRoleService->execute($user);

            return $higherRoleUsers;
        });

        return $this->ok('Higher Role Users retrieved successfully', $higherRoleUsers->toArray());

    }
}
