<?php

use App\Http\Controllers\Api\StudentController;

Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->middleware('permission:view students');
    Route::post('/', [StudentController::class, 'store'])->middleware('permission:create students');
    Route::get('/{student}', [StudentController::class, 'show'])->middleware('permission:view students');
    Route::patch('/{student}', [StudentController::class, 'update'])->middleware('permission:update students');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('permission:delete students');
});
Route::post('/zankoline/students/{highSchoolStudent}/enroll', [StudentController::class, 'enrollStudents']);
