<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LetterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Routes
Route::post('/auth/login',[AuthController::class, 'login']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/register',[AuthController::class, 'register']);
    Route::post('/auth/logout',[AuthController::class, 'logout']);
    Route::post('/auth/change-password',[AuthController::class, 'changePassword']);

    // Letters
    Route::post('/letters',[LetterController::class, 'store']);
    Route::get('/letters',[LetterController::class, 'index']);
});
