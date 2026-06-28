<?php

use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;

// Roles
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::put('/roles/{role}', [RoleController::class, 'update']);

// Permissions
Route::get('/permissions', [PermissionController::class, 'index']);
