<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterVerificationRequest;
use App\Models\Letter;
use App\Services\LetterVerificationHashService;
use App\Traits\ApiResponses;

/**
 * @group Letter Verification
 *
 * APIs for letter-verification retrival.
 */
class LetterVerificationController extends Controller
{
    use ApiResponses;

    public function getLetterVerification(LetterVerificationRequest $request, LetterVerificationHashService $letterVerificationHashService)
    {
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
                            'comment',
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

        return $this->ok('Letter verification retrieved successfully', [
            'letter_number' => $letter->letter_number,
            'title' => $letter->title,
            'status' => $letter->status,
            'content_verified' => $letterVerificationHashService->verify($letter),
            'created_at' => $letter->created_at,
            'flows' => $letter->flows,
            'receiver' => $letter->receiver,
            'sender' => $letter->sender,
            'signatures' => $letter->signatures,
            'stamps' => $letter->stamps,
            'letter_uuid' => $letter->letter_uuid,
        ]);
    }
}
