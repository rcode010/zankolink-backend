<?php

use App\Http\Controllers\Api\DepartmentController;

Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])->middleware('permission:view departments');
    Route::post('/', [DepartmentController::class, 'store'])->middleware('permission:create departments');
    Route::get('/{department}', [DepartmentController::class, 'show'])->middleware('permission:view department');
    Route::patch('/{department}', [DepartmentController::class, 'update'])->middleware('permission:update departments');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:delete departments');
    Route::patch('/{department}/seat', [DepartmentController::class, 'updateSeat'])->middleware('permission:update department seats');

    Route::get('/student-selected-courses', [DepartmentController::class, 'getStudentSelectedCourses']);
    Route::post('/approve-selection', [DepartmentController::class, 'approveStudentSelection']);
    Route::patch('/{department}/course-selection-settings', [DepartmentController::class,'updateCourseSelectionSettings']);
});
