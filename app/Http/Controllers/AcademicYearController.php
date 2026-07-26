<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\ActivateAcademicYearService;
use App\Traits\ApiResponses;
/**
 * @group AcademicYear
 *
 * APIs for academicYear update.
 */
class AcademicYearController extends Controller
{
    use ApiResponses;
    /**
     * Update the current academic year and create a new one.
     * Route: POST /api/academic-year/update
     */
    public function updateAcademicYear(StoreAcademicYearRequest $request, ActivateAcademicYearService $activator)
    {
        $validated = $request->validated();

        $academicYear = isset($validated['id'])
            ? AcademicYear::findOrFail($validated['id'])
            : new AcademicYear();

        $academicYear->fill(collect($validated)->only(['year', 'start_date', 'end_date'])->all());

        if ($validated['is_active']) {
            $activator->execute($academicYear);
        } else {
            $academicYear->is_active = false;
            $academicYear->save();
        }

        return $this->ok(
            isset($validated['id'])
                ? 'Academic year updated successfully.'
                : 'Academic year created successfully.',
            $academicYear->fresh()->toArray()
        );
    }

    public function retrieveActiveAcademicYear()
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            return $this->error('No active academic year is set.', 404);
        }

        return $this->ok(
            'Active Academic Year retrieved successfully.',
            $academicYear->toArray(),
        );
    }
}