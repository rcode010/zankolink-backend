<?php

namespace App\Observers;

use App\Models\AcademicYear;
use App\Models\University;

class UniversityObserver
{
    public function creating(University $university): void
    {
        if (!$university->academic_year_id) {
            $university->academic_year_id = AcademicYear::where('is_active', true)
                ->value('id');
        }
    }

}
