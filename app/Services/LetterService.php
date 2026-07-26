<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterRecipient;
use App\Models\LetterSignature;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class LetterService
{
    public function create(
        array $data,
        LetterPayloadEnrichmentService $snapshotService,
        QrCodeService $qrCodeService,
        User $user,
        LetterVerificationHashService $letterVerificationHashService,
        array $files,
        array $recipientIds = [],
    ) {
        return DB::transaction(function () use ($data,$snapshotService, $qrCodeService, $user, $letterVerificationHashService, $files, $recipientIds) {
        if (isset($data['payload'])) {
                $data['payload'] = $snapshotService->run(
                    $data['type'],
                    $data['payload']
                );
            }
            $letter = Letter::create($data)->fresh();

            foreach ($recipientIds as $recipientId) {
                LetterRecipient::create([
                    'letter_id' => $letter->id,
                    'recipient_id' => $recipientId,
                ]);
            }

            $hashData = $letterVerificationHashService->generate($letter);

            $qrCodePath = $qrCodeService->generate($letter, 'private', 'qr-codes', 400);
            $letter->update([
                'verification_hash' => $hashData,
                'qr_code_path' => $qrCodePath,
            ]);
            LetterSignature::create([
                'letter_id' => $letter->id,
                'user_id' => $user->id,
            ]);

            foreach ($files as $file) {
                $path = $file->store('attachments/letters/'.$letter->id, 'private');

                $letter->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'file_url' => $path,
                ]);
            }

            return $letter;
        });
    }
}
