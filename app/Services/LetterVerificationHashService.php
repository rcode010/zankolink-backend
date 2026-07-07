<?php

namespace App\Services;

use App\Models\Letter;

class LetterVerificationHashService
{
    public function generate(Letter $letter): string
    {
        return hash_hmac(
            'sha256',
            json_encode(
                $this->payload($letter),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
            config('app.key')
        );
    }

    public function verify(Letter $letter): bool
    {
        if (! $letter->verification_hash) {
            return false;
        }

        return hash_equals(
            $letter->verification_hash,
            $this->generate($letter)
        );
    }

    private function payload(Letter $letter): array
    {
        return [
            'letter_number' => $letter->letter_number,
            'type' => $letter->type,
            'title' => $letter->title,
            'body' => $letter->body,
            'original_sender_id' => (int) $letter->original_sender_id,
            'academic_year_id' => (int) $letter->academic_year_id,
            'payload' => $letter->payload ?? null,
        ];
    }
}
