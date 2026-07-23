<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardFilterRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Letter;
use App\Models\University;
use App\Models\UserScope;
use App\Traits\ApiResponses;
use App\Traits\ResolvesLetterScope;
use Illuminate\Support\Facades\DB;

/**
 * @group Dashboard
 *
 * APIs Dashboard.
 */
class DashboardController extends Controller
{
    use ApiResponses, ResolvesLetterScope;

    /**
     * Single dashboard entry point.
     *
     * GET /api/dashboard?scope_type=MINISTRY
     * GET /api/dashboard?scope_type=UNIVERSITY&scope_id=1
     * GET /api/dashboard?scope_type=FACULTY&scope_id=3
     * GET /api/dashboard?scope_type=DEPARTMENT&scope_id=7
     * Optional: &academic_year_id=3
     */
    public function index(DashboardFilterRequest $request)
    {
        $validated = $request->validated();

        $scopeType = $validated['scope_type'];
        $scopeId = $validated['scope_id'] ?? null;
        $academicYearId = $validated['academic_year_id'] ?? null;

        // Verify the authenticated user actually holds this scope.
        $userScope = $this->resolveUserScope($scopeType, $scopeId);

        if (! $userScope) {
            return $this->error('You do not have access to this scope.', 403);
        }

        $roleName = $userScope->role->name ?? null;

        return match ($scopeType) {
            'MINISTRY' => $this->ministry($academicYearId),

            // Same scope_type (UNIVERSITY) holds four different roles
            // (confirmed via DatabaseSeeder). Branch by role name.
            'UNIVERSITY' => match ($roleName) {
                'UNIVERSITY_ADMIN' => $this->universityPresident($scopeId, $academicYearId),
                'UNIVERSITY_ADMIN_ADMINISTRATION' => $this->adminAdministration($academicYearId),
                'UNIVERSITY_ADMIN_STUDENTS' => $this->adminStudents($academicYearId),
                'UNIVERSITY_ADMIN_SCIENCE' => $this->adminScience($academicYearId),
                default => $this->error('Unsupported role for this scope.', 400),
            },

            'FACULTY' => $this->dean($scopeId, $academicYearId),

            'DEPARTMENT' => $this->headOfDepartment($scopeId, $academicYearId),

            default => $this->error('Unsupported scope type.', 400),
        };
    }

    /**
     * TASK 1 — Ministry dashboard.
     */
    protected function ministry(?int $academicYearId)
    {
        $ministryUserIds = $this->userIdsForMinistry();

        $counts = $this->letterStatusCounts($ministryUserIds, $academicYearId, withApprovedThisMonth: true);

        $totalUniversities = University::count();

        $facultiesPerUniversity = University::query()
            ->withCount('faculties')
            ->orderByDesc('faculties_count')
            ->take(6)
            ->get(['id', 'name'])
            ->map(fn ($u) => ['name' => $u->name, 'count' => $u->faculties_count]);

        return $this->ok('Ministry dashboard retrieved successfully', [
            'summary' => [
                'total_universities' => $totalUniversities,
                'pending_letters' => (int) $counts->pending,
                'approved_this_month' => (int) $counts->approved_this_month,
            ],
            'faculties_per_university' => $facultiesPerUniversity,
            'letters_by_status' => $this->statusArray($counts),
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * TASK 2 — University President dashboard.
     */
    protected function universityPresident(int $universityId, ?int $academicYearId)
    {
        $university = University::with('faculties')->findOrFail($universityId);
        $facultyIds = $university->faculties->pluck('id');

        $officialUserIds = $this->userIdsUnderUniversity($universityId);

        $counts = $this->letterStatusCounts($officialUserIds, $academicYearId);

        $departmentAdmins = Department::whereIn('faculty_id', $facultyIds)
            ->whereNotNull('admin_id')
            ->count();

        $activeDeans = Faculty::whereIn('id', $facultyIds)
            ->whereNotNull('admin_id')
            ->count();

        $studentsPerFaculty = Faculty::whereIn('id', $facultyIds)
            ->get(['id', 'name'])
            ->map(fn ($f) => [
                'name' => $f->name,
                'count' => DB::table('students')
                    ->join('departments', 'departments.id', '=', 'students.department_id')
                    ->where('departments.faculty_id', $f->id)
                    ->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $divisions = $this->universityDivisionsThisWeek($universityId);

        return $this->ok('University dashboard retrieved successfully', [
            'summary' => [
                'total_faculties' => $facultyIds->count(),
                'pending_letters' => (int) $counts->pending,
                'department_admins' => $departmentAdmins,
                'active_deans' => $activeDeans,
            ],
            'divisions' => $divisions,
            'students_per_faculty' => $studentsPerFaculty,
            'letters_by_status' => $this->statusArray($counts),
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * Weekly letter activity for the three university-level admin divisions
     * (Administration, Students, Science) — shown on the University President
     * dashboard as a rollup of their subordinate admins' activity this week.
     */
    protected function universityDivisionsThisWeek(int $universityId)
    {
        $roleLabels = [
            'UNIVERSITY_ADMIN_ADMINISTRATION' => 'Administration',
            'UNIVERSITY_ADMIN_STUDENTS' => 'Student Affairs',
            'UNIVERSITY_ADMIN_SCIENCE' => 'Scientific Affairs',
        ];

        return collect($roleLabels)->map(function ($label, $roleName) use ($universityId) {
            $userId = UserScope::where('scope_type', 'UNIVERSITY')
                ->where('scope_id', $universityId)
                ->whereHas('role', fn ($q) => $q->where('name', $roleName))
                ->value('user_id');

            $lettersThisWeek = $userId
                ? Letter::where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
                })
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count()
                : 0;

            return [
                'label' => $label,
                'letters_this_week' => $lettersThisWeek,
            ];
        })->values();
    }

    /**
     * TASK 3 — Admin-Administration dashboard.
     * No dedicated entity — personal inbox only.
     */
    protected function adminAdministration(?int $academicYearId)
    {
        $counts = $this->letterStatusCounts(
            collect([auth()->id()]),
            $academicYearId,
            withApprovedThisMonth: true,
            withRejectedThisMonth: true
        );

        return $this->ok('Admin - Administration dashboard retrieved successfully', [
            'summary' => [
                'pending_letters' => (int) $counts->pending,
                'approved_this_month' => (int) $counts->approved_this_month,
                'rejected_this_month' => (int) $counts->rejected_this_month,
            ],
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * TASK 4 — Admin-Students dashboard.
     *
     * "Student Requests" is NOT a separate entity — it's the total count
     * of letters (any status) sent by OR sent to this Admin-Students user.
     */
    protected function adminStudents(?int $academicYearId)
    {
        $counts = $this->letterStatusCounts(
            collect([auth()->id()]),
            $academicYearId,
            withApprovedThisMonth: true
        );

        $totalLetters = (int) $counts->pending + (int) $counts->approved + (int) $counts->rejected;

        return $this->ok('Admin - Students dashboard retrieved successfully', [
            'summary' => [
                'pending_letters' => (int) $counts->pending,
                'student_requests' => $totalLetters,
                'approved_this_month' => (int) $counts->approved_this_month,
            ],
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * TASK 5 — Admin-Science dashboard.
     *
     * Same pattern as Task 4: "Academic Requests" is the total count of
     * letters (any status) sent by OR to this Admin-Science user.
     */
    protected function adminScience(?int $academicYearId)
    {
        $counts = $this->letterStatusCounts(
            collect([auth()->id()]),
            $academicYearId,
            withApprovedThisMonth: true
        );

        $totalLetters = (int) $counts->pending + (int) $counts->approved + (int) $counts->rejected;

        return $this->ok('Admin - Science dashboard retrieved successfully', [
            'summary' => [
                'pending_letters' => (int) $counts->pending,
                'academic_requests' => $totalLetters,
                'approved_this_month' => (int) $counts->approved_this_month,
            ],
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * TASK 6 — Dean dashboard.
     */
    protected function dean(int $facultyId, ?int $academicYearId)
    {
        $faculty = Faculty::with('departments')->findOrFail($facultyId);
        $departmentIds = $faculty->departments->pluck('id');

        $officialUserIds = $this->userIdsUnderFaculty($facultyId);

        $counts = $this->letterStatusCounts($officialUserIds, $academicYearId, withApprovedThisMonth: true);

        $headsOfDepartment = Department::whereIn('id', $departmentIds)
            ->whereNotNull('admin_id')
            ->count();

        $studentsPerDepartment = Department::whereIn('id', $departmentIds)
            ->withCount('students')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['name' => $d->name, 'count' => $d->students_count])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        return $this->ok('Dean dashboard retrieved successfully', [
            'summary' => [
                'total_departments' => $departmentIds->count(),
                'pending_letters' => (int) $counts->pending,
                'heads_of_department' => $headsOfDepartment,
                'approved_this_month' => (int) $counts->approved_this_month,
            ],
            'students_per_department' => $studentsPerDepartment,
            'letters_by_status' => $this->statusArray($counts),
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * TASK 7 — Head of Department dashboard.
     */
    protected function headOfDepartment(int $departmentId, ?int $academicYearId)
    {
        $department = Department::with('faculty')
            ->withCount(['students', 'teachers'])
            ->findOrFail($departmentId);

        $userIds = $this->userIdsForDepartment($departmentId);

        $counts = $this->letterStatusCounts(
            $userIds,
            $academicYearId,
            withApprovedThisMonth: true,
            withRejectedThisMonth: true
        );

        return $this->ok('Head of Department dashboard retrieved successfully', [
            'summary' => [
                'pending_letters' => (int) $counts->pending,
                'approved_this_month' => (int) $counts->approved_this_month,
                'rejected_this_month' => (int) $counts->rejected_this_month,
            ],
            'info' => [
                'department' => $department->name,
                'faculty' => $department->faculty->name,
                'students' => $department->students_count,
                'lecturers' => $department->teachers_count,
            ],
            'recent_letters' => $this->recentLettersForUser(auth()->id()),
        ]);
    }

    /**
     * Shared aggregation: pending/approved/rejected counts (and optionally
     * approved-this-month / rejected-this-month) in a SINGLE query, scoped
     * to letters where the given user IDs are sender OR receiver.
     *
     * Business rule: status counts always aggregate BOTH outgoing and
     * incoming letters for the given scope — never one-directional.
     */
    protected function letterStatusCounts(
        $userIds,
        ?int $academicYearId,
        bool $withApprovedThisMonth = false,
        bool $withRejectedThisMonth = false
    ) {
        $baseQuery = fn () => Letter::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('sender_id', $userIds)->orWhereIn('receiver_id', $userIds);
            })
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        // MySQL-only MONTH()/YEAR() raw functions break on SQLite (used in dev).
        // Keep the status breakdown driver-agnostic via a simple CASE WHEN
        // (no date functions), and compute "this month" counts separately
        // using Laravel's whereMonth()/whereYear() — which Laravel translates
        // to the correct SQL for whichever DB driver is active.
        $counts = $baseQuery()->selectRaw(<<<'SQL'
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        SQL)->first();

        $result = (object) [
            'pending' => $counts->pending,
            'approved' => $counts->approved,
            'rejected' => $counts->rejected,
        ];

        if ($withApprovedThisMonth) {
            $result->approved_this_month = $baseQuery()
                ->where('status', 'approved')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();
        }

        if ($withRejectedThisMonth) {
            $result->rejected_this_month = $baseQuery()
                ->where('status', 'rejected')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();
        }

        return $result;
    }

    /**
     * Format a letterStatusCounts() result into the standard
     * {pending, approved, rejected} array used by donut charts.
     */
    protected function statusArray($counts): array
    {
        $pending = (int) $counts->pending;
        $approved = (int) $counts->approved;
        $rejected = (int) $counts->rejected;

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $pending + $approved + $rejected,
        ];
    }

    /**
     * Helper: recent letters addressed to a specific user.
     */
    protected function recentLettersForUser(int $userId, int $limit = 2)
    {
        return Letter::with(['sender:id,name', 'receiver:id,name'])
            ->where('receiver_id', $userId)
            ->latest()
            ->take($limit)
            ->get(['id', 'title', 'status', 'sender_id', 'receiver_id', 'created_at']);
    }
}
