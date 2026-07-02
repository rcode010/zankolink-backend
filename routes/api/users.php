<?php

use App\Http\Controllers\Api\UserController;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('permission:view users');
    Route::get('/superior-roles', [UserController::class, 'superiorRole']);
    Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:view user');
    Route::patch('/{user}', [UserController::class, 'update'])->middleware('permission:update users');
    Route::post('/{user}/activate', [UserController::class, 'activate'])->middleware('permission:activate users');
    Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:deactivate users');
});
