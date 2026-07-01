<?php

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/reports/statistics', [ReportController::class, 'getStatistics']);