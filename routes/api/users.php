<?php

use App\Http\Controllers\Api\UserController;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::patch('/{user}', [UserController::class, 'update']);
    Route::post('/{user}/activate', [UserController::class, 'activate']);
    Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
});
