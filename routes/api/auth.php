<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/moodle/login', [AuthController::class, 'moodleLogin']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:forgetPassword');
Route::post('/auth/verify', [AuthController::class, 'verify'])->middleware('throttle:verify');

// Protected Routes
Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('permission:create users');

    Route::post('/auth/two-factor/prepare', [AuthController::class, 'prepareTwoFactor']);
    Route::post('/auth/two-factor/enable', [AuthController::class, 'enableTwoFactor']);
    Route::post('/auth/two-factor/disable', [AuthController::class, 'disableTwoFactor']);
});

Route::middleware(['auth:sanctum', 'ability:admin,moodle'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/auth/me', [AuthController::class, 'profile']);

});
