<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attachment;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\DepartmentOffering;
use App\Models\DepartmentOfferingSubject;
use App\Models\Faculty;
use App\Models\HighSchoolStudent;
use App\Models\Letter;
use App\Models\LetterSignature;
use App\Models\SectionItem;
use App\Models\SectionSubmission;
use App\Models\Student;
use App\Models\StudentContactInfo;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\University;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * An offering splits its intake between the zankoline and parallel tracks,
     * and the two capacities must add up to this.
     */
    private const OFFERING_CAPACITY_TOTAL = 100;

    /**
     * Governorates of the department_offerings enum, with their main city.
     */
    private const OFFERING_LOCATIONS = [
        'Erbil' => 'Erbil',
        'Sulaimani' => 'Sulaimani',
        'Duhok' => 'Duhok',
        'Halabja' => 'Halabja',
        'Kirkuk' => 'Kirkuk',
    ];

    /**
     * Departments admitting scientific graduates.
     */
    private const SCIENTIFIC_DEPARTMENTS = [
        'Software Engineering',
        'Computer Science',
        'Information Technology',
        'Civil Engineering',
        'Mechanical Engineering',
        'Architectural Engineering',
        'Medicine and General Surgery',
        'Orthodontics and Dentistry',
        'Clinical Pharmacy',
        'Nursing and Midwifery',
        'Soil and Water Science',
    ];

    /**
     * Departments admitting literary graduates, anything else is scientific.
     */
    private const LITERARY_DEPARTMENTS = [
        'Public Law',
        'Private Law',
        'Political Science',
        'Business Administration',
        'Accounting and Finance',
        'English Translation',
        'Kurdish Literature',
        'Basic Education and Teaching',
        'Fine Arts and Design',
    ];

    private Generator $faker;

    private ?AcademicYear $activeAcademicYear = null;

    private string $defaultPassword = 'Password@123';

    private string $hashedDefaultPassword;

    private int $teacherNumber = 1;

    private int $universitiesCount = 3;

    private int $facultiesPerUniversity = 3;


    private int $departmentsPerFaculty = 6;

    /**
     * Subjects a department scores its applicants on.
     */
    private int $subjectsPerDepartment = 3;

    private int $teachersPerDepartment = 5;

    private int $studentsPerDepartment = 10;

    private int $coursesPerDepartment = 5;

    private int $lettersCount = 5;

    /** @var array<string, int> */
    private array $roleIds = [];

    /** @var array<int, string> Governorate resolved once per faculty. */
    private array $facultyGovernorates = [];

    private int $departmentNumber = 0;

    private int $bulkInsertSize = 1000;

    private function userCode(int $number): string
    {
        return str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    private function positionCode(int $universityNumber, int $facultyNumber = 0, int $departmentNumber = 0): string
    {
        return "{$universityNumber}{$facultyNumber}{$departmentNumber}";
    }

    private function emailNumber(int $number): string
    {
        return str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        DB::disableQueryLog();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->hashedDefaultPassword = Hash::make($this->defaultPassword);
        $this->faker = FakerFactory::create('en_US');

        DB::transaction(function (): void {
            $this->seedRoles();

            $this->call(AcademicYearSeeder::class);

            $this->activeAcademicYear = AcademicYear::query()
                ->where('is_active', true)
                ->firstOrFail();

            $this->seedMinistryUsers();
            $this->seedUniversities();
                        $this->seedLetters();
            //            $this->createMoodleDemoUsers();
            //            $this->createQaCourseUsers();
            $this->seedGrade12Subjects();
            $this->seedDepartmentSubjects();
            $this->createTestStudent();
            $this->call(HighSchoolStudentSeeder::class);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Artisan::call('zankolink:seed-frontend-users');
    }

    private function seedGrade12Subjects(): void
    {
        $subjects = [
            ['name' => 'Kurdish', 'credit_number' => 3, 'major_type' => 'general', 'year_level' => 12],
            ['name' => 'English', 'credit_number' => 4, 'major_type' => 'general', 'year_level' => 12],
            ['name' => 'Arabic', 'credit_number' => 3, 'major_type' => 'general', 'year_level' => 12],
            ['name' => 'Mathematics', 'credit_number' => 5, 'major_type' => 'scientific', 'year_level' => 12],
            ['name' => 'Physics', 'credit_number' => 4, 'major_type' => 'scientific', 'year_level' => 12],
            ['name' => 'Chemistry', 'credit_number' => 4, 'major_type' => 'scientific', 'year_level' => 12],
            ['name' => 'Biology', 'credit_number' => 4, 'major_type' => 'scientific', 'year_level' => 12],
            ['name' => 'History', 'credit_number' => 3, 'major_type' => 'literary', 'year_level' => 12],
            ['name' => 'Geography', 'credit_number' => 3, 'major_type' => 'literary', 'year_level' => 12],
            ['name' => 'Economics', 'credit_number' => 3, 'major_type' => 'literary', 'year_level' => 12],
            ['name' => 'Mathematics', 'credit_number' => 3, 'major_type' => 'literary', 'year_level' => 12],

        ];

        $payload = [];

        foreach ($subjects as $subject) {
            $payload[] = [
                'name' => $subject['name'],
                'credit_number' => $subject['credit_number'],
                'major_type' => $subject['major_type'],
                'year_level' => $subject['year_level'],
            ];
        }

        Subject::upsert(
            $payload,
            ['name', 'major_type', 'year_level'],
        );
    }

    /**
     * Give every department the subjects its admission is scored on.
     *
     * Runs once the grade 12 subjects exist, so it covers the departments of
     * earlier runs too. The subjects are taken from the major the department
     * admits, otherwise a department would be scored on a subject none of its
     * applicants ever sat.
     */
    private function seedDepartmentSubjects(): void
    {
        $subjectsByMajorType = [];

        $subjects = Subject::query()
            ->where('year_level', 12)
            ->whereIn('major_type', ['scientific', 'literary'])
            ->orderBy('id')
            ->get(['id', 'major_type', 'credit_number']);

        foreach ($subjects as $subject) {
            $subjectsByMajorType[$subject->major_type][] = $subject;
        }

        $rows = [];

        foreach (Department::query()->orderBy('id')->get(['id', 'name']) as $department) {
            $pool = $subjectsByMajorType[$this->departmentMajorType($department->name)] ?? [];

            if (count($pool) < $this->subjectsPerDepartment) {
                continue;
            }

            for ($index = 0; $index < $this->subjectsPerDepartment; $index++) {
                // Rotating on the department id keeps the picks stable across
                // runs, so the upsert updates its rows instead of adding more.
                $subject = $pool[($department->id + $index) % count($pool)];

                $rows[] = [
                    'department_id' => $department->id,
                    'subject_id' => $subject->id,
                    'credit' => $subject->credit_number,
                    'minimum_grade' => $this->faker->numberBetween(50, 85),
                ];
            }
        }

        if ($rows === []) {
            return;
        }

        DepartmentOfferingSubject::upsert(
            $rows,
            ['department_id', 'subject_id'],
            ['credit', 'minimum_grade'],
        );
    }

    private function departmentMajorType(string $name): string
    {
        return in_array($name, self::LITERARY_DEPARTMENTS, true)
            ? 'literary'
            : 'scientific';
    }

    private function createTestStudent(): void
    {
        $percentage = mt_rand(0, 10);

        $student = HighSchoolStudent::updateOrCreate(
            ['code' => 123456],
            [
                'name' => 'student',
                'major_type' => 'scientific',
                'gender' => 'male',
                'is_active' => true,
                'status' => 'draft',
                'password' => 'password',
                'grade_average' => 95.500,
                'grade_10' => $percentage,
                'grade_11' => $percentage,
            ]
        );

        StudentContactInfo::updateOrCreate(
            ['student_id' => $student->id],
            [
                'phone' => '07501234567',
                'email' => 'student@example.com',
                'id_number' => 'A123456789',
                'governorate' => 'Erbil',
                'home_address' => 'Erbil, Iraq',
                'emergency_contact_name' => 'Test Guardian',
                'emergency_contact_phone' => '07507654321',
            ]
        );
        $payload = Subject::query()
            ->select('id')
            ->where('major_type', $student->major_type)
            ->orWhere('major_type', 'general')
            ->get()
            ->map(fn ($subject) => [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'grade' => $this->faker->numberBetween(50, 100),
            ])
            ->toArray();

        StudentSubject::upsert(
            $payload,
            ['student_id', 'subject_id']
        );
    }

    private function seedRoles(): void
    {
        $permissions = [
            // Multi Recipient Letters
            'view multi-recipient letters', 'create multi-recipient letters',
            // Universities
            'view universities', 'view university', 'create universities', 'update universities', 'delete universities',
            // Faculties
            'view faculties', 'view faculty', 'create faculties', 'update faculties', 'delete faculties',
            // Departments
            'view departments', 'view department', 'create departments', 'update departments', 'delete departments', 'update department seats',
            // Users
            'view users', 'view user', 'create users', 'update users', 'delete users', 'activate users', 'deactivate users',
            // Teachers
            'view teachers', 'view teacher', 'create teachers', 'update teachers', 'delete teachers', 'assign teachers', 'unassign teachers',
            // Students
            'view students', 'create students', 'update students', 'delete students',
            // Courses
            'view courses', 'create courses', 'update courses', 'delete courses', 'assign course teachers',
            'view course teachers', 'update course teachers', 'delete course teachers',
            'assign course students', 'view course students', 'update course students', 'delete course students',
            // Letters
            'view letters', 'create letters', 'update letters', 'raise letters', 'approve letters', 'decline letters', 'forward letters',
            // Attachments
            'upload attachments', 'download attachments', 'delete attachments',
            // Signatures
            'view signatures', 'create signatures',
            // Reports
            'view reports',
            // Academic Year
            'update academic year',
            // Letter Stamps
            'create stamps', 'view stamps',
            // Roles & Permissions
            'view roles', 'create roles', 'update roles', 'delete roles',
            'view permissions', 'view user roles', 'create user roles', 'delete user roles',
            // Course Section
            'view course sections', 'view course section', 'create course sections',
            'update course sections', 'delete course sections',
            // Section Item
            'view section items', 'view section item', 'create section items', 'update section items',
            'delete section items', 'download section attachments', 'create section notes',
            // Section Submission
            'view section submissions', 'view section submission', 'create section submissions', 'update section submissions', 'delete section submissions',
            // Section Submission Attachments
            'download section submission attachments', 'delete section submission attachments',
            // Student Submission
            'create student submissions', 'view student submissions', 'download student submissions', 'delete student submissions',
            'view student submission', 'update student submissions', 'view own submission',
            // Academic Request
            'view academic requests', 'view academic request', 'create academic requests', 'update academic request',
            // Attendance Sessions
            'view attendance sessions', 'view attendance session', 'create attendance sessions',
            'update attendance sessions', 'delete attendance sessions',
            // Student Attendance
            'create attendance records', 'view attendance records', 'view own attendance records',
            'update attendance records',
            // Course Assessments
            'view course assessments', 'create course assessments', 'update course assessments',
            'view course assessment', 'delete course assessments', 'publish course assessments',
            // Course Marks
            'view own marks',
            // Student Marks
            'view assessment marks', 'create student marks',
            'view student mark', 'update student mark',
            'view gradebook', 'store gradebook',
            // Course Selection
            'view student course selections', 'create student course selections',
            'update course selection settings', 'close course selections',
            'store student course selections',

        ];

        $now = now();

        Permission::query()->upsert(
            array_map(
                static fn (string $permission): array => [
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $permissions
            ),
            ['name', 'guard_name'],
            ['updated_at']
        );

        $roles = [
            // Ministry
            'MINISTRY_ADMIN' => $permissions,

            'MINISTRY_IMPORT_EXPORT_STAFF' => [
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
                'create stamps', 'view stamps',
                'approve letters', 'decline letters', 'forward letters', 'view multi-recipient letters',
            ],

            'MINISTRY_ADMINISTRATION_HEAD' => [
                'view letters', 'update letters', 'raise letters', 'create letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
                'create stamps', 'view stamps',
                'view reports', 'forward letters', 'view multi-recipient letters',
            ],

            // University
            'UNIVERSITY_ADMIN' => [
                'view university', 'view faculties', 'view faculty', 'create faculties', 'update faculties',
                'view departments', 'view department', 'create departments', 'update departments',
                'view teachers', 'view students', 'view courses', 'view reports',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'upload attachments', 'download attachments',
                'create stamps', 'view stamps',
                'view signatures', 'create signatures', 'forward letters', 'view multi-recipient letters',
            ],

            'UNIVERSITY_ADMIN_ADMINISTRATION' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'create signatures', 'view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters', 'view multi-recipient letters',
            ],

            'UNIVERSITY_ADMIN_STUDENTS' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'create signatures', 'view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters', 'view multi-recipient letters',
            ],

            'UNIVERSITY_ADMIN_SCIENCE' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'create signatures', 'view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters', 'view multi-recipient letters',
            ],
            'UNIVERSITY_ADMIN_IMPORT_EXPORT' => [
                'view university', 'view universities', 'view faculties', 'view faculty', 'forward letters', 'view multi-recipient letters',
                'view letters', 'view stamps', 'view signatures',
            ],

            // Faculty
            'DEAN' => [
                'view university', 'view faculty', 'view departments', 'create departments', 'update departments',
                'view teachers', 'view teacher', 'create teachers', 'update teachers', 'assign teachers',
                'view students', 'view courses', 'create courses', 'update courses',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'upload attachments', 'download attachments',
                'create stamps', 'view stamps',
                'view signatures', 'create signatures',
                'view reports', 'forward letters', 'view multi-recipient letters',
            ],

            // Department
            'HEAD_OF_DEPARTMENT' => [
                'view department', 'view faculty', 'view university', 'view teachers', 'view teacher', 'assign teachers', 'unassign teachers',
                'view students', 'update students', 'create students',
                'view courses', 'create courses', 'update courses', 'delete courses',
                'assign course teachers', 'view course teachers',
                'update course teachers', 'delete course teachers',
                'update department seats', 'assign course students',
                'view course students',
                'create stamps', 'view stamps',
                'view signatures', 'create signatures',
                'update course students', 'delete course students',
                'view letters', 'create letters', 'raise letters', 'approve letters', 'decline letters',
                'upload attachments', 'download attachments', 'forward letters', 'view multi-recipient letters',
                'view academic requests', 'view academic request', 'update academic request',
                'view student course selections', 'update course selection settings',
                'close course selections', 'store student course selections',
                'view gradebook', 'update teachers',
            ],

            'lecturer' => [
                'view courses', 'view students',
                'upload attachments', 'download attachments',
                'view course sections', 'view course section',
                'create course sections', 'update course sections',
                'delete course sections', 'view section items',
                'view section item', 'create section items',
                'update section items', 'delete section items',
                'download section attachments', 'create section notes',
                'view section submissions', 'view section submission', 'create section submissions',
                'update section submissions', 'delete section submissions',
                'download section submission attachments', 'delete section submission attachments',
                'view student submissions', 'update student submissions',
                'view student submission', 'download student submissions', 'view academic requests',
                'view academic request', 'create academic requests',
                'view attendance sessions', 'view attendance session', 'create attendance sessions',
                'update attendance sessions', 'delete attendance sessions',
                'create attendance records', 'view attendance records',
                'update attendance records',
                'view course assessments', 'create course assessments', 'update course assessments',
                'view course assessment', 'delete course assessments',
                'view assessment marks', 'create student marks',
                'view student mark', 'update student mark',
                'view gradebook', 'store gradebook', 'publish course assessments',
            ],

            'student' => [
                'view courses',
                'upload attachments', 'download attachments',
                'view course sections', 'view course section',
                'view section items', 'view section item',
                'download section attachments', 'view section submissions',
                'view section submission', 'download section submission attachments',
                'create student submissions', 'view own submission',
                'download student submissions', 'delete student submissions',
                'view academic requests', 'view academic request',
                'create academic requests', 'view own attendance records',
                'view own marks', 'create student course selections',
            ],

            // Zankoline portal
            'HIGH_SCHOOL_GRADUATE' => [
                'view departments',
            ],
            'REGISTRAR'=>[
                'create students'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        $this->roleIds = Role::query()
            ->where('guard_name', 'web')
            ->pluck('id', 'name')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function createScopedUser(
        string $name,
        string $email,
        string $roleName,
        string $scopeType,
        ?int $scopeId = null,
        ?string $phone = null
    ): User {
        $user = User::updateOrCreate(
            ['email' => strtolower($email)],
            [
                'name' => $name,
                'password' => $this->hashedDefaultPassword,
                'phone' => $phone ?? '07700000000',
                'is_active' => true,
                'is_two_factor_enabled' => false,
            ]
        );

        $user->syncRoles([$roleName]);

        $this->createUserScope(
            $user,
            $roleName,
            $scopeType,
            $scopeId
        );

        return $user;
    }

    private function seedMinistryUsers(): void
    {
        $this->createScopedUser(
            name: 'Ministry Admin',
            email: 'ministry.admin@zankolink.test',
            roleName: 'MINISTRY_ADMIN',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07700000001'
        );

        $this->createScopedUser(
            name: 'Ministry Import Export Staff',
            email: 'ministry.import-export@zankolink.test',
            roleName: 'MINISTRY_IMPORT_EXPORT_STAFF',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07700000002'
        );

        $this->createScopedUser(
            name: 'Ministry Administration Head',
            email: 'ministry.administration-head@zankolink.test',
            roleName: 'MINISTRY_ADMINISTRATION_HEAD',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07700000003'
        );

        $this->createScopedUser(
            name: 'High School Graduate',
            email: 'graduate001@zankolink.test',
            roleName: 'HIGH_SCHOOL_GRADUATE',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07700000004'
        );
    }

    private function seedUniversities(): void
    {
        University::factory()
            ->count($this->universitiesCount)
            ->create(['academic_year_id' => $this->activeAcademicYear->id])
            ->each(function (University $university, int $index) {
                $u = $index + 1;

                $positionCode = $this->positionCode($u, 0, 0);
                $userCode = $this->userCode(1);

                $admin = $this->createScopedUser(
                    name: "University Admin {$u}",
                    email: "university.admin{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'UNIVERSITY_ADMIN',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );

                $this->createScopedUser(
                    name: "University Administration Staff {$u}",
                    email: "university.administration{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'UNIVERSITY_ADMIN_ADMINISTRATION',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );

                $this->createScopedUser(
                    name: "University Students Staff {$u}",
                    email: "university.students{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'UNIVERSITY_ADMIN_STUDENTS',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );

                $this->createScopedUser(
                    name: "University Science Staff {$u}",
                    email: "university.science{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'UNIVERSITY_ADMIN_SCIENCE',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );
                $this->createScopedUser(
                    name: "University Import-Export Staff {$u}",
                    email: "university.import-export{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'UNIVERSITY_ADMIN_IMPORT_EXPORT',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );
                $this->createScopedUser(
                    name: "Registrar University {$u}",
                    email: "registrar{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'REGISTRAR',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id,
                );
                $university->update(['admin_id' => $admin->id]);

                $this->seedFaculties($university, $u);
            });
    }

    private function seedFaculties(University $university, int $u): void
    {
        Faculty::factory()
            ->count($this->facultiesPerUniversity)
            ->for($university)
            ->create()
            ->each(function (Faculty $faculty, int $index) use ($u) {
                $f = $index + 1;

                $positionCode = $this->positionCode($u, $f, 0);
                $userCode = $this->userCode(1);

                $admin = $this->createScopedUser(
                    name: "Dean U{$u} F{$f}",
                    email: "dean{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'DEAN',
                    scopeType: 'FACULTY',
                    scopeId: $faculty->id
                );

                $faculty->update(['admin_id' => $admin->id]);

                $this->seedDepartments($faculty, $u, $f);
            });
    }

    private function seedDepartments(Faculty $faculty, int $u, int $f): void
    {
        Department::factory()
            ->count($this->departmentsPerFaculty)
            ->for($faculty)
            ->sequence(fn (): array => ['name' => $this->nextDepartmentName()])
            ->create()
            ->each(function (Department $department, int $index) use ($u, $f) {
                $this->seedDepartmentOfferings($department);

                $d = $index + 1;

                $positionCode = $this->positionCode($u, $f, $d);
                $userCode = $this->userCode(1);

                $admin = $this->createScopedUser(
                    name: "Head of Department U{$u} F{$f} D{$d}",
                    email: "hod{$userCode}{$positionCode}@zankolink.test",
                    roleName: 'HEAD_OF_DEPARTMENT',
                    scopeType: 'DEPARTMENT',
                    scopeId: $department->id
                );

                $department->update(['admin_id' => $admin->id]);

                $this->seedTeachers($department);
                $this->seedStudents($department, $u, $f, $d);
                $this->seedCourses($department);
            });
    }

    private function nextDepartmentName(): string
    {
        $number = $this->departmentNumber++;

        $names = $number % 2 === 0
            ? self::SCIENTIFIC_DEPARTMENTS
            : self::LITERARY_DEPARTMENTS;

        return $names[intdiv($number, 2) % count($names)];
    }
    private function seedDepartmentOfferings(Department $department): void
    {
        $governorate = $this->facultyGovernorate($department->faculty_id);
        $majorType = $this->departmentMajorType($department->name);

        $zankolineCapacity = $this->faker->numberBetween(40, 80);

        $shared = [
            'department_id'    => $department->id,
            'academic_year_id' => $this->activeAcademicYear->id,
            'governorate'      => $governorate,
            'major_type'       => $majorType,
            'city'             => self::OFFERING_LOCATIONS[$governorate],
            'minimum_grade'    => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        DepartmentOffering::upsert(
            [
                $shared + [
                    'track_type' => 'zankoline',
                    'capacity'   => $zankolineCapacity,
                ],
                $shared + [
                    'track_type' => 'parallel',
                    'capacity'   => self::OFFERING_CAPACITY_TOTAL - $zankolineCapacity,
                ],
            ],
            ['department_id', 'academic_year_id', 'track_type'],
            ['capacity', 'governorate', 'major_type', 'city', 'updated_at'],
        );
    }
    /**
     * Departments of the same university are offered in the same governorate.
     */
    private function facultyGovernorate(int $facultyId): string
    {
        if (! isset($this->facultyGovernorates[$facultyId])) {
            $universityId = (int) DB::table('faculties')
                ->where('id', $facultyId)
                ->value('university_id');

            $governorates = array_keys(self::OFFERING_LOCATIONS);

            $this->facultyGovernorates[$facultyId] = $governorates[$universityId % count($governorates)];
        }

        return $this->facultyGovernorates[$facultyId];
    }

    private function seedTeachers(Department $department): void
    {
        $departmentLinks = [];
        $now = now();

        for ($i = 1; $i <= $this->teachersPerDepartment; $i++) {
            $number = $this->emailNumber($this->teacherNumber);

            $user = $this->createScopedUser(
                name: "Teacher {$number}",
                email: "teacher{$number}@zankolink.test",
                roleName: 'lecturer',
                scopeType: 'DEPARTMENT',
                scopeId: $department->id
            );

            $teacher = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'title' => $this->faker->randomElement(['mr', 'ms', 'dr', 'lecturer']),
                    'speciality' => $this->faker->randomElement([
                        'Software Engineering',
                        'Computer Science',
                        'Artificial Intelligence',
                        'Networks',
                        'Cyber Security',
                        'Database Systems',
                    ]),
                ]
            );

            $departmentLinks[] = [
                'teacher_id' => $teacher->id,
                'department_id' => $department->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->teacherNumber++;
        }

        DB::table('teacher_department')->upsert(
            $departmentLinks,
            ['teacher_id', 'department_id'],
            ['updated_at']
        );
    }

    private function seedStudents(Department $department, int $u, int $f, int $d): void
    {
        $positionCode = $this->positionCode($u, $f, $d);

        for ($i = 1; $i <= $this->studentsPerDepartment; $i++) {
            $studentCode = $this->userCode($i);

            $user = $this->createScopedUser(
                name: "Student {$studentCode}",
                email: "student{$studentCode}{$positionCode}@zankolink.test",
                roleName: 'student',
                scopeType: 'DEPARTMENT',
                scopeId: $department->id
            );

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $department->id,
                    'enrollment_type' => $this->faker->randomElement(['morning', 'parallel', 'evening']),
                    'student_number' => "ST{$studentCode}{$positionCode}",
                    'stage' => $this->faker->numberBetween(1, 4),
                    'status' => 'active',
                ]
            );
        }
    }

    private function seedCourses(Department $department): void
    {
        Course::factory()
            ->count($this->coursesPerDepartment)
            ->for($department)
            ->create()
            ->each(function (Course $course) use ($department) {
                $this->seedCourseTeachers($course, $department);
                $this->seedCourseStudents($course, $department);

                $this->seedCourseAttendance($course);
                $this->seedCourseSection($course);
            });
    }

    private function seedCourseTeachers(Course $course, Department $department): void
    {
        $teachers = $department->teachers()
            ->inRandomOrder()
            ->limit($this->faker->numberBetween(1, 3))
            ->get(['teachers.id']);

        if ($teachers->isEmpty()) {
            return;
        }

        $roles = ['primary_lecturer', 'assistant_lecturer', 'lab_instructor'];
        $now = now();

        $rows = $teachers
            ->values()
            ->map(static fn (Teacher $teacher, int $index): array => [
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'role' => $roles[$index],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('course_teacher')->insert($rows);
    }

    private function seedCourseStudents(Course $course, Department $department): void
    {
        $students = Student::query()
            ->where('department_id', $department->id)
            ->inRandomOrder()
            ->limit($this->faker->numberBetween(8, 10))
            ->get(['id']);

        if ($students->isEmpty()) {
            return;
        }

        $now = now();

        $rows = $students
            ->map(fn (Student $student): array => [
                'course_id' => $course->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->activeAcademicYear->id,
                'grade' => $this->faker->optional()->numberBetween(50, 100),
                'status' => 'enrolled',
                'enrolled_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('course_student')->upsert(
            $rows,
            ['course_id', 'student_id', 'academic_year_id'],
            ['grade', 'status', 'enrolled_at', 'updated_at']
        );
    }

    private function seedLetters(): void
    {
        $allowedSenders = User::permission('create letters')->get();

        Letter::factory()
            ->count($this->lettersCount)
            ->make(['academic_year_id' => $this->activeAcademicYear->id])
            ->each(function ($letter) use ($allowedSenders) {
                $sender = $allowedSenders->random();
                $receiver = $allowedSenders->where('id', '!=', $sender->id)->random();

                $letter->original_sender_id = $sender->id;
                $letter->sender_id = $sender->id;
                $letter->receiver_id = $receiver->id;
                $letter->save();

                Attachment::factory()
                    ->count($this->faker->numberBetween(0, 3))
                    ->create(['letter_id' => $letter->id]);

                $signers = $allowedSenders
                    ->where('id', '!=', $sender->id)
                    ->shuffle()
                    ->take($this->faker->numberBetween(1, 2));

                foreach ($signers as $signer) {
                    LetterSignature::factory()->create([
                        'letter_id' => $letter->id,
                        'user_id' => $signer->id,
                    ]);
                }
            });
    }

    private function createMoodleDemoUsers(): void
    {
        $department = Department::first();

        $academicYearId = DB::table('academic_years')
            ->where('is_active', true)
            ->value('id');

        if (! $department || ! $academicYearId) {
            return;
        }

        $courses = Course::query()
            ->whereNotNull('department_id')
            ->take(3)
            ->get();

        if ($courses->isEmpty()) {
            return;
        }

        // Teachers

        $teacherUsersData = [
            [
                'name' => 'Demo Teacher One',
                'email' => 'teacher@zankolink.test',
                'phone' => '07700000001',
                'title' => 'mr',
                'speciality' => 'Software Engineering',
            ],
            [
                'name' => 'Demo Teacher Two',
                'email' => 'teacher2@zankolink.test',
                'phone' => '07700000002',
                'title' => 'dr',
                'speciality' => 'Computer Networks',
            ],
        ];

        $teachers = collect();

        foreach ($teacherUsersData as $teacherData) {
            $teacherUser = User::updateOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'password' => $this->hashedDefaultPassword,
                    'phone' => $teacherData['phone'],
                    'is_active' => true,
                ]
            );

            $teacherUser->syncRoles(['lecturer']);

            $teacher = Teacher::updateOrCreate(
                ['user_id' => $teacherUser->id],
                [
                    'title' => $teacherData['title'],
                    'speciality' => $teacherData['speciality'],
                ]
            );

            $departmentIds = $courses
                ->pluck('department_id')
                ->unique();

            foreach ($departmentIds as $departmentId) {
                DB::table('teacher_department')->updateOrInsert(
                    [
                        'teacher_id' => $teacher->id,
                        'department_id' => $departmentId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $this->createUserScope(
                    $teacherUser,
                    'lecturer',
                    'DEPARTMENT',
                    $departmentId
                );
            }

            $teachers->push($teacher);
        }

        // Assign Teachers To Multiple Courses

        foreach ($courses as $course) {
            foreach ($teachers as $teacher) {
                DB::table('course_teacher')->updateOrInsert(
                    [
                        'course_id' => $course->id,
                        'teacher_id' => $teacher->id,
                    ],
                    [
                        'role' => 'primary_lecturer',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Students

        $studentUsersData = [
            [
                'name' => 'Demo Student One',
                'email' => 'student@zankolink.test',
                'phone' => '07700000003',
                'student_number' => 'ST75585',
            ],
            [
                'name' => 'Demo Student Two',
                'email' => 'student2@zankolink.test',
                'phone' => '07700000004',
                'student_number' => 'ST75586',
            ],
        ];

        $students = collect();

        foreach ($studentUsersData as $studentData) {
            $studentUser = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => 'Password@123',
                    'phone' => $studentData['phone'],
                    'is_active' => true,
                ]
            );

            $studentUser->syncRoles(['student']);

            $student = Student::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'department_id' => $department->id,
                    'enrollment_type' => 'morning',
                    'student_number' => $studentData['student_number'],
                    'stage' => 3,
                    'status' => 'active',
                ]
            );

            $this->createUserScope(
                $studentUser,
                'student',
                'DEPARTMENT',
                $department->id
            );

            $students->push($student);
        }

        //  Enroll Students Into Multiple Courses

        foreach ($courses as $course) {
            foreach ($students as $student) {
                DB::table('course_student')->updateOrInsert(
                    [
                        'course_id' => $course->id,
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYearId,
                    ],
                    [
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function createUserScope(User $user, string $roleName, string $scopeType, ?int $scopeId): void
    {
        $roleId = $this->roleIds[$roleName]
            ??= (int) Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

        if (! $roleId) {
            return;
        }

        DB::table('user_scopes')->updateOrInsert(
            [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedCourseSection(Course $course): void
    {
        $teachers = $course->teachers()
            ->get(['teachers.id']);

        $students = $course->students()
            ->wherePivot(
                'academic_year_id',
                $this->activeAcademicYear->id
            )
            ->get(['students.id']);

        if ($teachers->isEmpty() || $students->isEmpty()) {
            return;
        }

        $primaryTeacher = $course->teachers()
            ->wherePivot('role', 'primary_lecturer')
            ->first();

        if (! $primaryTeacher) {
            return;
        }

        $sections = collect();

        for ($i = 1; $i <= 4; $i++) {
            $section = CourseSection::factory()->create([
                'course_id' => $course->id,
                'teacher_id' => $primaryTeacher->id,
                'title' => "Section {$i}",
            ]);

            $itemCreator = $teachers->random();

            $this->seedSectionItems(
                $section,
                $itemCreator
            );

            $sections->push($section);
        }

        /*
         * Prepare all planned submissions before calculating weights.
         *
         * Store both the section and the teacher who creates
         * each submission.
         */
        $submissionPlan = collect();

        foreach ($sections as $section) {
            $submissionCount = $this->faker->numberBetween(1, 3);

            for ($i = 0; $i < $submissionCount; $i++) {
                $submissionPlan->push([
                    'section' => $section,
                    'teacher' => $teachers->random(),
                ]);
            }
        }

        $totalSubmissions = $submissionPlan->count();

        if ($totalSubmissions === 0) {
            return;
        }

        $baseWeight = intdiv(100, $totalSubmissions);
        $remainder = 100 % $totalSubmissions;

        $assessments = collect();

        foreach ($submissionPlan->values() as $index => $plan) {
            $weight = $baseWeight
                + ($index < $remainder ? 1 : 0);

            $assessment = $this->seedSectionSubmission(
                $plan['section'],
                $weight,
                $plan['teacher']
            );

            $assessments->push($assessment);
        }

        $this->seedStudentMarks(
            $assessments,
            $students
        );
    }

    private function seedSectionItems(CourseSection $section, Teacher $teacher): void
    {
        $now = now();

        $rows = collect(
            SectionItem::factory()
                ->count(3)
                ->raw([
                    'section_id' => $section->id,
                    'created_by_teacher_id' => $teacher->id,
                ])
        )
            ->map(static fn (array $row): array => [
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('section_items')->insert($rows);
    }

    private function seedSectionSubmission(CourseSection $section, int $weight, Teacher $teacher): CourseAssessments
    {
        $assessment = CourseAssessments::factory()->create([
            'course_id' => $section->course_id,
            'teacher_id' => $teacher->id,
            'academic_year_id' => $this->activeAcademicYear->id,
            'weight' => $weight,
        ]);

        SectionSubmission::factory()->create([
            'course_section_id' => $section->id,
            'course_assessment_id' => $assessment->id,
            'created_by_teacher_id' => $teacher->id,
        ]);

        return $assessment;
    }

    private function seedStudentMarks(Collection $assessments, Collection $students): void
    {
        if ($assessments->isEmpty() || $students->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($assessments as $assessment) {
            foreach ($students as $student) {
                $status = $this->faker->randomElement([
                    'valid',
                    'valid',
                    'valid',
                    'absent',
                    'excused',
                    'under_review',
                ]);

                $rows[] = [
                    'course_assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                    'mark' => $status === 'valid'
                        ? $this->faker->randomFloat(
                            2,
                            0,
                            (float) $assessment->max_mark
                        )
                        : null,
                    'feedback' => null,
                    'graded_by' => $assessment->teacher_id,
                    'graded_at' => $now,
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, $this->bulkInsertSize) as $chunk) {
            DB::table('student_marks')->insert($chunk);
        }
    }

    private function seedCourseAttendance(Course $course): void
    {
        $teacherId = DB::table('course_teacher')
            ->where('course_id', $course->id)
            ->value('teacher_id');

        $studentIds = DB::table('course_student')
            ->where('course_id', $course->id)
            ->where(
                'academic_year_id',
                $this->activeAcademicYear->id
            )
            ->pluck('student_id');

        if (! $teacherId || $studentIds->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= 2; $i++) {
            // Creates two recent sessions: one week ago and two weeks ago.
            $sessionDate = now()
                ->subWeeks($i)
                ->startOfDay();

            $startHour = $this->faker->randomElement([
                8,
                10,
                12,
                14,
            ]);

            $startAt = $sessionDate
                ->copy()
                ->setTime($startHour, 0);

            $endAt = $startAt
                ->copy()
                ->addMinutes(90);

            $isHoliday = $this->faker->boolean(5);

            $sessionId = DB::table(
                'course_attendance_sessions'
            )->insertGetId([
                'course_id' => $course->id,
                'academic_year_id' => $this->activeAcademicYear->id,
                'teacher_id' => $teacherId,
                'session_date' => $sessionDate->toDateString(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'title' => $isHoliday
                    ? 'Holiday'
                    : "Attendance Session {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->seedAttendanceRecords(
                $sessionId,
                $studentIds,
                $isHoliday
            );
        }
    }

    private function seedAttendanceRecords(
        int $sessionId,
        Collection $studentIds,
        bool $isHoliday
    ): void {
        $now = now();
        $rows = [];

        foreach ($studentIds as $studentId) {
            $status = $isHoliday
                ? 'Holiday'
                : $this->faker->randomElement([
                    // Repeated values make Present more common.
                    'Present',
                    'Present',
                    'Present',
                    'Present',
                    'Present',
                    'Present',
                    'Late',
                    'Late',
                    'Absent',
                    'Excused Absence',
                ]);

            $rows[] = [
                'attendance_session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status,
                'note' => match ($status) {
                    'Late' => 'Student arrived late.',
                    'Absent' => $this->faker->optional(0.3)->sentence(),
                    'Excused Absence' => 'Absence was excused.',
                    'Holiday' => 'No class was held.',
                    default => null,
                },
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (
            array_chunk($rows, $this->bulkInsertSize) as $chunk
        ) {
            DB::table('student_attendances')
                ->insert($chunk);
        }
    }

    private function createQaCourseUsers(): void
    {
        $department = Department::query()
            ->where('name', 'Software Engineering')
            ->first()
            ?? Department::query()->firstOrFail();

        $academicYearId = $this->activeAcademicYear->id;
        $now = now();

        $course = Course::query()->updateOrCreate(
            [
                'code' => 'QA-SWE-001',
            ],
            [
                'department_id' => $department->id,
                'name' => 'QA Software Engineering Course',
                'credit_hours' => 3,
                'year_level' => 3,
                'semester' => 'fall',
                'type' => 'mandatory',
                'is_active' => true,
            ]
        );

        $teacherData = [
            [
                'name' => 'QA Primary Lecturer',
                'email' => 'qa.primary@zankolink.test',
                'phone' => '07500000001',
                'title' => 'dr',
                'speciality' => 'Software Engineering',
                'course_role' => 'primary_lecturer',
            ],
            [
                'name' => 'QA Assistant Lecturer',
                'email' => 'qa.assistant@zankolink.test',
                'phone' => '07500000002',
                'title' => 'lecturer',
                'speciality' => 'Software Engineering',
                'course_role' => 'assistant_lecturer',
            ],
            [
                'name' => 'QA Lab Instructor',
                'email' => 'qa.lab@zankolink.test',
                'phone' => '07500000003',
                'title' => 'lecturer',
                'speciality' => 'Software Engineering',
                'course_role' => 'lab_instructor',
            ],
        ];

        foreach ($teacherData as $data) {
            $user = $this->createScopedUser(
                name: $data['name'],
                email: $data['email'],
                roleName: 'lecturer',
                scopeType: 'DEPARTMENT',
                scopeId: $department->id,
                phone: $data['phone']
            );

            $teacher = Teacher::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'title' => $data['title'],
                    'speciality' => $data['speciality'],
                ]
            );

            DB::table('teacher_department')->updateOrInsert(
                [
                    'teacher_id' => $teacher->id,
                    'department_id' => $department->id,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('course_teacher')->updateOrInsert(
                [
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->id,
                ],
                [
                    'role' => $data['course_role'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $studentData = [
            [
                'name' => 'QA Student One',
                'email' => 'qa.student1@zankolink.test',
                'phone' => '07500000011',
                'student_number' => 'QA-ST-001',
            ],
            [
                'name' => 'QA Student Two',
                'email' => 'qa.student2@zankolink.test',
                'phone' => '07500000012',
                'student_number' => 'QA-ST-002',
            ],
            [
                'name' => 'QA Student Three',
                'email' => 'qa.student3@zankolink.test',
                'phone' => '07500000013',
                'student_number' => 'QA-ST-003',
            ],
        ];

        foreach ($studentData as $data) {
            $user = $this->createScopedUser(
                name: $data['name'],
                email: $data['email'],
                roleName: 'student',
                scopeType: 'DEPARTMENT',
                scopeId: $department->id,
                phone: $data['phone']
            );

            $student = Student::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'department_id' => $department->id,
                    'enrollment_type' => 'morning',
                    'student_number' => $data['student_number'],
                    'stage' => 3,
                    'status' => 'active',
                ]
            );

            DB::table('course_student')->updateOrInsert(
                [
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'status' => 'enrolled',
                    'enrolled_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
