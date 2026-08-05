<?php

use App\Http\Controllers\AcademicYearController;

Route::get('/academic-year/active', [AcademicYearController::class, 'retrieveActiveAcademicYear']);
