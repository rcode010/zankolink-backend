<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignUserRoleRequest;
use App\Models\User;
use App\Models\UserScope;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * @group User-Role
 *
 * APIs for user-role CRUD.
 */
class UserRoleController extends Controller
{
    use ApiResponses;

    public function index(User $user)
    {
        $roles = $user->userScopes()
            ->with('role:id,name')
            ->get()
            ->map(function ($scope) {
                return [
                    'user_scope_id' => $scope->id,
                    'role_id' => $scope->role_id,
                    'role_name' => $scope->role->name,
                    'scope_type' => $scope->scope_type,
                    'scope_id' => $scope->scope_id,
                ];
            });

        return $this->ok('User roles retrieved successfully.', $roles->toArray());
    }

    public function store(AssignUserRoleRequest $request, User $user)
    {
        $credentials = $request->validated();

        $role = Role::where('guard_name', 'web')
            ->where('id', $credentials['role_id'])
            ->firstOrFail();

        $scopeId = $credentials['scope_id'] ?? null;

        $alreadyAssigned = UserScope::where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('scope_type', $credentials['scope_type'])
            ->where('scope_id', $scopeId)
            ->exists();

        if ($alreadyAssigned) {
            return $this->error('User already has this role in this scope.', 409);
        }

        $userScope = DB::transaction(function () use ($user, $role, $credentials, $scopeId) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            return UserScope::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => $credentials['scope_type'],
                'scope_id' => $scopeId,
            ]);
        });

        return $this->created('Role assigned successfully.', [
            'user_scope_id' => $userScope->id,
            'role_id' => $role->id,
            'role_name' => $role->name,
            'scope_type' => $userScope->scope_type,
            'scope_id' => $userScope->scope_id,
        ]);
    }

    public function destroy(User $user, UserScope $userScope)
    {

        if ($userScope->user_id != $user->id) {
            return $this->error('This role does not belong to this user', 409);
        }

        DB::transaction(function () use ($userScope, $user) {
            $role = $userScope->role;

            $userScope->delete();

            $stillHasSameRoleInAnotherScope = UserScope::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();

            if (! $stillHasSameRoleInAnotherScope) {
                $user->removeRole($role);
            }
        });

        return $this->ok('Role deleted successfully.');
    }

    public function destroyByRole(User $user, Role $role)
    {
        DB::transaction(function () use ($user, $role) {
            UserScope::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->delete();

            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        });

        return $this->ok('Role removed from user successfully.');
    }
}
