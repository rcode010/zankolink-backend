<?php

use App\Http\Controllers\Api\SectionItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle')->group(function () {

    // Material operations bounded tightly to a specific parent course section
    Route::prefix('course-sections/{section}')->group(function () {
        Route::get('/items', [SectionItemController::class, 'index'])->middleware('permission:view section items');
        Route::post('/items', [SectionItemController::class, 'store'])->middleware('permission:create section items');
    });

    // Standalone entity operations on discrete section items/materials
    Route::prefix('section-items')->group(function () {
        Route::get('/{item}', [SectionItemController::class, 'show'])->middleware('permission:view section item');
        Route::get('/{item}/download', [SectionItemController::class, 'download'])->middleware('permission:view section attachments');
        Route::match(['put', 'patch'], '/{item}', [SectionItemController::class, 'update'])->middleware('permission:update section items');
        Route::delete('/{item}', [SectionItemController::class, 'destroy'])->middleware('permission:delete section items');
    });

});
