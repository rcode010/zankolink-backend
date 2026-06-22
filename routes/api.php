<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\UniversityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Routes
Route::post('/auth/login',[AuthController::class, 'login']);
Route::post('/auth/reset-password',[AuthController::class, 'resetPassword']);
Route::post('/auth/forget-password',[AuthController::class, 'forgetPassword']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/register',[AuthController::class, 'register']);
    Route::post('/auth/logout',[AuthController::class, 'logout']);
    Route::post('/auth/change-password',[AuthController::class, 'changePassword']);
    Route::post('/auth/forget-password',[AuthController::class, 'forgetPassword']);
    Route::post('/auth/reset-password',[AuthController::class, 'resetPassword']);

    //Universities
    Route::prefix('universities')->group(function () {
        Route::get('/', [UniversityController::class,'index']);
        Route::get('/{university}', [UniversityController::class,'show']);
        Route::post('/', [UniversityController::class,'store']);
        Route::patch('/{university}', [UniversityController::class,'update']);
        Route::delete('/{university}', [UniversityController::class,'destroy']);
    });

    // Departments
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::get('{id}', [DepartmentController::class, 'show']);
        Route::patch('{id}', [DepartmentController::class, 'update']);
        Route::delete('{id}', [DepartmentController::class, 'destroy']);
    });
    Route::get('faculties/{faculty_id}/departments', [DepartmentController::class, 'indexByFaculty']);

    // Letters
    Route::post('/letters',[LetterController::class, 'store']);
    Route::get('/letters',[LetterController::class, 'index']);
    Route::get('/letters/{letter}',[LetterController::class, 'show'])->whereNumber('letter');

});


