<?php

use App\Http\Controllers\AcademicYearController;
use Illuminate\Support\Facades\Route;

// Route for updating the academic year (Ministry Admin only)
Route::post('/academic-year/update', [AcademicYearController::class, 'updateAcademicYear'])->middleware('permission:update academic year');
Route::patch('/academic-year/{academicYear}/zankoline-deadline', [AcademicYearController::class, 'updateZankolineDeadline'])->middleware('permission:update academic year');
Route::get('/academic-year', [AcademicYearController::class, 'show']);
