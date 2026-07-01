<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterVerificationRequest;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Support\Str;

class LetterVerificationController extends Controller
{
    use ApiResponses;
    public function getLetterVerification(LetterVerificationRequest $request){
        $credentials = $request->validated();

        $letter = Letter::where('letter_uuid', $credentials['letter_uuid'])
            ->with([
                'sender:id,name',
                'receiver:id,name',

                'flows' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'letter_id',
                            'action',
                            'actor_id',
                            'from_recipient_id',
                            'to_recipient_id',
                            'note',
                            'created_at',
                        ])
                        ->oldest();
                },
                'signatures' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'letter_id',
                            'user_id',
                            'role_at_time',
                            'created_at',
                        ])
                        ->oldest();
                },

                'signatures.user:id,name',

                'stamps' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'letter_id',
                            'user_id',
                            'created_at',
                        ])
                        ->oldest();
                },

                'stamps.user:id,name',
            ])
            ->firstOrFail();

        $dataToBeHashed = [
            'letter_number' => $letter->letter_number,
            'type' => $letter->type,
            'title' => $letter->title,
            'body' => $letter->body,

            'original_sender_id' => $letter->original_sender_id,
            'academic_year_id' => $letter->academic_year_id,
            'payload' => $letter->payload ?? null,
        ];
        $hashData = hash_hmac(
            'sha256',
            json_encode($dataToBeHashed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            config('app.key')
        );
        $content_verified = hash_equals($letter->verification_hash, $hashData);

        return $this->ok("Letter verification retrieved successfully", [
            'letter_number'=>$letter->letter_number,
            'title'=>$letter->title,
            'status'=>$letter->status,
            'content_verified'=>$content_verified,
            'created_at'=>$letter->created_at,
            'flows'=>$letter->flows,
            'receiver'=>$letter->receiver,
            'sender'=>$letter->sender,
            'signatures'=>$letter->signatures,
            'stamps'=>$letter->stamps,
            'letter_uuid'=>$letter->letter_uuid,
        ]);
    }
}
