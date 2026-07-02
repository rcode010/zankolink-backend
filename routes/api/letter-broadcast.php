<?php


use App\Http\Controllers\LetterBroadcastController;

Route::get('/letter-broadcast',[LetterBroadcastController::class,'index'])->middleware('permission:view letter broadcast');
Route::get('/letter-broadcast/{letterBroadcast}',[LetterBroadcastController::class,'show'])->middleware('permission:view letter broadcast');
Route::post('/letter-broadcast',[LetterBroadcastController::class,'store'])->middleware('permission:create letter broadcast');
