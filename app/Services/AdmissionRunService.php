<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads an admission run's data, allocates it and writes the outcome.
 *
 * The Artisan command and the queued job are both entry points onto this class
 * and hold no logic of their own, so a local run and a queued run cannot
 * diverge.
 *
 * No Eloquent anywhere in the run path: hydrating models for tens of thousands
 * of students and millions of choices would dominate both memory and runtime.
 */
class AdmissionRunService
{
    /**
     * Students loaded per window. One cohort is far too small to query for on
     * its own, so consecutive cohorts are accumulated up to this many students
     * and loaded together. Never more than one window is held at a time.
     */
    public const WINDOW_STUDENT_TARGET = 2000;

    /**
     * Rows per write statement. Keeps the id lists and inserts to a size MySQL
     * plans well, and lets each batch commit on its own.
     */
    public const WRITE_BATCH_SIZE = 2000;

    /**
     * Cohorts between progress reports. The queued job writes each one to the
     * cache, so reporting every cohort would cost thousands of cache writes for
     * a run that only takes a couple of minutes.
     */
    public const PROGRESS_COHORT_INTERVAL = 50;

    /**
     * PROVISIONAL. department_offering_subjects.minimum_grade is not treated as
     * an entry gate. The table may carry placeholder values, and switching this
     * on while it does would reject every applicant.
     */
    public const ENFORCE_SUBJECT_MINIMUM_GRADE = false;

    /** @var array<string, array{seconds: float, peak_mb: float}> */
    private array $phases = [];

    private ?Closure $onProgress = null;

    /**
     * PROVISIONAL. grade_average both groups students into cohorts and sets the
     * order cohorts are processed in; the per choice score only settles
     * contests inside a cohort. Kept behind a method because if offerings turn
     * out to rank purely on score, the grouping changes shape rather than value.
     */
    public function cohortKeyColumn(): string
    {
        return 'grade_average';
    }

    public function activeYearId(): int
    {
        $id = DB::table('academic_years')->where('is_active', 1)->value('id');

        if ($id === null) {
            throw new RuntimeException('No academic year is marked active.');
        }

        return (int) $id;
    }

    /**
     * @param  Closure(string, ?int): void|null  $onProgress
     */
    public function run(
        int $academicYearId,
        ?int $limit = null,
        bool $dryRun = false,
        ?Closure $onProgress = null,
    ): AdmissionRunSummary {
        DB::connection()->disableQueryLog();

        $this->onProgress = $onProgress;
        $this->phases = [];

        $summary = new AdmissionRunSummary;

        $summary->add('academic year', $academicYearId)
            ->add('limit', $limit)
            ->add('dry run', $dryRun);

        $this->guardTelescope($summary, $limit);

        $this->phase('preflight', fn () => $this->preflight($academicYearId));

        /** @var array{seats: array<int, int>, department: array<int, int>, skipped_departments: int, offerings: int} $offerings */
        $offerings = $this->phase('offerings', fn () => $this->loadOfferings($academicYearId));

        $weights = $this->phase('weights', fn () => $this->loadDepartmentWeights());

        $manifest = $this->phase('manifest', fn () => $this->loadCohortManifest($limit));

        $run = $this->phase(
            'allocation',
            fn () => $this->allocate($academicYearId, $manifest, $offerings, $weights)
        );

        /** @var AdmissionAllocator $allocator */
        $allocator = $run['allocator'];
        $totals = $run['totals'];

        $totalSeats = array_sum($offerings['seats']);
        $expansionSeats = array_sum(array_column($allocator->expansions(), 'extra_seats'));
        $stats = $allocator->stats();

        $summary
            ->add('cohorts', count($manifest))
            ->add('students in scope', array_sum(array_column($manifest, 'students')))
            ->add('offerings with seats', $offerings['offerings'])
            ->add('total seats', $totalSeats)
            ->add('departments skipped (no seat_available)', $offerings['skipped_departments'])
            ->add('departments with subject weights', count($weights))
            ->add('windows', $totals['windows'])
            ->add('students loaded', $totals['students'])
            ->add('eligible choices loaded', $totals['choices'])
            ->add('students with no eligible choice', $totals['students_without_choices'])
            ->add('subject marks loaded', $totals['marks'])
            ->add('choices per student (avg)', $totals['students'] > 0
                ? round($totals['choices'] / $totals['students'], 2)
                : 0)
            ->add('cohorts allocated', $totals['cohorts_allocated'])
            ->add('students placed', $totals['placed'])
            ->add('students unplaced', $totals['unplaced'])
            ->add('seats used', $totalSeats + $expansionSeats - array_sum($allocator->remainingSeats()))
            ->add('seats left', array_sum($allocator->remainingSeats()))
            ->add('tracks that filled', count($allocator->cutoffs()))
            ->add('contests', $stats['contests'])
            ->add('  decided by choice score', $stats['decided_by_score'])
            ->add('  decided by weighted subjects', $stats['decided_by_weighted_subjects'])
            ->add('  decided by seat expansion', $stats['decided_by_expansion'])
            ->add('seat expansions', count($allocator->expansions()))
            ->add('extra seats granted', $expansionSeats);

        $this->warnOnDeadRung($summary, $stats);

        if ($totals['students'] !== $totals['placed'] + $totals['unplaced']) {
            $summary->warn(
                'Loaded students and allocated students disagree, a cohort was not handed to the allocator.'
            );
        }

        if ($dryRun) {
            $summary->add('written', 'nothing (dry run)');
        } else {
            // A --limit run only looked at the cohorts above this grade, so the
            // writes are scoped to them. Rejecting students the run never
            // evaluated would be wrong, and on a full run this is the lowest
            // grade in the table so the scope covers everyone anyway.
            $lowestGrade = $limit === null || $manifest === []
                ? null
                : $manifest[array_key_last($manifest)]['grade'];

            $written = $this->phase(
                'persistence',
                fn () => $this->persist(
                    $academicYearId,
                    $run['placements'],
                    $run['accepted_choice_ids'],
                    $allocator->cutoffs(),
                    $lowestGrade,
                )
            );

            $summary
                ->add('choices reset to rejected', $written['choices_reset'])
                ->add('choices marked accepted', $written['choices_accepted'])
                ->add('students marked accepted', $written['students_accepted'])
                ->add('students marked rejected', $written['students_rejected'])
                ->add('offering cutoffs written', $written['cutoffs_written']);

            $problems = $this->phase(
                'verification',
                fn () => $this->verifyWrites($academicYearId, $offerings['seats'], $allocator->expansions())
            );

            $summary->add('verification', $problems === [] ? 'passed' : 'FAILED');

            foreach ($problems as $problem) {
                $summary->warn($problem);
            }
        }

        $summary->addPhase('  window loading', $totals['load_seconds'], memory_get_peak_usage(true) / 1_048_576);
        $summary->addPhase('  allocating', $totals['allocate_seconds'], memory_get_peak_usage(true) / 1_048_576);

        foreach ($this->phases as $phase => $timing) {
            $summary->addPhase($phase, $timing['seconds'], $timing['peak_mb']);
        }

        $this->report(
            "Allocation complete: {$totals['placed']} placed, {$totals['unplaced']} unplaced.",
            100
        );

        return $summary;
    }

    /**
     * Write the outcome.
     *
     * Two bulk patterns rather than per row updates: the choices are reset in
     * one statement and the winners marked in batches, and the placements go
     * through a temporary table so the students table is updated by a join.
     *
     * Deliberately not wrapped in a single transaction. One transaction over
     * millions of rows builds an enormous undo log and holds locks for the
     * whole run; each batch commits on its own instead.
     *
     * @param  array<int, int>  $placements  studentId => offeringId
     * @param  list<int>  $acceptedChoiceIds
     * @param  array<int, string>  $cutoffs
     * @return array<string, int>
     */
    private function persist(
        int $academicYearId,
        array $placements,
        array $acceptedChoiceIds,
        array $cutoffs,
        ?string $lowestGrade,
    ): array {
        $written = [
            'choices_reset' => 0,
            'choices_accepted' => 0,
            'students_accepted' => 0,
            'students_rejected' => 0,
            'cutoffs_written' => 0,
        ];

        $this->report('Persisting: resetting choices.', null);

        $written['choices_reset'] = $this->resetChoices($academicYearId, $lowestGrade);

        foreach (array_chunk($acceptedChoiceIds, self::WRITE_BATCH_SIZE) as $batch) {
            $written['choices_accepted'] += DB::table('students_choices')
                ->whereIn('id', $batch)
                ->update(['status' => 'accepted', 'updated_at' => now()]);
        }

        $this->report('Persisting: writing placements.', null);

        // Not "+=": the array union operator keeps the left hand value for keys
        // that already exist, which would silently report the initial zeroes.
        $written = array_merge($written, $this->persistPlacements($placements, $lowestGrade));

        $this->report('Persisting: writing cutoffs.', null);

        $written['cutoffs_written'] = $this->persistCutoffs($academicYearId, $cutoffs);

        return $written;
    }

    /**
     * A full run resets every choice of the year in one statement. A --limit
     * run must not touch cohorts it never looked at, so it is scoped to the
     * grades actually processed.
     */
    private function resetChoices(int $academicYearId, ?string $lowestGrade): int
    {
        $query = DB::table('students_choices')->where('academic_year_id', $academicYearId);

        if ($lowestGrade !== null) {
            $query->whereIn('student_id', function ($sub) use ($lowestGrade) {
                $sub->select('id')
                    ->from('high_school_students')
                    ->where('is_active', 1)
                    ->where($this->cohortKeyColumn(), '>=', $lowestGrade);
            });
        }

        return $query->update(['status' => 'rejected', 'updated_at' => now()]);
    }

    /**
     * @param  array<int, int>  $placements
     * @return array<string, int>
     */
    private function persistPlacements(array $placements, ?string $lowestGrade): array
    {
        DB::statement('drop temporary table if exists tmp_allocations');
        DB::statement(
            'create temporary table tmp_allocations ('
            .'student_id bigint unsigned primary key, '
            .'offering_id bigint unsigned not null)'
        );

        $rows = [];

        foreach ($placements as $studentId => $offeringId) {
            $rows[] = ['student_id' => $studentId, 'offering_id' => $offeringId];
        }

        foreach (array_chunk($rows, self::WRITE_BATCH_SIZE) as $batch) {
            DB::table('tmp_allocations')->insert($batch);
        }

        // updated_at is written too, so the row count reflects the students the
        // run decided rather than only those whose outcome happened to change:
        // MySQL reports changed rows, not matched rows. It also leaves a record
        // of when a placement was last decided.
        $now = now();

        $accepted = DB::update(
            'update high_school_students h '
            .'join tmp_allocations t on t.student_id = h.id '
            .'set h.accepted_department_offering_id = t.offering_id, h.status = ?, h.updated_at = ?',
            ['accepted', $now]
        );

        $rejectSql = 'update high_school_students '
            .'set accepted_department_offering_id = null, status = ?, updated_at = ? '
            .'where is_active = 1 '
            .'and id not in (select student_id from tmp_allocations)';

        $bindings = ['rejected', $now];

        if ($lowestGrade !== null) {
            $rejectSql .= ' and '.$this->cohortKeyColumn().' >= ?';
            $bindings[] = $lowestGrade;
        }

        $rejected = DB::update($rejectSql, $bindings);

        DB::statement('drop temporary table if exists tmp_allocations');

        return [
            'students_accepted' => $accepted,
            'students_rejected' => $rejected,
        ];
    }

    /**
     * A track that never filled keeps a null minimum_grade: publishing the
     * lowest admission of a track with seats to spare would invent a bar that
     * never applied.
     *
     * @param  array<int, string>  $cutoffs
     */
    private function persistCutoffs(int $academicYearId, array $cutoffs): int
    {
        DB::table('department_offerings')
            ->where('academic_year_id', $academicYearId)
            ->update(['minimum_grade' => null, 'updated_at' => now()]);

        // Offerings sharing a cutoff are written together; there are at most a
        // couple of hundred offerings, so this stays a handful of statements.
        $byGrade = [];

        foreach ($cutoffs as $offeringId => $grade) {
            $byGrade[$grade][] = $offeringId;
        }

        ksort($byGrade);

        $written = 0;

        foreach ($byGrade as $grade => $offeringIds) {
            $written += DB::table('department_offerings')
                ->where('academic_year_id', $academicYearId)
                ->whereIn('id', $offeringIds)
                ->update(['minimum_grade' => $grade, 'updated_at' => now()]);
        }

        return $written;
    }

    /**
     * Read the written data back and check it against what the run intended.
     *
     * @param  array<int, int>  $seats  offeringId => seats before expansion
     * @param  list<array{offering_id: int, extra_seats: int, student_ids: list<int>}>  $expansions
     * @return list<string> problems found, empty when the write is sound
     */
    private function verifyWrites(int $academicYearId, array $seats, array $expansions): array
    {
        $problems = [];

        $granted = [];

        foreach ($expansions as $expansion) {
            $granted[$expansion['offering_id']] = ($granted[$expansion['offering_id']] ?? 0) + $expansion['extra_seats'];
        }

        $accepted = DB::table('students_choices')
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'accepted')
            ->groupBy('department_offering_id')
            ->selectRaw('department_offering_id, count(*) as accepted')
            ->get();

        foreach ($accepted as $row) {
            $offeringId = (int) $row->department_offering_id;
            $allowed = ($seats[$offeringId] ?? 0) + ($granted[$offeringId] ?? 0);

            if ((int) $row->accepted > $allowed) {
                $problems[] = "Offering {$offeringId} has {$row->accepted} accepted choices for {$allowed} seats.";
            }
        }

        // Selecting the grouped column explicitly: a bare count() on a grouped
        // query wraps it in "select *", which only_full_group_by rejects.
        $doubleAccepted = DB::table('students_choices')
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'accepted')
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();

        if ($doubleAccepted > 0) {
            $problems[] = "{$doubleAccepted} students hold more than one accepted choice.";
        }

        $ineligible = DB::table('students_choices as c')
            ->join('department_offerings as o', 'o.id', '=', 'c.department_offering_id')
            ->join('departments as d', 'd.id', '=', 'o.department_id')
            ->join('high_school_students as h', 'h.id', '=', 'c.student_id')
            ->where('c.academic_year_id', $academicYearId)
            ->where('c.status', 'accepted')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('h.major_type', '!=', 'scientific')
                        ->where('o.major_type', '!=', 'literary');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('d.accepted_gender')
                        ->where('d.accepted_gender', '!=', 'both')
                        ->whereColumn('d.accepted_gender', '!=', 'h.gender');
                });
            })
            ->count();

        if ($ineligible > 0) {
            $problems[] = "{$ineligible} accepted choices fail the eligibility rules.";
        }

        $mismatched = DB::table('high_school_students as h')
            ->leftJoin('students_choices as c', function ($join) use ($academicYearId) {
                $join->on('c.student_id', '=', 'h.id')
                    ->where('c.academic_year_id', '=', $academicYearId)
                    ->where('c.status', '=', 'accepted');
            })
            ->whereNotNull('h.accepted_department_offering_id')
            ->where(function ($query) {
                $query->whereNull('c.id')
                    ->orWhereColumn('c.department_offering_id', '!=', 'h.accepted_department_offering_id');
            })
            ->count();

        if ($mismatched > 0) {
            $problems[] = "{$mismatched} placed students have no matching accepted choice.";
        }

        $statusMismatch = DB::table('high_school_students')
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('status', 'accepted')->whereNull('accepted_department_offering_id');
                })->orWhere(function ($inner) {
                    $inner->where('status', '!=', 'accepted')->whereNotNull('accepted_department_offering_id');
                });
            })
            ->count();

        if ($statusMismatch > 0) {
            $problems[] = "{$statusMismatch} students have a status that disagrees with their placement.";
        }

        return $problems;
    }

    /**
     * PROVISIONAL guard for the ranking defaults.
     *
     * students_choices.score is NOT NULL, but if it was seeded as a copy of
     * grade_average it can never separate a cohort, and every contest silently
     * falls through to the subject total. Say so rather than let a dead rung
     * pass for a working one.
     *
     * @param  array<string, int>  $stats
     */
    private function warnOnDeadRung(AdmissionRunSummary $summary, array $stats): void
    {
        if ($stats['contests'] === 0) {
            return;
        }

        if ($stats['decided_by_score'] === 0) {
            $summary->warn(
                'No contest was decided by the choice score. It may be a copy of grade_average, '
                .'which leaves the ladder resting entirely on weighted subjects.'
            );
        }

        if ($stats['decided_by_weighted_subjects'] === 0) {
            $summary->warn(
                'No contest reached the weighted subject total. Either the choice score always '
                .'separates, or department_offering_subjects carries no usable credits.'
            );
        }
    }

    /**
     * Telescope records every query. On a full run that dominates the runtime
     * and bloats the database, so say so loudly rather than silently producing
     * a misleading timing.
     */
    private function guardTelescope(AdmissionRunSummary $summary, ?int $limit): void
    {
        if (! config('telescope.enabled', false) || $limit !== null) {
            return;
        }

        $warning = 'Telescope is enabled and this is a full run. It records every query and will dominate the runtime. Set TELESCOPE_ENABLED=false.';

        $summary->warn($warning);
        $this->report('WARNING: '.$warning, null);
    }

    /**
     * Everything that must hold before a single seat is handed out.
     */
    private function preflight(int $academicYearId): void
    {
        $activeYears = (int) DB::table('academic_years')->where('is_active', 1)->count();

        if ($activeYears !== 1) {
            throw new RuntimeException(
                "Expected exactly one active academic year, found {$activeYears}."
            );
        }

        $malformed = DB::table('department_offerings')
            ->where('academic_year_id', $academicYearId)
            ->groupBy('department_id')
            ->havingRaw('count(*) <> 2 or sum(capacity) <> 100')
            ->orderBy('department_id')
            ->pluck('department_id');

        if ($malformed->isNotEmpty()) {
            $shown = $malformed->take(10)->implode(', ');

            throw new RuntimeException(
                'Every department must have two offerings whose capacities total 100. '
                ."Departments failing that: {$shown}".($malformed->count() > 10 ? ' ...' : '').'.'
            );
        }
    }

    /**
     * Seats per offering.
     *
     * capacity is a percentage of the department's seat_available, not a seat
     * count. The pair is computed together so rounding cannot lose or invent a
     * seat: the zankoline track floors, and parallel takes the remainder.
     *
     * @return array{seats: array<int, int>, department: array<int, int>, skipped_departments: int, offerings: int}
     */
    private function loadOfferings(int $academicYearId): array
    {
        $rows = DB::table('department_offerings as o')
            ->join('departments as d', 'd.id', '=', 'o.department_id')
            ->where('o.academic_year_id', $academicYearId)
            ->whereNull('d.deleted_at')
            ->orderBy('o.department_id')
            ->orderBy('o.id')
            ->get(['o.id', 'o.department_id', 'o.track_type', 'o.capacity', 'd.seat_available']);

        $byDepartment = [];

        foreach ($rows as $row) {
            $byDepartment[(int) $row->department_id][] = $row;
        }

        ksort($byDepartment);

        $seats = [];
        $department = [];
        $skipped = 0;

        foreach ($byDepartment as $departmentId => $offerings) {
            $seatAvailable = $offerings[0]->seat_available;

            if ($seatAvailable === null) {
                $skipped++;

                continue;
            }

            $seatAvailable = (int) $seatAvailable;
            $zankolineSeats = 0;

            foreach ($offerings as $offering) {
                if ($offering->track_type === 'zankoline') {
                    $zankolineSeats = intdiv($seatAvailable * (int) $offering->capacity, 100);
                }
            }

            foreach ($offerings as $offering) {
                $offeringSeats = $offering->track_type === 'zankoline'
                    ? $zankolineSeats
                    : $seatAvailable - $zankolineSeats;

                // A track with no seats can never admit anyone, so it is left
                // out entirely rather than carried through the run.
                if ($offeringSeats <= 0) {
                    continue;
                }

                $seats[(int) $offering->id] = $offeringSeats;
                $department[(int) $offering->id] = $departmentId;
            }
        }

        if ($byDepartment !== [] && $skipped === count($byDepartment)) {
            throw new RuntimeException(
                'Every department is missing seat_available, so the run could only place nobody.'
            );
        }

        $total = array_sum($seats);

        if ($total <= 0) {
            throw new RuntimeException('Total seats across all offerings is zero.');
        }

        $this->report("Offerings: {$total} seats across ".count($seats).' tracks.', 5);

        return [
            'seats' => $seats,
            'department' => $department,
            'skipped_departments' => $skipped,
            'offerings' => count($seats),
        ];
    }

    /**
     * Subject credits per department, for the allocator's second tie-break.
     *
     * @return array<int, array<int, int>> departmentId => [subjectId => credit]
     */
    private function loadDepartmentWeights(): array
    {
        $weights = [];

        $rows = DB::table('department_offering_subjects')
            ->orderBy('department_id')
            ->orderBy('subject_id')
            ->get(['department_id', 'subject_id', 'credit']);

        foreach ($rows as $row) {
            $weights[(int) $row->department_id][(int) $row->subject_id] = (int) $row->credit;
        }

        return $weights;
    }

    /**
     * The whole processing plan in one cheap query: every distinct grade
     * average with its population, highest first.
     *
     * @return list<array{grade: string, students: int}>
     */
    private function loadCohortManifest(?int $limit): array
    {
        $column = $this->cohortKeyColumn();

        $rows = DB::table('high_school_students')
            ->where('is_active', 1)
            ->groupBy($column)
            ->orderByDesc($column)
            ->get([$column.' as grade', DB::raw('count(*) as students')]);

        $manifest = [];
        $running = 0;

        foreach ($rows as $row) {
            $manifest[] = [
                'grade' => (string) $row->grade,
                'students' => (int) $row->students,
            ];

            $running += (int) $row->students;

            // --limit stops at a cohort boundary. Splitting a cohort would put
            // students of equal standing on opposite sides of the run.
            if ($limit !== null && $running >= $limit) {
                break;
            }
        }

        $this->report('Manifest: '.count($manifest)." cohorts, {$running} students.", 10);

        return $manifest;
    }

    /**
     * Walk the manifest, loading one window of consecutive cohorts at a time
     * and handing the allocator each cohort in turn.
     *
     * Windows arrive highest grade first and a window's students are ordered by
     * grade descending, so grouping them preserves the order the allocator
     * requires. A window's choice and mark maps are passed whole rather than
     * sliced per cohort: the allocator only ever looks up the ids it was given,
     * and copying the slices would cost more than it saves.
     *
     * @param  list<array{grade: string, students: int}>  $manifest
     * @param  array{seats: array<int, int>, department: array<int, int>}  $offerings
     * @param  array<int, array<int, int>>  $weights
     * @return array{allocator: AdmissionAllocator, totals: array<string, int|float>, placements: array<int, int>, accepted_choice_ids: list<int>}
     */
    private function allocate(int $academicYearId, array $manifest, array $offerings, array $weights): array
    {
        $allocator = new AdmissionAllocator(
            $offerings['seats'],
            $offerings['department'],
            $weights,
        );

        $offeringIds = array_keys($offerings['seats']);

        $totals = [
            'windows' => 0,
            'students' => 0,
            'choices' => 0,
            'marks' => 0,
            'students_without_choices' => 0,
            'cohorts_allocated' => 0,
            'placed' => 0,
            'unplaced' => 0,
            'load_seconds' => 0.0,
            'allocate_seconds' => 0.0,
        ];

        /** @var array<int, int> studentId => offeringId */
        $placements = [];

        /** @var list<int> */
        $acceptedChoiceIds = [];

        $plannedStudents = array_sum(array_column($manifest, 'students'));
        $processed = 0;

        foreach ($this->windows($manifest) as $grades) {
            $startedAt = hrtime(true);
            $window = $this->loadWindow($academicYearId, $grades, $offeringIds);
            $totals['load_seconds'] += (hrtime(true) - $startedAt) / 1_000_000_000;

            $totals['windows']++;
            $totals['students'] += count($window['students']);
            $totals['marks'] += $window['marks_count'];

            foreach ($this->cohortsOf($window['students']) as $grade => $studentIds) {
                foreach ($studentIds as $studentId) {
                    $choiceCount = count($window['choices'][$studentId] ?? []);

                    $totals['choices'] += $choiceCount;

                    if ($choiceCount === 0) {
                        $totals['students_without_choices']++;
                    }
                }

                $startedAt = hrtime(true);

                $cohortPlacements = $allocator->allocateCohort(
                    (string) $grade,
                    $studentIds,
                    $window['choices'],
                    $window['grades'],
                );

                $totals['allocate_seconds'] += (hrtime(true) - $startedAt) / 1_000_000_000;
                $totals['cohorts_allocated']++;

                foreach ($cohortPlacements as $studentId => $offeringId) {
                    if ($offeringId === null) {
                        $totals['unplaced']++;

                        continue;
                    }

                    $totals['placed']++;
                    $placements[$studentId] = $offeringId;

                    $choiceId = $this->winningChoiceId($window['choices'], $studentId, $offeringId);

                    if ($choiceId !== null) {
                        $acceptedChoiceIds[] = $choiceId;
                    }
                }

                $processed += count($studentIds);

                if ($totals['cohorts_allocated'] % self::PROGRESS_COHORT_INTERVAL === 0) {
                    $percent = $plannedStudents > 0
                        ? (int) min(99, 10 + (int) round(85 * $processed / $plannedStudents))
                        : 99;

                    $this->report(
                        "Cohort {$totals['cohorts_allocated']} of ".count($manifest)
                        .": {$totals['placed']} placed, {$totals['unplaced']} unplaced.",
                        $percent
                    );
                }
            }

            // The window goes out of scope here: only one is ever resident.
            unset($window);
        }

        return [
            'allocator' => $allocator,
            'totals' => $totals,
            'placements' => $placements,
            'accepted_choice_ids' => $acceptedChoiceIds,
        ];
    }

    /**
     * Split a window's students into cohorts of equal grade average.
     *
     * The rows arrive ordered by grade descending, so insertion order already
     * is the order the allocator needs.
     *
     * @param  list<object>  $students
     * @return array<string, list<int>>
     */
    private function cohortsOf(array $students): array
    {
        $cohorts = [];

        foreach ($students as $student) {
            $cohorts[(string) $student->grade][] = (int) $student->id;
        }

        return $cohorts;
    }

    /**
     * The choice row that produced a placement, so persistence can mark exactly
     * that row accepted.
     *
     * @param  array<int, list<array{choice_id: int, offering_id: int, score: string}>>  $choices
     */
    private function winningChoiceId(array $choices, int $studentId, int $offeringId): ?int
    {
        foreach ($choices[$studentId] ?? [] as $choice) {
            if ($choice['offering_id'] === $offeringId) {
                return $choice['choice_id'];
            }
        }

        return null;
    }

    /**
     * Group consecutive cohorts into windows of about WINDOW_STUDENT_TARGET
     * students.
     *
     * @param  list<array{grade: string, students: int}>  $manifest
     * @return iterable<int, list<string>>
     */
    private function windows(array $manifest): iterable
    {
        $grades = [];
        $students = 0;

        foreach ($manifest as $cohort) {
            $grades[] = $cohort['grade'];
            $students += $cohort['students'];

            if ($students >= self::WINDOW_STUDENT_TARGET) {
                yield $grades;

                $grades = [];
                $students = 0;
            }
        }

        if ($grades !== []) {
            yield $grades;
        }
    }

    /**
     * The three queries a window costs, all keyed on its student ids.
     *
     * @param  list<string>  $grades
     * @param  list<int>  $offeringIds
     * @return array{students: list<object>, choices: array<int, list<array{offering_id: int, score: string}>>, grades: array<int, array<int, string>>, marks_count: int}
     */
    private function loadWindow(int $academicYearId, array $grades, array $offeringIds): array
    {
        $column = $this->cohortKeyColumn();

        $students = DB::table('high_school_students')
            ->where('is_active', 1)
            ->whereIn($column, $grades)
            ->orderByDesc($column)
            ->orderBy('id')
            ->get(['id', $column.' as grade', 'major_type', 'gender'])
            ->all();

        if ($students === []) {
            return ['students' => [], 'choices' => [], 'grades' => [], 'marks_count' => 0];
        }

        $studentIds = array_map(static fn (object $student): int => (int) $student->id, $students);

        $choices = $this->loadChoices($academicYearId, $studentIds, $offeringIds);
        $marks = $this->loadSubjectMarks($studentIds);

        return [
            'students' => $students,
            'choices' => $choices,
            'grades' => $marks['grades'],
            'marks_count' => $marks['count'],
        ];
    }

    /**
     * Eligible choices only, filtered in SQL.
     *
     * Filtering here rather than in PHP is the difference between transferring
     * the rows that matter and transferring every row and throwing most away;
     * choice loading is the single largest cost in the run.
     *
     * @param  list<int>  $studentIds
     * @param  list<int>  $offeringIds
     * @return array<int, list<array{offering_id: int, score: string}>>
     */
    private function loadChoices(int $academicYearId, array $studentIds, array $offeringIds): array
    {
        $rows = DB::table('students_choices as c')
            ->join('department_offerings as o', 'o.id', '=', 'c.department_offering_id')
            ->join('departments as d', 'd.id', '=', 'o.department_id')
            ->join('high_school_students as h', 'h.id', '=', 'c.student_id')
            ->whereIn('c.student_id', $studentIds)
            ->where('c.academic_year_id', $academicYearId)
            ->where('o.academic_year_id', $academicYearId)
            ->whereIn('o.id', $offeringIds)
            ->whereNull('d.deleted_at')
            // A scientific graduate may take any track, a literary one only
            // literary tracks. The asymmetry is intended.
            ->where(function ($query) {
                $query->where('h.major_type', 'scientific')
                    ->orWhere('o.major_type', 'literary');
            })
            // NULL accepted_gender means the department has no gender rule; an
            // IN comparison against NULL would silently exclude it.
            ->where(function ($query) {
                $query->whereNull('d.accepted_gender')
                    ->orWhere('d.accepted_gender', 'both')
                    ->orWhereColumn('d.accepted_gender', 'h.gender');
            })
            ->orderBy('c.student_id')
            ->orderBy('c.preference_order')
            ->orderBy('c.id')
            ->get(['c.id', 'c.student_id', 'c.department_offering_id', 'c.score']);

        $choices = [];

        foreach ($rows as $row) {
            $choices[(int) $row->student_id][] = [
                'choice_id' => (int) $row->id,
                'offering_id' => (int) $row->department_offering_id,
                'score' => (string) $row->score,
            ];
        }

        return $choices;
    }

    /**
     * @param  list<int>  $studentIds
     * @return array{grades: array<int, array<int, string>>, count: int}
     */
    private function loadSubjectMarks(array $studentIds): array
    {
        $rows = DB::table('student_subjects')
            ->whereIn('student_id', $studentIds)
            ->orderBy('student_id')
            ->orderBy('subject_id')
            ->get(['student_id', 'subject_id', 'grade']);

        $grades = [];
        $count = 0;

        foreach ($rows as $row) {
            $grades[(int) $row->student_id][(int) $row->subject_id] = (string) $row->grade;
            $count++;
        }

        return ['grades' => $grades, 'count' => $count];
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    private function phase(string $name, Closure $work): mixed
    {
        $startedAt = hrtime(true);

        $result = $work();

        $this->phases[$name] = [
            'seconds' => (hrtime(true) - $startedAt) / 1_000_000_000,
            'peak_mb' => memory_get_peak_usage(true) / 1_048_576,
        ];

        return $result;
    }

    private function report(string $message, ?int $percent): void
    {
        if ($this->onProgress !== null) {
            ($this->onProgress)($message, $percent);
        }
    }
}
