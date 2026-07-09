<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group AcademicYear
 *
 * APIs for academicYear update.
 */
class AcademicYearController extends Controller
{
    /**
     * Update the current academic year and create a new one.
     * Route: POST /api/academic-year/update
     */
    public function updateAcademicYear(Request $request)
    {
        // 1. Validate the incoming request data from frontend
        $request->validate([
            'year' => 'required|string', // e.g., "2026-2027"
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        // Use a database transaction to ensure both operations succeed or fail together
        DB::transaction(function () use ($request) {

            // 2. Set all previous academic years to inactive
            AcademicYear::where('is_active', true)->update(['is_active' => false]);

            // 3. Create the new academic year and set it as active
            AcademicYear::create([
                'year' => $request->year,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => true,
            ]);
        });

        return response()->json([
            'message' => 'Academic year updated successfully. New year is now active.',
        ], 200);
    }
}
