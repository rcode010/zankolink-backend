<?php

use App\Http\Controllers\Api\AttachmentController;

Route::post('/letters/{letter}/attachments', [AttachmentController::class, 'store']);
Route::get('/letters/{letter}/attachments/{attachment}/download', [AttachmentController::class, 'download']);
Route::delete('/letters/{letter}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
