<?php

use App\Http\Controllers\Api\AttachmentController;

Route::get('/letters/{letter}/attachments/{attachment}/download', [AttachmentController::class, 'download'])->middleware('permission:download attachments');
Route::delete('/letters/{letter}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->middleware('permission:delete attachments');
