<?php

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

require __DIR__.'/api/auth.php';

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    require __DIR__.'/api/universities.php';
    require __DIR__.'/api/academic-years.php';
    require __DIR__.'/api/faculties.php';
    require __DIR__.'/api/departments.php';
    require __DIR__.'/api/teachers.php';
    require __DIR__.'/api/students.php';
    require __DIR__.'/api/courses.php';

    require __DIR__.'/api/teacher-departments.php';
    require __DIR__.'/api/course-teachers.php';
    require __DIR__.'/api/course-students.php';

    require __DIR__.'/api/letters.php';
    require __DIR__.'/api/attachments.php';
    require __DIR__.'/api/signatures.php';
    require __DIR__.'/api/users.php';
    require __DIR__.'/api/reports.php';
});
