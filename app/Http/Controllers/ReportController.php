<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function getStatistics(Request $request): JsonResponse
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Calculate the dynamic academic year based on the current month
        $defaultAcademicYear = $currentMonth >= 9
            ? $currentYear.'-'.($currentYear + 1)
            : ($currentYear - 1).'-'.$currentYear;

        $academicYear = $request->query('academic_year', $defaultAcademicYear);

        // Fetch all letters for the selected academic year to process in memory
        $letters = Letter::where('academic_year', $academicYear)->get();

        $totalLetters = $letters->count();

        $approvedLetters = $letters->where('status', 'APPROVED')->count();

        $approvalRate = $totalLetters > 0 ? round(($approvedLetters / $totalLetters) * 100) : 0;

        // Filter letters that have been processed and contain valid timestamps
        $processedLetters = $letters->whereIn('status', ['APPROVED', 'REJECTED'])
            ->filter(fn ($letter) => $letter->updated_at && $letter->created_at);

        // Calculate average response time using Carbon's diffInDays
        $totalDays = $processedLetters->sum(fn ($letter) => $letter->created_at->diffInDays($letter->updated_at));

        $avgResponseDays = $processedLetters->count() > 0 ? $totalDays / $processedLetters->count() : 0;

        $avgResponse = round($avgResponseDays, 1).' days';

        // Group letters by short month name (e.g., Jan, Feb) for the chart
        $monthlyCounts = $letters->groupBy(fn ($letter) => $letter->created_at->format('M'))
            ->map(fn ($group) => $group->count());

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];

        // Build structured array matching frontend chart requirements
        foreach ($months as $month) {
            $chartData[] = [
                'month' => $month,
                'count' => $monthlyCounts->get($month, 0),
            ];
        }

        return response()->json([
            'summary' => [
                'letters_this_year' => $totalLetters,
                'approval_rate' => $approvalRate.'%',
                'avg_response' => $avgResponse,
            ],
            'chart' => $chartData,
        ]);
    }
}
