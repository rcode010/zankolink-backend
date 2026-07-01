<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getStatistics(Request $request): JsonResponse
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        $defaultAcademicYear = $currentMonth >= 9
            ? $currentYear . '-' . ($currentYear + 1)
            : ($currentYear - 1) . '-' . $currentYear;

        // Example: ?academic_year=2025-2026
        $academicYear = $request->query('academic_year', $defaultAcademicYear);

        $academicYearId = DB::table('academic_years')
            ->where('year', $academicYear)
            ->value('id');

        if (! $academicYearId) {
            return response()->json([
                'message' => 'Academic year not found.',
                'academic_year' => $academicYear,
            ], 404);
        }

        $letters = Letter::where('academic_year_id', $academicYearId)->get();

        $totalLetters = $letters->count();

        $approvedLetters = $letters->where('status', 'approved')->count();

        $approvalRate = $totalLetters > 0
            ? round(($approvedLetters / $totalLetters) * 100)
            : 0;

        $processedLetters = $letters->whereIn('status', ['approved', 'rejected'])
            ->filter(fn ($letter) => $letter->updated_at && $letter->created_at);

        $totalDays = $processedLetters->sum(
            fn ($letter) => $letter->created_at->diffInDays($letter->updated_at)
        );

        $avgResponseDays = $processedLetters->count() > 0
            ? $totalDays / $processedLetters->count()
            : 0;

        $avgResponse = round($avgResponseDays, 1) . ' days';

        $monthlyCounts = $letters->groupBy(
            fn ($letter) => $letter->created_at->format('M')
        )->map(fn ($group) => $group->count());

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $chartData = [];

        foreach ($months as $month) {
            $chartData[] = [
                'month' => $month,
                'count' => $monthlyCounts->get($month, 0),
            ];
        }

        return response()->json([
            'summary' => [
                'letters_this_year' => $totalLetters,
                'approval_rate' => $approvalRate . '%',
                'avg_response' => $avgResponse,
            ],
            'chart' => $chartData,
        ]);
    }
}
