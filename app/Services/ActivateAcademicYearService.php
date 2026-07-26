<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class ActivateAcademicYearService
{
    /**
     * Make the given academic year the sole active one.
     *
     * This is the only supported way to activate an academic year: it
     * deactivates every other year and activates this one inside one
     * transaction. Code that sets is_active = true directly on the model
     * and calls save() (or update()) will not deactivate the other rows.
     */
    public function execute(AcademicYear $academicYear): AcademicYear
    {
        return DB::transaction(function () use ($academicYear) {
            AcademicYear::where('is_active', true)
                ->when(
                    $academicYear->exists,
                    fn ($query) => $query->whereKeyNot($academicYear->getKey())
                )
                ->update(['is_active' => false]);

            $academicYear->is_active = true;
            $academicYear->save();

            return $academicYear->fresh();
        });
    }
}