<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponses;
    public function index(Request $request){
        $roles = Role::with('permissions:id,name')
            ->get()->map(function($role){
                return[
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions'=> $role->permissions->pluck('name')
                ];
            });

        return $this->ok("Roles retrieved successfully.", $roles->toArray());
    }



}
