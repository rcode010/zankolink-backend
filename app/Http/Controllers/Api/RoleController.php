<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
/**
 * @group Role
 *
 * APIs for role CRUD.
 */
class RoleController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $roles = Role::with('permissions:id,name')
            ->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });

        return $this->ok('Roles retrieved successfully.', $roles->toArray());
    }

    public function store(StoreRoleRequest $request)
    {
        $credentials = $request->validated();

        $role = Role::firstOrCreate(['name' => $credentials['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($credentials['permissions']);
        $role->load('permissions:id,name');

        return $this->created('Role created successfully.', [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions
                ->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])
                ->values(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $credentials = $request->validated();

        if (array_key_exists('name', $credentials)) {
            $role->update([
                'name' => $credentials['name'],
            ]);
        }
        if (array_key_exists('permissions', $credentials)) {
            $role->syncPermissions($credentials['permissions']);
        }
        $role->load('permissions:id,name');

        return $this->ok('Role updated successfully.', [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions
                ->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])
                ->values(),
        ]);

    }

    public function destroy(Role $role)
    {
        $protectedRoles = [
            'MINISTRY_ADMIN',
            'MINISTRY_STAFF',
            'UNIVERSITY_ADMIN',
            'UNIVERSITY_STAFF',
            'DEAN',
            'DEPARTMENT_HEAD',
            'lecturer',
            'student',
        ];
        if (in_array($role->name, $protectedRoles, true)) {
            return $this->error('System roles cannot be deleted.', 403);
        }

        if ($role->users()->exists()) {
            return $this->error('Cannot delete role because it is assigned to users.', 409);
        }
        $role->delete();

        return $this->ok('Role deleted successfully.');
    }
}
