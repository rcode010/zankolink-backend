<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attachment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Letter;
use App\Models\LetterSignature;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\University;
use App\Models\User;
use App\Models\UserScope;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    private Generator $faker;

    private ?AcademicYear $activeAcademicYear = null;
    private string $defaultPassword = 'Password@123';
    private string $hashedDefaultPassword;
    private int $teacherNumber = 1;

    private int $universitiesCount = 3;
    private int $facultiesPerUniversity = 3;
    private int $departmentsPerFaculty = 4;
    private int $teachersPerDepartment = 5;
    private int $studentsPerDepartment = 10;
    private int $coursesPerDepartment = 5;
    private int $lettersCount = 30;

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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->hashedDefaultPassword = Hash::make($this->defaultPassword);
        $this->faker = FakerFactory::create();
        $this->seedRoles();

        $this->call(AcademicYearSeeder::class);
        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $this->seedMinistryUsers();
        $this->seedUniversities();

        $this->seedLetters();
        $this->createMoodleDemoUsers();

//        Artisan::call('zankolink:seed-frontend-users');
    }

    private function seedRoles(): void
    {
        $permissions = [
            // Letter Broadcast
            'view letter broadcast','create letter broadcast',
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
            'view own submission', 'view student submission', 'update student submissions',
            // Academic Request
            'view academic requests', 'view academic request', 'create academic requests',
            // Attendance Sessions
            'view attendance sessions', 'view attendance session', 'create attendance sessions',
            'update attendance sessions', 'delete attendance sessions',
            // Student Attendance
            'create attendance records', 'view attendance records', 'view own attendance records',
            'update attendance records',
            // Course Assessments
            'view course assessments', 'create course assessments', 'update course assessments',
            'view course assessment', 'delete course assessments',
            // Course Marks
            'view own marks',
            // Student Marks
            'view assessment marks', 'create student marks',
            'view student mark', 'update student mark',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            // Ministry
            'MINISTRY_ADMIN' => $permissions,

            'MINISTRY_IMPORT_EXPORT_STAFF' => [
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
                'create stamps', 'view stamps',
                'approve letters', 'decline letters', 'forward letters','view letter broadcast'
            ],

            'MINISTRY_ADMINISTRATION_HEAD' => [
                'view letters', 'update letters', 'raise letters','create letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
                'create stamps', 'view stamps',
                'view reports', 'forward letters','view letter broadcast'
            ],

            // University
            'UNIVERSITY_ADMIN' => [
                'view university', 'view faculties', 'view faculty','create faculties', 'update faculties',
                'view departments', 'view department', 'create departments', 'update departments',
                'view users', 'create users', 'update users', 'activate users', 'deactivate users',
                'view teachers', 'view students', 'view courses', 'view reports',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'upload attachments', 'download attachments',
                'create stamps', 'view stamps',
                'view signatures', 'create signatures', 'forward letters','view letter broadcast'
            ],

            'UNIVERSITY_ADMIN_ADMINISTRATION' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'create signatures','view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast'
            ],

            'UNIVERSITY_ADMIN_STUDENTS' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'create signatures','view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast'
            ],

            'UNIVERSITY_ADMIN_SCIENCE' => [
                'view university', 'view faculties', 'view faculty', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'create signatures','view signatures',
                'create stamps', 'view stamps',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast'
            ],

            // Faculty
            'DEAN' => [
                'view university', 'view faculty', 'view departments', 'create departments', 'update departments',
                'view teachers', 'view teacher', 'create teachers', 'update teachers', 'assign teachers',
                'view students', 'view courses', 'create courses', 'update courses',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'upload attachments', 'download attachments',
                'create stamps', 'view stamps',
                'view signatures','create signatures',
                'view reports','forward letters','view letter broadcast'
            ],

            // Department
            'HEAD_OF_DEPARTMENT' => [
                'view department','view faculty','view university', 'view teachers', 'view teacher', 'assign teachers', 'unassign teachers',
                'view students',
                'view courses', 'create courses', 'update courses',
                'assign course teachers', 'view course teachers',
                'update course teachers', 'delete course teachers',
                'update department seats', 'assign course students',
                'view course students',
                'create stamps', 'view stamps',
                'view signatures','create signatures',
                'update course students', 'delete course students',
                'view letters', 'create letters', 'raise letters','approve letters', 'decline letters',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast',
                'view academic requests', 'view academic request',
            ],

            'lecturer' => [
                'view courses', 'view students',
                'view letters', 'create letters', 'raise letters',
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
            ],

            'student' => [
                'view courses',
                'view letters', 'create letters',
                'upload attachments', 'download attachments',
                'view course sections', 'view course section',
                'view section items', 'view section item',
                'download section attachments', 'view section submissions',
                'view section submission', 'download section submission attachments',
                'create student submissions', 'view own submission',
                'download student submissions', 'delete student submissions',
                'view academic requests', 'view academic request',
                'create academic requests', 'view own attendance records',
                'view own marks',
            ],

            // Zankoline portal
            'HIGH_SCHOOL_GRADUATE' => [
                'view departments',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }
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
            ->create()
            ->each(function (Department $department, int $index) use ($u, $f) {
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
    private function seedTeachers(Department $department): void
    {
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

            DB::table('teacher_department')->updateOrInsert(
                [
                    'teacher_id' => $teacher->id,
                    'department_id' => $department->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->teacherNumber++;
        }
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
            });
    }

    private function seedCourseTeachers(Course $course, Department $department): void
    {
        $teachers = $department->teachers()
            ->inRandomOrder()
            ->limit($this->faker->numberBetween(1, 3))
            ->get();

        foreach ($teachers as $index => $teacher) {
            $roles = ['primary_lecturer', 'assistant_lecturer', 'lab_instructor'];

            DB::table('course_teacher')->updateOrInsert(
                [
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->id,
                    'role' => $roles[$index],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedCourseStudents(Course $course, Department $department): void
    {
        $students = Student::where('department_id', $department->id)
            ->inRandomOrder()
            ->limit($this->faker->numberBetween(8, 10))
            ->get();

        foreach ($students as $student) {
            DB::table('course_student')->updateOrInsert(
                [
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $this->activeAcademicYear->id,
                ],
                [
                    'grade' => $this->faker->optional()->numberBetween(50, 100),
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedLetters(): void
    {
        $users = User::all();

        Letter::factory()
            ->count($this->lettersCount)
            ->make(['academic_year_id' => $this->activeAcademicYear->id])
            ->each(function ($letter) use ($users) {
                $sender = $users->random();
                $receiver = $users->where('id', '!=', $sender->id)->random();

                $letter->original_sender_id = $sender->id;
                $letter->sender_id = $sender->id;
                $letter->receiver_id = $receiver->id;
                $letter->save();

                Attachment::factory()
                    ->count($this->faker->numberBetween(0, 3))
                    ->create(['letter_id' => $letter->id]);

                $signers = $users
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
        $roleId = DB::table('roles')
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
}
