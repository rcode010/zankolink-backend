<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    use ApiResponses;
    public function index(Request $request, User $user){
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
        });;

        return $this->ok("User roles retrieved successfully.", $roles->toArray());
    }
}
