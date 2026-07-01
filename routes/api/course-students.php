<?php

use App\Http\Controllers\Api\StudentCourseController;

Route::get('/departments/{department}/students', [StudentCourseController::class, 'departmentStudents'])->middleware('permission:view students');
Route::prefix('courses/{course}')->group(function () {
    Route::get('/students', [StudentCourseController::class, 'courseStudents'])->middleware('permission:view course students');
    Route::post('/assign-student', [StudentCourseController::class, 'store'])->middleware('permission:assign course students');
    Route::put('/students/{student}', [StudentCourseController::class, 'update'])->middleware('permission:update course students');
    Route::delete('/students/{student}', [StudentCourseController::class, 'destroy'])->middleware('permission:delete course students');
});
