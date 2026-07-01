<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardFilterRequest;
use App\Models\AcademicYear;
use App\Models\Faculty;
use App\Models\Letter;
use App\Models\University;
use App\Traits\ApiResponses;
use App\Traits\ResolvesLetterScope;

class ReportController extends Controller
{
    use ApiResponses, ResolvesLetterScope;

    /**
     * Single reports entry point.
     * Only MINISTRY, UNIVERSITY (president role only), and FACULTY (dean)
     * have a Reports page — confirmed from the sidebar screenshots
     * (Admin-* and Head of Department have no "Reports" link).
     *
     * GET /api/reports/statistics?scope_type=MINISTRY
     * GET /api/reports/statistics?scope_type=UNIVERSITY&scope_id=1
     * GET /api/reports/statistics?scope_type=FACULTY&scope_id=3
     * Optional: &academic_year_id=3
     */
   public function getStatistics(DashboardFilterRequest $request)
    {
        $validated = $request->validated();

        $scopeType = $validated['scope_type'];
        $scopeId = $validated['scope_id'] ?? null;
        $academicYearId = $validated['academic_year_id'] ?? $this->defaultAcademicYearId();

        if (! $academicYearId) {
            return $this->error('Academic year not found.', 404);
        }

        // لۆژیکی دۆزینەوەی داتاکان بەبێ مەرجی تۆکن بۆ تاقیکردنەوەی خێرا
        return match ($scopeType) {
            'MINISTRY'   => $this->ministryReport($academicYearId),
            'UNIVERSITY' => $this->universityReport($scopeId, $academicYearId),
            'FACULTY'    => $this->deanReport($scopeId, $academicYearId),
            default      => $this->error('Reports are not available for this scope.', 400),
        };
    }
    /**
     * TASK 8 — Ministry report.
     */
    protected function ministryReport(int $academicYearId)
    {
        $ministryUserIds = $this->userIdsForMinistry();

        $stats = $this->buildReportStatistics($ministryUserIds, $academicYearId);

        return $this->ok('Ministry statistics retrieved successfully', $stats);
    }

    /**
     * TASK 9 — University President report.
     */
    protected function universityReport(int $universityId, int $academicYearId)
    {
        University::findOrFail($universityId);

        $officialUserIds = $this->userIdsUnderUniversity($universityId);

        $stats = $this->buildReportStatistics($officialUserIds, $academicYearId);

        return $this->ok('University statistics retrieved successfully', $stats);
    }

    /**
     * TASK 10 — Dean report.
     */
    protected function deanReport(int $facultyId, int $academicYearId)
    {
        Faculty::findOrFail($facultyId);

        $officialUserIds = $this->userIdsUnderFaculty($facultyId);

        $stats = $this->buildReportStatistics($officialUserIds, $academicYearId);

        return $this->ok('Faculty statistics retrieved successfully', $stats);
    }

    /**
     * Shared report calculation: letters this year, approval rate,
     * average response time, and a Jan-Dec monthly chart.
     *
     * Business rule: scoped to letters where the given user IDs are
     * EITHER sender or receiver — same rule as the dashboards.
     */
    protected function buildReportStatistics($userIds, int $academicYearId): array
    {
        $letters = Letter::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('sender_id', $userIds)->orWhereIn('receiver_id', $userIds);
            })
            ->where('academic_year_id', $academicYearId)
            ->get(['status', 'created_at', 'updated_at']);

        $totalLetters = $letters->count();

        $approvedLetters = $letters->where('status', 'approved')->count();

        $approvalRate = $totalLetters > 0
            ? round(($approvedLetters / $totalLetters) * 100)
            : 0;

        $processedLetters = $letters->whereIn('status', ['approved', 'rejected'])
            ->filter(fn ($letter) => $letter->created_at && $letter->updated_at);

        $totalDays = $processedLetters->sum(
            fn ($letter) => $letter->created_at->diffInDays($letter->updated_at)
        );

        $avgResponseDays = $processedLetters->count() > 0
            ? $totalDays / $processedLetters->count()
            : 0;

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $monthlyCounts = $letters->groupBy(
            fn ($letter) => $letter->created_at->format('M')
        )->map(fn ($group) => $group->count());

        $chartData = collect($months)->map(fn ($month) => [
            'month' => $month,
            'count' => $monthlyCounts->get($month, 0),
        ])->values();

        return [
            'summary' => [
                'letters_this_year' => $totalLetters,
                'approval_rate' => $approvalRate.'%',
                'avg_response' => round($avgResponseDays, 1).' days',
            ],
            'chart' => $chartData,
        ];
    }

    /**
     * Default to the currently active academic year when none is given.
     */
    protected function defaultAcademicYearId(): ?int
    {
        return AcademicYear::where('is_active', true)->value('id');
    }
}