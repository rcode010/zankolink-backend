<?php


use App\Http\Controllers\Api\CourseTeacherController;

Route::get('/departments/{department}/teachers/available', [CourseTeacherController::class, 'departmentTeachers']);

Route::prefix('courses/{course}')->group(function () {
    Route::post('/assign-teacher', [CourseTeacherController::class, 'store']);
    Route::get('/teachers', [CourseTeacherController::class, 'courseTeachers']);
    Route::put('/teachers/{teacher}', [CourseTeacherController::class, 'update']);
    Route::delete('/teachers/{teacher}', [CourseTeacherController::class, 'destroy']);
});
