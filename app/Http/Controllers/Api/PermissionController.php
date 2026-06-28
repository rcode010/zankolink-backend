<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $permissions = Permission::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $this->ok('Permissions retrieved successfully.', $permissions->toArray());

    }
}
