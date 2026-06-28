<?php

use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserRoleController;

// Roles
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::put('/roles/{role}', [RoleController::class, 'update']);
Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

// Permissions
Route::get('/permissions', [PermissionController::class, 'index']);

// User Role
Route::get('users/{user}/roles', [UserRoleController::class, 'index']);
Route::post('users/{user}/roles', [UserRoleController::class, 'store']);
