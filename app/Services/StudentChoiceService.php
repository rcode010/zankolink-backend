<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DepartmentOffering;
use App\Models\HighSchoolStudent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StudentChoiceService
{
    /**
     * Check the form is still accepting submissions, then that every chosen
     * offering is open this year and admits this student, by major and by
     * gender.
     *
     * @param  list<array{department_offering_id: int, preference_order: int}>  $choices
     */
    public function validateChoices(
        HighSchoolStudent $student,
        array $choices,
        AcademicYear $academicYear
    ): void {
        $this->ensureSubmissionIsOpen($academicYear);

        $offeringIds = array_column($choices, 'department_offering_id');

        $offerings = DepartmentOffering::query()
            ->whereIn('id', $offeringIds)
            ->where('academic_year_id', $academicYear->id)
            ->with('department:id,name,accepted_gender')
            ->get(['id', 'department_id', 'major_type']);

        $this->ensureOfferingsAreOpen($offeringIds, $offerings);
        $this->ensureMajorIsAccepted($student, $offerings);
        $this->ensureGenderIsAccepted($student, $offerings);
    }

    private function ensureSubmissionIsOpen(AcademicYear $academicYear): void
    {
        $opensAt = $academicYear->zankoline_submission_starts_at;
        $closesAt = $academicYear->zankoline_submission_ends_at;

        if ($opensAt === null || $closesAt === null) {
            throw ValidationException::withMessages([
                'choices' => ['The submission period has not been opened yet.'],
            ]);
        }

        if (now()->lessThan($opensAt)) {
            throw ValidationException::withMessages([
                'choices' => ['The submission period has not started yet.'],
            ]);
        }

        if (now()->greaterThan($closesAt)) {
            throw ValidationException::withMessages([
                'choices' => ['The submission period has ended.'],
            ]);
        }
    }

    /**
     * @param  list<int>  $offeringIds
     * @param  Collection<int, DepartmentOffering>  $offerings
     */
    private function ensureOfferingsAreOpen(array $offeringIds, Collection $offerings): void
    {
        $missing = array_values(array_diff($offeringIds, $offerings->pluck('id')->all()));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'choices' => ['Some of the chosen offerings are not open this year.'],
            ]);
        }
    }

    /**
     * A literary graduate never sat the scientific subjects, so they may only
     * apply to literary departments. A scientific graduate may apply to either.
     *
     * @param  Collection<int, DepartmentOffering>  $offerings
     */
    private function ensureMajorIsAccepted(HighSchoolStudent $student, Collection $offerings): void
    {
        if ($student->major_type !== 'literary') {
            return;
        }

        $rejected = $offerings
            ->where('major_type', '!=', 'literary')
            ->map(fn (DepartmentOffering $offering): string => $offering->department->name)
            ->unique()
            ->values();

        if ($rejected->isNotEmpty()) {
            throw ValidationException::withMessages([
                'choices' => ['These departments do not admit literary graduates: '.$rejected->implode(', ').'.'],
            ]);
        }
    }

    /**
     * @param  Collection<int, DepartmentOffering>  $offerings
     */
    private function ensureGenderIsAccepted(HighSchoolStudent $student, Collection $offerings): void
    {
        $rejected = $offerings
            ->filter(function (DepartmentOffering $offering) use ($student): bool {
                $accepted = $offering->department->accepted_gender;

                // Null or "both" means the department has no gender rule.
                return $accepted !== null
                    && $accepted !== 'both'
                    && $accepted !== $student->gender;
            })
            ->map(fn (DepartmentOffering $offering): string => $offering->department->name)
            ->unique()
            ->values();

        if ($rejected->isNotEmpty()) {
            throw ValidationException::withMessages([
                'choices' => ['These departments do not admit '.$student->gender.' students: '.$rejected->implode(', ').'.'],
            ]);
        }
    }
}
