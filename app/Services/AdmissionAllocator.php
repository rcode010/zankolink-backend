<?php

namespace App\Services;

/**
 * Cohort based deferred acceptance for the admission run.
 *
 * Pure by design: every input arrives through the constructor or through
 * allocateCohort(), so the algorithm can be exercised against a handful of
 * hand written students with no database behind it.
 *
 * Cohorts MUST be handed over highest grade average first. Seats are consumed
 * as each cohort finishes, and no later cohort can outrank an earlier one, so a
 * cohort's placements are final once allocateCohort() returns and only one
 * cohort is ever held in memory.
 */
class AdmissionAllocator
{
    /**
     * Decimal columns are compared as integer thousandths. decimal(6,3) values
     * are exact at this scale, floats are not, and ranking is where that shows.
     */
    private const DECIMAL_SCALE = 1000;

    /** @var array<int, int> offeringId => seats still free */
    private array $remainingSeats;

    /** @var array<int, string> offeringId => grade average of its last admission */
    private array $lastAdmittedGrade = [];

    /** @var list<array{offering_id: int, extra_seats: int, student_ids: list<int>}> */
    private array $expansions = [];

    /** @var array<string, int> */
    private array $stats = [
        'contests' => 0,
        'decided_by_score' => 0,
        'decided_by_weighted_subjects' => 0,
        'decided_by_expansion' => 0,
    ];

    /** @var array<int, array<int, string>> Grades of the cohort in flight. */
    private array $cohortGrades = [];

    /** @var array<int, array<int, int>> studentId => departmentId => weighted total */
    private array $weightedTotals = [];

    /**
     * @param  array<int, int>  $offeringSeats  offeringId => seats
     * @param  array<int, int>  $offeringDepartment  offeringId => departmentId
     * @param  array<int, array<int, int>>  $departmentWeights  departmentId => [subjectId => credit]
     */
    public function __construct(
        array $offeringSeats,
        private readonly array $offeringDepartment,
        private readonly array $departmentWeights,
    ) {
        $this->remainingSeats = $offeringSeats;
    }

    /**
     * Place one cohort of students who all share the same grade average.
     *
     * @param  list<int>  $studentIds
     * @param  array<int, list<array{offering_id: int, score: string}>>  $choices
     *                                                                             eligibility filtered, in preference order
     * @param  array<int, array<int, string>>  $studentGrades  studentId => [subjectId => grade]
     * @return array<int, int|null> studentId => offeringId or null
     */
    public function allocateCohort(
        string $gradeAverage,
        array $studentIds,
        array $choices,
        array $studentGrades,
    ): array {
        // The caller's ordering must not reach the outcome.
        sort($studentIds);

        $this->cohortGrades = $studentGrades;
        $this->weightedTotals = [];

        $scores = $this->indexScores($studentIds, $choices);

        $pointer = array_fill_keys($studentIds, 0);

        /** @var array<int, list<int>> offeringId => tentative holders */
        $held = [];

        /** @var array<int, int> studentId => offeringId they are held at */
        $heldBy = [];

        while (true) {
            $proposals = $this->collectProposals($studentIds, $choices, $pointer, $heldBy);

            if ($proposals === []) {
                break;
            }

            // Sorted so PHP's insertion order cannot reach the outcome.
            ksort($proposals);

            foreach ($proposals as $offeringId => $proposers) {
                $candidates = array_merge($held[$offeringId] ?? [], $proposers);

                [$winners] = $this->settle($offeringId, $candidates, $scores);

                // Every candidate is reconsidered, so clear them all and then
                // re-hold the winners. A holder from an earlier round that lost
                // its seat becomes unheld and proposes again next round.
                foreach ($candidates as $studentId) {
                    unset($heldBy[$studentId]);
                }

                foreach ($winners as $studentId) {
                    $heldBy[$studentId] = $offeringId;
                }

                $held[$offeringId] = $winners;
            }
        }

        $this->consumeSeats($held, $gradeAverage);

        $placements = array_fill_keys($studentIds, null);

        foreach ($heldBy as $studentId => $offeringId) {
            $placements[$studentId] = $offeringId;
        }

        ksort($placements);

        return $placements;
    }

    /**
     * Cutoffs of the tracks that filled.
     *
     * A track with seats to spare has no cutoff: publishing its lowest
     * admission would invent a bar that never applied.
     *
     * @return array<int, string> offeringId => grade average
     */
    public function cutoffs(): array
    {
        $cutoffs = [];

        foreach ($this->lastAdmittedGrade as $offeringId => $gradeAverage) {
            if (($this->remainingSeats[$offeringId] ?? 0) <= 0) {
                $cutoffs[$offeringId] = $gradeAverage;
            }
        }

        ksort($cutoffs);

        return $cutoffs;
    }

    /**
     * @return array<int, int>
     */
    public function remainingSeats(): array
    {
        return $this->remainingSeats;
    }

    /**
     * @return list<array{offering_id: int, extra_seats: int, student_ids: list<int>}>
     */
    public function expansions(): array
    {
        return $this->expansions;
    }

    /**
     * Which rung of the ladder settled each contest. A rung that never decides
     * anything is a dead rung, and this is what makes that visible.
     *
     * @return array<string, int>
     */
    public function stats(): array
    {
        return $this->stats;
    }

    /**
     * Every unheld student proposes to their next preference and advances.
     *
     * Deriving the unheld set fresh each round is what lets a tentative holder
     * be displaced by a stronger applicant rejected elsewhere.
     *
     * @param  list<int>  $studentIds
     * @param  array<int, list<array{offering_id: int, score: string}>>  $choices
     * @param  array<int, int>  $pointer
     * @param  array<int, int>  $heldBy
     * @return array<int, list<int>> offeringId => proposers
     */
    private function collectProposals(
        array $studentIds,
        array $choices,
        array &$pointer,
        array $heldBy,
    ): array {
        $proposals = [];

        foreach ($studentIds as $studentId) {
            if (isset($heldBy[$studentId])) {
                continue;
            }

            $preferences = $choices[$studentId] ?? [];
            $at = $pointer[$studentId];

            // Out of preferences: this student is done for good.
            if ($at >= count($preferences)) {
                continue;
            }

            $pointer[$studentId] = $at + 1;

            $proposals[(int) $preferences[$at]['offering_id']][] = $studentId;
        }

        return $proposals;
    }

    /**
     * Decide who holds an offering's seats this round.
     *
     * @param  list<int>  $candidates
     * @param  array<int, array<int, int>>  $scores
     * @return array{0: list<int>, 1: list<int>} winners, losers
     */
    private function settle(int $offeringId, array $candidates, array $scores): array
    {
        $seats = max(0, $this->remainingSeats[$offeringId] ?? 0);

        if ($seats === 0) {
            return [[], $candidates];
        }

        if (count($candidates) <= $seats) {
            return [$candidates, []];
        }

        $this->stats['contests']++;

        $ranked = $this->rank($offeringId, $candidates, $scores);

        $lastAdmittedKey = $this->rankKey($offeringId, $ranked[$seats - 1], $scores);

        // Anyone level with the last admission is admitted too, and the track
        // grows to fit them. Adding seats displaces nobody, so no cascade.
        $tied = [];

        for ($index = $seats; $index < count($ranked); $index++) {
            if ($this->rankKey($offeringId, $ranked[$index], $scores) !== $lastAdmittedKey) {
                break;
            }

            $tied[] = $ranked[$index];
        }

        $this->recordDecision($offeringId, $ranked, $seats, $scores, $tied !== []);

        if ($tied === []) {
            return [array_slice($ranked, 0, $seats), array_slice($ranked, $seats)];
        }

        $this->remainingSeats[$offeringId] = $seats + count($tied);

        $this->expansions[] = [
            'offering_id' => $offeringId,
            'extra_seats' => count($tied),
            'student_ids' => $tied,
        ];

        return [
            array_slice($ranked, 0, $seats + count($tied)),
            array_slice($ranked, $seats + count($tied)),
        ];
    }

    /**
     * Order candidates best first.
     *
     * The cohort already shares a grade average, so the ladder starts below it:
     * the per choice score, then the weighted subject total. The closing
     * comparison on student id only gives usort a total order; real ties are
     * settled by growing the track, not by id.
     *
     * @param  list<int>  $candidates
     * @param  array<int, array<int, int>>  $scores
     * @return list<int>
     */
    private function rank(int $offeringId, array $candidates, array $scores): array
    {
        usort($candidates, function (int $a, int $b) use ($offeringId, $scores): int {
            [$scoreA, $weightedA] = $this->rankKey($offeringId, $a, $scores);
            [$scoreB, $weightedB] = $this->rankKey($offeringId, $b, $scores);

            return ($scoreB <=> $scoreA)
                ?: ($weightedB <=> $weightedA)
                ?: ($a <=> $b);
        });

        return $candidates;
    }

    /**
     * @param  array<int, array<int, int>>  $scores
     * @return array{0: int, 1: int}
     */
    private function rankKey(int $offeringId, int $studentId, array $scores): array
    {
        return [
            $scores[$studentId][$offeringId] ?? 0,
            $this->weightedTotal($studentId, $this->offeringDepartment[$offeringId] ?? null),
        ];
    }

    /**
     * sum(grade x credit) over the department's scored subjects.
     *
     * Every candidate at one offering shares a department and so the same
     * credit total, which makes raw sums comparable without a lossy division. A
     * subject the student has no mark for contributes nothing.
     */
    private function weightedTotal(int $studentId, ?int $departmentId): int
    {
        if ($departmentId === null) {
            return 0;
        }

        if (isset($this->weightedTotals[$studentId][$departmentId])) {
            return $this->weightedTotals[$studentId][$departmentId];
        }

        $grades = $this->cohortGrades[$studentId] ?? [];
        $total = 0;

        foreach ($this->departmentWeights[$departmentId] ?? [] as $subjectId => $credit) {
            if (! isset($grades[$subjectId])) {
                continue;
            }

            $total += self::thousandths($grades[$subjectId]) * (int) $credit;
        }

        return $this->weightedTotals[$studentId][$departmentId] = $total;
    }

    /**
     * @param  list<int>  $ranked
     * @param  array<int, array<int, int>>  $scores
     */
    private function recordDecision(int $offeringId, array $ranked, int $seats, array $scores, bool $expanded): void
    {
        if ($expanded) {
            $this->stats['decided_by_expansion']++;

            return;
        }

        [$scoreIn, $weightedIn] = $this->rankKey($offeringId, $ranked[$seats - 1], $scores);
        [$scoreOut, $weightedOut] = $this->rankKey($offeringId, $ranked[$seats], $scores);

        if ($scoreIn !== $scoreOut) {
            $this->stats['decided_by_score']++;

            return;
        }

        if ($weightedIn !== $weightedOut) {
            $this->stats['decided_by_weighted_subjects']++;
        }
    }

    /**
     * @param  array<int, list<int>>  $held
     */
    private function consumeSeats(array $held, string $gradeAverage): void
    {
        foreach ($held as $offeringId => $students) {
            if ($students === []) {
                continue;
            }

            $this->remainingSeats[$offeringId] -= count($students);
            $this->lastAdmittedGrade[$offeringId] = $gradeAverage;
        }
    }

    /**
     * @param  list<int>  $studentIds
     * @param  array<int, list<array{offering_id: int, score: string}>>  $choices
     * @return array<int, array<int, int>> studentId => offeringId => score in thousandths
     */
    private function indexScores(array $studentIds, array $choices): array
    {
        $scores = [];

        foreach ($studentIds as $studentId) {
            foreach ($choices[$studentId] ?? [] as $choice) {
                $scores[$studentId][(int) $choice['offering_id']] = self::thousandths($choice['score']);
            }
        }

        return $scores;
    }

    private static function thousandths(int|float|string $value): int
    {
        return (int) round(((float) $value) * self::DECIMAL_SCALE);
    }
}
