<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UniversityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/forget-password', [AuthController::class, 'forgetPassword']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Universities
    Route::prefix('universities')->group(function () {
        Route::get('/', [UniversityController::class, 'index']);
        Route::get('/{university}', [UniversityController::class, 'show']);
        Route::post('/', [UniversityController::class, 'store']);
        Route::patch('/{university}', [UniversityController::class, 'update']);
        Route::delete('/{university}', [UniversityController::class, 'destroy']);
    });

    // Faculties
    Route::prefix('faculties')->group(function () {
        Route::get('/', [FacultyController::class, 'index']);
        Route::get('/{faculty}', [FacultyController::class, 'show']);
        Route::patch('/{faculty}', [FacultyController::class, 'update']);
        Route::delete('/{faculty}', [FacultyController::class, 'destroy']);
    });
    Route::post(
        'universities/{university}/faculties',
        [FacultyController::class, 'store']
    );

    // Departments
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::get('/{department}', [DepartmentController::class, 'show']);
        Route::patch('/{department}', [DepartmentController::class, 'update']);
        Route::delete('/{department}', [DepartmentController::class, 'destroy']);
    });

    // Teachers
    Route::prefix('teachers')->group(function () {
        Route::get('/', [TeacherController::class, 'index']);
        Route::post('/', [TeacherController::class, 'store']);
        Route::get('/{teacher}', [TeacherController::class, 'show']);
        Route::patch('/{teacher}', [TeacherController::class, 'update']);
        Route::delete('/{teacher}', [TeacherController::class, 'destroy']);
    });

    // Letters
    Route::post('/letters', [LetterController::class, 'store']);
    Route::get('/letters', [LetterController::class, 'index']);
    Route::get('/letters/{letter}', [LetterController::class, 'show'])->whereNumber('letter');

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::post('/{user}/activate', [UserController::class, 'activate']);
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
    });

});

Route::get('/',function (){
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});
