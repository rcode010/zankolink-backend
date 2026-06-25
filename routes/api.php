<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SignatureController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentCourseController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherDepartmentController;
use App\Http\Controllers\Api\UniversityController;
use App\Http\Controllers\Api\UserController;
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
    Route::post('universities/{university}/faculties', [FacultyController::class, 'store']);

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

    // Students
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{student}', [StudentController::class, 'show']);
        Route::patch('/{student}', [StudentController::class, 'update']);
        Route::delete('/{student}', [StudentController::class, 'destroy']);
    });

    // Courses
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::post('/', [CourseController::class, 'store']);
        Route::get('/{course}', [CourseController::class, 'show']);
        Route::patch('/{course}', [CourseController::class, 'update']);
        Route::delete('/{course}', [CourseController::class, 'destroy']);
    });

    // teacher_department
    Route::prefix('departments/{department}')->group(function () {
        Route::post('/assign-teacher', [TeacherDepartmentController::class, 'store']);
        Route::get('/teachers', [TeacherDepartmentController::class, 'index']);
        Route::delete('/teachers/{teacher}', [TeacherDepartmentController::class, 'destroy']);
    });

    // course_student
    Route::get('/departments/{department}/students', [StudentCourseController::class, 'departmentStudents']);
    Route::prefix('/courses/{course}')->group(function () {
        Route::get('/students', [StudentCourseController::class, 'courseStudents']);
        Route::post('/assign-student', [StudentCourseController::class, 'store']);
        Route::put('/students/{student}', [StudentCourseController::class, 'update']);
        Route::delete('/students/{student}', [StudentCourseController::class, 'destroy']);
    });

    // Letters
    Route::post('/letters', [LetterController::class, 'store']);
    Route::get('/letters', [LetterController::class, 'index']);
    Route::get('/letters/recents', [LetterController::class, 'recentLetters']);
    Route::get('/letters/{letter}', [LetterController::class, 'show'])->whereNumber('letter');
    Route::patch('/letters/{letter}', [LetterController::class, 'update'])->whereNumber('letter');
    Route::post('/letters/{letter}/raise', [LetterController::class, 'raiseLetter'])->whereNumber('letter');

    // Signatures
    Route::post('/signatures', [SignatureController::class, 'store']);
    Route::get('/signatures/{id}', [SignatureController::class, 'show']);
    Route::get('/signatures', [SignatureController::class, 'index']);

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::post('/{user}/activate', [UserController::class, 'activate']);
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
    });

    // The endpoint for the reports dashboard statistics
    Route::get('/reports/statistics', [ReportController::class, 'getStatistics']);
});

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});
