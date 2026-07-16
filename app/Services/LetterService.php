<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterSignature;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LetterService
{
    public function create(array $data, QrCodeService $qrCodeService, User $user, LetterVerificationHashService $letterVerificationHashService, array $files)
    {
        return DB::transaction(function () use ($data, $qrCodeService, $user, $letterVerificationHashService, $files) {

            $letter = Letter::create($data)->fresh();

            $hashData = $letterVerificationHashService->generate($letter);

            $qrCodePath = $qrCodeService->generate($letter, 'public', 'qr-codes', 400);
            $letter->update([
                'verification_hash' => $hashData,
                'qr_code_path' => $qrCodePath,
            ]);
            LetterSignature::create([
                'letter_id' => $letter->id,
                'user_id' => $user->id,
            ]);

            foreach ($files as $file) {
                $path = $file->store('section-submission', 'public');

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
