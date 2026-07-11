<?php

use App\Http\Controllers\Api\SectionItemController;
use Illuminate\Support\Facades\Route;


Route::prefix('moodle')->group(function () {

    // Material operations bounded tightly to a specific parent course section
    Route::prefix('course-sections/{section}')->group(function () {
        Route::get('/items', [SectionItemController::class, 'index']);
        Route::post('/items', [SectionItemController::class, 'store']);
    });

    // Standalone entity operations on discrete section items/materials
    Route::prefix('section-items')->group(function () {
        Route::get('/{item}', [SectionItemController::class, 'show']);
        Route::get('/{item}/download', [SectionItemController::class, 'download']);
        Route::match(['put', 'patch'], '/{item}', [SectionItemController::class, 'update']);
        Route::delete('/{item}', [SectionItemController::class, 'destroy']);
    });

});