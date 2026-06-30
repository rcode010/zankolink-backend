<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStampRequest;
use App\Models\Letter;
use App\Models\LetterStamp;
use App\Traits\ApiResponses;

class LetterStampController extends Controller
{
    use ApiResponses;
    public function store(StoreStampRequest $request){
        $user = $request->user();

        $letter = Letter::findOrFail($request->letter_id);

        if($letter->is_processed()){
            return $this->error("Letter already processed",422);
        }


        $letterStamp = LetterStamp::create([
            'user_id' => $user->id,
            'letter_id' => $request->letter_id,
        ]);

        return $this->ok("Successfully created letter stamp",
            $letterStamp->toArray()
        );
    }
}
