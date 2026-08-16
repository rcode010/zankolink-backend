<?php

namespace Database\Seeders;

use App\Models\Subject;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the high school graduates the Zankoline admission runs against.
 *
 * Everything is written with bulk statements: students, their grade 12 subject
 * marks and the recalculated averages are all handled per chunk, so the number
 * of queries grows with the number of chunks and never with the number of
 * students.
 */
class HighSchoolStudentSeeder extends Seeder
{
    /**
     * Major types of the high_school_students.major_type enum.
     */
    private const MAJOR_SCIENTIFIC = 'scientific';

    private const MAJOR_LITERARY = 'literary';

    /**
     * Subjects every student sits for, whatever their major is.
     */
    private const MAJOR_GENERAL = 'general';

    /**
     * Year level of the subjects a high school graduate is examined on.
     */
    private const GRADE_LEVEL = 12;

    private const NAME_POOL_SIZE = 400;

    /**
     * How many high school students the table holds once the seeder finished.
     */
    private int $studentsCount = 1000;

    /**
     * Rows per bulk statement. Kept low enough that a batch stays under the
     * bound parameter limit SQLite enforces per statement.
     */
    private int $studentBatchSize = 2000;

    private int $studentSubjectBatchSize = 2000;

    private int $gradeAverageBatchSize = 10000;

    /**
     * Choices every student submits. The admission caps a choice list at 50,
     * see StoreStudentChoiceRequest.
     */
    private int $choicesPerStudent = 50;

    private int $choiceBatchSize = 2000;

    /**
     * Students read per chunk while their choices are written.
     */
    private int $choiceStudentChunkSize = 1000;

    /**
     * Generated codes start above this value, so a re-run can tell the students
     * it generated apart from the ones seeded elsewhere.
     */
    private int $codeBase = 1000000;

    /**
     * Share of the generated students following the scientific major.
     */
    private int $scientificPercentage = 60;

    private string $defaultPassword = 'password';

    private Generator $faker;

    /** @var array<string, list<array{id: int, credit_number: int}>> */
    private array $subjectsByMajorType = [];

    /** @var list<string> */
    private array $maleFirstNames = [];

    /** @var list<string> */
    private array $femaleFirstNames = [];

    /** @var list<string> */
    private array $lastNames = [];

    public function run(): void
    {
        $this->faker = FakerFactory::create('en_US');

        if (! $this->loadSubjects()) {
            $this->command?->warn(
                'No grade '.self::GRADE_LEVEL.' subjects found, skipping the high school students.'
            );

            return;
        }

        $this->seedStudents();
        $this->recalculateGradeAverages();
        $this->seedStudentChoices();
    }

    /**
     * Submit a full choice list for every student.
     *
     * Offerings are loaded once and grouped per major, so a student only ever
     * picks departments admitting their own major. Students are read in chunks
     * and the rows are flushed in bulk, so nothing is queried per student.
     */
    private function seedStudentChoices(): void
    {
        $academicYearId = (int) DB::table('academic_years')
            ->where('is_active', true)
            ->value('id');

        if ($academicYearId === 0) {
            $this->command?->warn('No active academic year, skipping the student choices.');

            return;
        }

        $offerings = $this->loadOfferings($academicYearId);

        if ($offerings === []) {
            $this->command?->warn('No department offerings found, skipping the student choices.');

            return;
        }

        foreach ($offerings as $majorType => $majorOfferings) {
            if (count($majorOfferings) < $this->choicesPerStudent) {
                $this->command?->warn(sprintf(
                    'Only %d %s offerings exist, %s students will choose that many instead of %d.',
                    count($majorOfferings),
                    $majorType,
                    $majorType,
                    $this->choicesPerStudent
                ));
            }
        }

        $pending = DB::table('high_school_students')
            ->whereNotExists($this->missingChoices())
            ->count();

        if ($pending === 0) {
            $this->command?->info('Student choices already seeded, skipping the generation.');

            return;
        }

        $governorates = $this->offeringGovernorates($offerings);

        $now = now();
        $rows = [];

        $output = $this->command?->getOutput();
        $progress = $output?->createProgressBar($pending);
        $progress?->start();

        DB::table('high_school_students')
            ->select('id', 'major_type', 'grade_average')
            ->whereNotExists($this->missingChoices())
            ->orderBy('id')
            ->chunkById($this->choiceStudentChunkSize, function ($students) use (
                $offerings,
                $governorates,
                $academicYearId,
                $now,
                &$rows,
                $progress
            ): void {
                foreach ($students as $student) {
                    $pool = $offerings[$student->major_type] ?? [];

                    if ($pool === []) {
                        continue;
                    }

                    // A student is local to a single governorate, so is_local
                    // stays consistent across their whole choice list.
                    $home = $governorates[$student->id % count($governorates)];

                    shuffle($pool);

                    foreach (array_slice($pool, 0, $this->choicesPerStudent) as $order => $offering) {
                        $rows[] = [
                            'student_id' => $student->id,
                            'department_offering_id' => $offering['id'],
                            'academic_year_id' => $academicYearId,
                            'score' => $student->grade_average,
                            'is_local' => $offering['governorate'] === $home,
                            'preference_order' => $order + 1,
                            'status' => 'pending',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (count($rows) >= $this->choiceBatchSize) {
                        DB::table('students_choices')->insert($rows);

                        $rows = [];
                    }
                }

                $progress?->advance($students->count());
            });

        if ($rows !== []) {
            DB::table('students_choices')->insert($rows);
        }

        $progress?->finish();
        $output?->newLine();
    }

    /**
     * Offerings of the active year, grouped by the major they admit.
     *
     * @return array<string, list<array{id: int, governorate: string}>>
     */
    private function loadOfferings(int $academicYearId): array
    {
        $offerings = [];

        $rows = DB::table('department_offerings')
            ->where('academic_year_id', $academicYearId)
            ->get(['id', 'major_type', 'governorate']);

        foreach ($rows as $row) {
            $offerings[$row->major_type][] = [
                'id' => (int) $row->id,
                'governorate' => $row->governorate,
            ];
        }

        return $offerings;
    }

    /**
     * The governorates that actually have offerings.
     *
     * Students are made local to one of these rather than to the full
     * governorate enum: a university only sits in some of them, and a student
     * local to a governorate without offerings could never be local to any
     * choice on their list.
     *
     * @param  array<string, list<array{id: int, governorate: string}>>  $offerings
     * @return list<string>
     */
    private function offeringGovernorates(array $offerings): array
    {
        $governorates = [];

        foreach ($offerings as $majorOfferings) {
            foreach ($majorOfferings as $offering) {
                $governorates[$offering['governorate']] = true;
            }
        }

        $governorates = array_keys($governorates);

        sort($governorates);

        return $governorates;
    }

    /**
     * Matches the students that have not submitted their choices yet, so a
     * re-run only fills in what is missing.
     */
    private function missingChoices(): callable
    {
        return static fn ($query) => $query
            ->select(DB::raw(1))
            ->from('students_choices')
            ->whereColumn('students_choices.student_id', 'high_school_students.id');
    }

    /**
     * Load the grade 12 subjects once and group them per major.
     *
     * Every student is then built from these two in-memory lists, so the
     * subjects table is never queried again while generating students.
     */
    private function loadSubjects(): bool
    {
        $subjects = Subject::query()
            ->where('year_level', self::GRADE_LEVEL)
            ->get(['id', 'major_type', 'credit_number']);

        foreach ([self::MAJOR_SCIENTIFIC, self::MAJOR_LITERARY] as $majorType) {
            $this->subjectsByMajorType[$majorType] = $subjects
                // A major keeps its own subjects plus the shared general ones,
                // never the subjects of the other major.
                ->whereIn('major_type', [$majorType, self::MAJOR_GENERAL])
                ->map(static fn (Subject $subject): array => [
                    'id' => (int) $subject->id,
                    'credit_number' => (int) $subject->credit_number,
                ])
                ->values()
                ->all();

            if ($this->subjectsByMajorType[$majorType] === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Top the table up to the configured amount of students.
     *
     * Each chunk inserts its students, reads back the generated ids with a
     * single query and writes the matching student_subjects rows.
     */
    private function seedStudents(): void
    {
        $missing = $this->studentsCount - (int) DB::table('high_school_students')->count();

        if ($missing <= 0) {
            $this->command?->info(
                'High school students already seeded, skipping the generation.'
            );

            return;
        }

        $this->prepareNamePools();

        $hashedPassword = Hash::make($this->defaultPassword);
        $now = now();
        $code = $this->nextStudentCode();

        $output = $this->command?->getOutput();
        $progress = $output?->createProgressBar($missing);
        $progress?->start();

        for ($created = 0; $created < $missing; $created += $this->studentBatchSize) {
            $batchSize = min($this->studentBatchSize, $missing - $created);

            $students = [];
            $marks = [];

            for ($index = 0; $index < $batchSize; $index++) {
                [$student, $studentMarks] = $this->makeStudent($code + $index, $hashedPassword, $now);

                $students[] = $student;
                $marks[] = $studentMarks;
            }

            DB::table('high_school_students')->insert($students);

            $this->seedStudentSubjects($code, $code + $batchSize - 1, $marks, $now);

            $code += $batchSize;

            unset($students, $marks);

            $progress?->advance($batchSize);
        }

        $progress?->finish();
        $output?->newLine();
    }

    /**
     * First free generated code, so an interrupted run can be resumed.
     */
    private function nextStudentCode(): int
    {
        $lastCode = (int) DB::table('high_school_students')
            ->where('code', '>=', $this->codeBase)
            ->max('code');

        return max($lastCode + 1, $this->codeBase + 1);
    }

    /**
     * Build one student row together with the marks of their subjects.
     *
     * The marks are generated around a single ability value so that a student's
     * subject marks, average and previous years stay coherent.
     *
     * @return array{0: array<string, mixed>, 1: list<array{subject_id: int, grade: int}>}
     */
    private function makeStudent(int $code, string $hashedPassword, Carbon $now): array
    {
        $isScientific = mt_rand(1, 100) <= $this->scientificPercentage;
        $isMale = mt_rand(0, 1) === 1;
        $ability = mt_rand(55, 98);
        $percentage = mt_rand(0, 10);

        $majorType = $isScientific ? self::MAJOR_SCIENTIFIC : self::MAJOR_LITERARY;

        $marks = [];
        $weightedMarks = 0;
        $credits = 0;

        foreach ($this->subjectsByMajorType[$majorType] as $subject) {
            $mark = min(100, max(50, $ability + mt_rand(-8, 8)));

            $marks[] = [
                'subject_id' => $subject['id'],
                'grade' => $mark,
            ];

            $weightedMarks += $mark * $subject['credit_number'];
            $credits += $subject['credit_number'];
        }

        $student = [
            'name' => $this->randomName($isMale),
            'code' => $code,
            'major_type' => $majorType,
            'gender' => $isMale ? 'male' : 'female',
            'is_active' => true,
            'status' => 'submitted',
            'password' => $hashedPassword,
            'grade_average' => round($weightedMarks / $credits, 3),
            'grade_10' => $percentage,
            'grade_11' => $percentage,
            'accepted_department_offering_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [$student, $marks];
    }

    /**
     * Write the student_subjects rows of a freshly inserted chunk.
     *
     * The ids of the chunk are read back with one query on the unique code
     * column; the codes are sequential, so they line up with the generated
     * marks by position.
     *
     * @param  list<list<array{subject_id: int, grade: int}>>  $marks
     */
    private function seedStudentSubjects(int $firstCode, int $lastCode, array $marks, Carbon $now): void
    {
        $studentIds = DB::table('high_school_students')
            ->whereBetween('code', [$firstCode, $lastCode])
            ->orderBy('code')
            ->pluck('id');

        $rows = [];

        foreach ($studentIds as $index => $studentId) {
            foreach ($marks[$index] as $mark) {
                $rows[] = [
                    'student_id' => $studentId,
                    'subject_id' => $mark['subject_id'],
                    'grade' => $mark['grade'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($rows) >= $this->studentSubjectBatchSize) {
                DB::table('student_subjects')->insert($rows);

                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('student_subjects')->insert($rows);
        }
    }

    /**
     * Recalculate grade_average from the marks stored in student_subjects.
     *
     * Subjects are weighted by their credit_number and the result is rounded to
     * the 3 decimals the column stores. One statement covers a whole id range,
     * students without any subject keep their current average.
     *
     * The sums are multiplied by 1.0 first, otherwise dividing two integers
     * truncates the average away on sqlite.
     */
    private function recalculateGradeAverages(): void
    {
        $minId = (int) DB::table('high_school_students')->min('id');
        $maxId = (int) DB::table('high_school_students')->max('id');

        if ($maxId === 0) {
            return;
        }

        $now = now();

        for ($from = $minId; $from <= $maxId; $from += $this->gradeAverageBatchSize) {
            $to = min($maxId, $from + $this->gradeAverageBatchSize - 1);

            DB::update(<<<'SQL'
                update high_school_students
                set grade_average = (
                        select round(
                            (1.0 * sum(student_subjects.grade * subjects.credit_number))
                                / nullif(sum(subjects.credit_number), 0),
                            3
                        )
                        from student_subjects
                        inner join subjects on subjects.id = student_subjects.subject_id
                        where student_subjects.student_id = high_school_students.id
                    ),
                    updated_at = ?
                where high_school_students.id between ? and ?
                    and exists (
                        select 1
                        from student_subjects
                        inner join subjects on subjects.id = student_subjects.subject_id
                        where student_subjects.student_id = high_school_students.id
                    )
                SQL, [$now, $from, $to]);
        }
    }

    /**
     * Build the name pools once instead of calling faker per student.
     */
    private function prepareNamePools(): void
    {
        for ($index = 0; $index < self::NAME_POOL_SIZE; $index++) {
            $this->maleFirstNames[] = $this->faker->firstNameMale();
            $this->femaleFirstNames[] = $this->faker->firstNameFemale();
            $this->lastNames[] = $this->faker->lastName();
        }
    }

    private function randomName(bool $isMale): string
    {
        $firstNames = $isMale ? $this->maleFirstNames : $this->femaleFirstNames;

        return $firstNames[array_rand($firstNames)].' '.$this->lastNames[array_rand($this->lastNames)];
    }
}
