<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getStatistics(Request $request)
    {
        // Get the academic year from query parameters, default to '2025-2026'
        $academicYear = $request->query('academic_year', '2025-2026');

        // 1. Letters This Year: Count all letters for the selected academic year
        $totalLetters = Letter::where('academic_year', $academicYear)->count();

        // 2. Approval Rate: Calculate percentage of approved letters ('approved' matches your enum)
        $approvedLetters = Letter::where('academic_year', $academicYear)
            ->where('status', 'approved')
            ->count();

        $approvalRate = $totalLetters > 0 ? round(($approvedLetters / $totalLetters) * 100) : 0;

        // 3. Avg Response Time: Dynamically calculated using TIMESTAMPDIFF between created_at and updated_at
        // Only calculates for letters that are no longer pending (either approved or rejected)
        $avgResponseDays = Letter::where('academic_year', $academicYear)
            ->whereIn('status', ['approved', 'rejected'])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days'))
            ->value('avg_days');

        // Format the output dynamically (round to 1 decimal place, fallback to 0 if no letters are processed yet)
        $avgResponse = $avgResponseDays !== null ? round($avgResponseDays, 1).' days' : '0 days';

        // 4. Letters Per Month: Fetch grouped count of letters per month
        // Fixed: Grouping by DB::raw to prevent SQL ONLY_FULL_GROUP_BY strict mode errors
        $monthlyData = Letter::where('academic_year', $academicYear)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('count', 'month')
            ->all();

        // Structure monthly data into an explicit array layout for front-end charts (Jan - Dec)
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        foreach (range(1, 12) as $monthNumber) {
            $chartData[] = [
                'month' => $months[$monthNumber - 1],
                'count' => $monthlyData[$monthNumber] ?? 0,
            ];
        }

        // Return structured JSON response matching the UI dashboard cards perfectly
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
