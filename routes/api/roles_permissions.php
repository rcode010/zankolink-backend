<?php

// Roles
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;

Route::get('/roles',[RoleController::class, 'index']);


// Permissions
Route::get('/permissions',[PermissionController::class, 'index']);
