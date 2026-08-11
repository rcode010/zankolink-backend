<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateZankolineDeadlineRequest;
use App\Models\AcademicYear;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;

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
    public function updateAcademicYear(StoreAcademicYearRequest $request)
    {
        $validated = $request->validated();

        $academicYear = DB::transaction(function () use ($validated) {
            $isActive = $validated['is_active'];

            if ($isActive) {
                AcademicYear::query()
                    ->where('is_active', true)
                    ->when(
                        isset($validated['id']),
                        fn ($query) => $query->whereKeyNot($validated['id'])
                    )
                    ->update([
                        'is_active' => false,
                    ]);
            }
            if (isset($validated['id'])) {
                $academicYear = AcademicYear::findOrFail(
                    $validated['id']
                );
                unset($validated['id']);

                $academicYear->update($validated);

                return $academicYear->fresh();
            }

            return AcademicYear::create([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'semester' => $validated['semester'],
                'year' => $validated['year'],
                'is_active' => $validated['is_active'],
            ]);
        });

        return $this->ok(
            isset($validated['id'])
                ? 'Academic year updated successfully.'
                : 'Academic year created successfully.',
            $academicYear->toArray()
        );
    }

    public function retrieveActiveAcademicYear()
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first();

        return $this->ok(
            'Active Academic Year retrieved successfully.',
            $academicYear->toArray(),
        );
    }

    public function updateZankolineDeadline(UpdateZankolineDeadlineRequest $request, AcademicYear $academicYear)
    {
        $credentials = $request->validated();
        $academicYear->updateOrFail([
            'zankoline_submission_starts_at' => $credentials['zankoline_submission_starts_at'],
            'zankoline_submission_ends_at' => $credentials['zankoline_submission_ends_at'],
        ]);

        return $this->ok('Academic year zankoline submission deadline updated successfully.', $academicYear->toArray());
    }
}
