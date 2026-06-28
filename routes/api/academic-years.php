<?php

use App\Http\Controllers\AcademicYearController;
use Illuminate\Support\Facades\Route;

// Route for updating the academic year (Ministry Admin only)
Route::post('/academic-year/update', [AcademicYearController::class, 'updateAcademicYear']);
