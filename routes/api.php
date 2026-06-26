<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseTeacherController;
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


Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require __DIR__ . '/api/auth.php';

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    require __DIR__ . '/api/universities.php';
    require __DIR__ . '/api/faculties.php';
    require __DIR__ . '/api/departments.php';
    require __DIR__ . '/api/teachers.php';
    require __DIR__ . '/api/students.php';
    require __DIR__ . '/api/courses.php';

    require __DIR__ . '/api/teacher-departments.php';
    require __DIR__ . '/api/course-teachers.php';
    require __DIR__ . '/api/course-students.php';

    require __DIR__ . '/api/letters.php';
    require __DIR__ . '/api/attachments.php';
    require __DIR__ . '/api/signatures.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/reports.php';
});
