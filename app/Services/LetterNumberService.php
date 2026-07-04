<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\LetterSequence;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    public function generate(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

            $userScope = UserScope::where('user_id', $user->id)->firstOrFail();
            $sequence = LetterSequence::where('academic_year_id', $activeYear->id)
                ->where('scope_type', $userScope->scope_type)
                ->when(
                    is_null($userScope->scope_id),
                    fn ($q) => $q->whereNull('scope_id'),
                    fn ($q) => $q->where('scope_id', $userScope->scope_id)
                )
                ->lockForUpdate()
                ->firstOrFail();

            $nextSequence = $sequence->last_sequence + 1;
            $sequence->update(['last_sequence' => $nextSequence]);

            return sprintf('%s/%s/%04d',
                strtoupper($userScope->scope_type),
                $activeYear->year,
                (int) $nextSequence
            ); // → MINISTRY/2025-2026/0001
        });
    }
}
