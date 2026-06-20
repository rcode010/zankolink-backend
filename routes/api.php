<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Routes
Route::post('/auth/login',[AuthController::class, 'login']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout',[AuthController::class, 'logout']);
});
