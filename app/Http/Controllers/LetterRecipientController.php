<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRecipientRequest;
use App\Http\Resources\LetterRecipientsResource;
use App\Models\AcademicYear;
use App\Models\Letter;
use App\Models\LetterRecipient;
use App\Services\LetterService;
use App\Services\LetterVerificationHashService;
use App\Services\QrCodeService;
use App\Services\LetterPayloadEnrichmentService;
use App\Traits\ApiResponses;
use Illuminate\Support\Str;

class LetterRecipientController extends Controller
{
    use ApiResponses;

    public function store(
        StoreLetterRecipientRequest $request,
        QrCodeService $qrCodeService,
        LetterVerificationHashService $hashService,
        LetterService $letterService,
        LetterPayloadEnrichmentService $enrichmentService
    ) {
        $this->authorize('createLetterRecipient', Letter::class);
        $user = $request->user();

        $data = $request->validated();

        $data['original_sender_id'] = $user->id;
        $data['sender_id'] = $user->id;
        $data['type'] = 'general';
        $data['letter_uuid'] = (string) Str::uuid();
        $data['academic_year_id'] = AcademicYear::where('is_active', true)->value('id');

        $data['receiver_id'] = null;

        $letter = $letterService->create(
            $data,
            $enrichmentService,
            $qrCodeService,
            $user,
            $hashService,
            $request->file('files', []),
            $data['recipient_ids']
        );

        if ($letter) {
            return $this->ok(
                'Letter created successfully',
                (new LetterRecipientsResource(
                    $letter->fresh()->load([
                        'attachments',
                        'recipients',
                    ])
                ))->resolve()
            );
        }

        return $this->error('Letter could not be created', 400);

    }
}
