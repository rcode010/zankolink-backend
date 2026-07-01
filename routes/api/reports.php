<?php

use App\Http\Controllers\Api\ReportController;

Route::get('/reports/statistics', [ReportController::class, 'getStatistics'])->middleware('permission:view reports');
