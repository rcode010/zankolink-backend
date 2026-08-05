<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/moodle/login', [AuthController::class, 'moodleLogin'])->middleware('throttle:login');
Route::post('/auth/verify', [AuthController::class, 'verify'])->middleware('throttle:verify');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:resetPassword');
Route::post('/auth/forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:forgetPassword');

Route::post('/auth/zankoline/login', [AuthController::class, 'zankolineLogin']);
Route::post('/auth/zankoline/register', [AuthController::class, 'zankolineRegister']);
// Protected Routes
Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('permission:create users', 'throttle:register');

    Route::post('/auth/two-factor/prepare', [AuthController::class, 'prepareTwoFactor'])->middleware('throttle:api');
    Route::post('/auth/two-factor/enable', [AuthController::class, 'enableTwoFactor'])->middleware('throttle:api');
    Route::post('/auth/two-factor/disable', [AuthController::class, 'disableTwoFactor'])->middleware('throttle:api');
});

Route::middleware(['auth:sanctum', 'ability:admin,moodle'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('throttle:api');
    Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:changePassword');
    Route::get('/auth/me', [AuthController::class, 'profile'])->middleware('throttle:api');

});
Route::middleware(['auth:sanctum', 'ability:zankoline'])->group(function () {

    Route::get('/auth/zankoline/me',[AuthController::class, 'zankolineMe'])->middleware('throttle:api');
});
