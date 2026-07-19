<?php


use App\Http\Controllers\LetterBroadcastController;

Route::post('/letter-broadcast',[LetterBroadcastController::class,'store'])->middleware('permission:create letter broadcast');
