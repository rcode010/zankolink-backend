<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\DepartmentAcceptedGenderController;
use App\Http\Requests\DepartmentAcceptedGenderRequest;

Route::prefix('departments')->group(function () {

    Route::get('/', [DepartmentController::class, 'index'])->middleware('permission:view departments');
    Route::post('/', [DepartmentController::class, 'store'])->middleware('permission:create departments');
    Route::patch('/genders', [DepartmentAcceptedGenderController::class, 'update']);
    Route::get('/student-selected-courses', [DepartmentController::class, 'getStudentSelectedCourses'])->middleware('permission:view student course selections');
    Route::get('/{department}', [DepartmentController::class, 'show'])->middleware('permission:view department');
    Route::patch('/{department}', [DepartmentController::class, 'update'])->middleware('permission:update departments');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:delete departments');
    Route::patch('/{department}/seat', [DepartmentController::class, 'updateSeat'])->middleware('permission:update department seats');

    Route::post('/selection', [DepartmentController::class, 'StudentSelection'])->middleware('permission:store student course selections');
    Route::patch('/{department}/course-selection-settings', [DepartmentController::class, 'updateCourseSelectionSettings'])->middleware('permission:update course selection settings');
    Route::patch('/{department}/course-selection-settings/close', [DepartmentController::class, 'closeCourseSelection'])->middleware('permission:close course selections');
});
