<?php

use App\Http\Controllers\Api\StudentCourseController;

Route::get('/departments/{department}/students', [StudentCourseController::class, 'departmentStudents']);
Route::prefix('courses/{course}')->group(function () {
    Route::get('/students', [StudentCourseController::class, 'courseStudents']);
    Route::post('/assign-student', [StudentCourseController::class, 'store']);
    Route::put('/students/{student}', [StudentCourseController::class, 'update']);
    Route::delete('/students/{student}', [StudentCourseController::class, 'destroy']);
});
