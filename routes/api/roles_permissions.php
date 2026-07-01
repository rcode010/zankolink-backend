<?php

use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserRoleController;

// Roles
Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:view roles');
Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create roles');
Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:update roles');
Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete roles');

// Permissions
Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:view permissions');

// User Role
Route::get('users/{user}/roles', [UserRoleController::class, 'index'])->middleware('permission:view user roles');
Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->middleware('permission:create user roles');
Route::delete('users/{user}/roles/{userScope}', [UserRoleController::class, 'destroy'])->middleware('permission:delete user roles');
Route::delete('users/{user}/roles/by-role/{role}', [UserRoleController::class, 'destroyByRole'])->middleware('permission:delete user roles');
