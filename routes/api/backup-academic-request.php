<?php

use App\Http\Controllers\AcademicRequestController;

Route::get('/backup/academic-requests',[AcademicRequestController::class, 'getAllAcademicRequests']);
