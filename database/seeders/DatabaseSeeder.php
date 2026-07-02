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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    private Generator $faker;

    private ?AcademicYear $activeAcademicYear = null;

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->faker = FakerFactory::create();
        $this->seedRoles();

        $this->call(AcademicYearSeeder::class);
        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $this->seedMinistryAdmin();
        $this->seedUniversities();

        $this->seedLetters();

        Artisan::call('zankolink:seed-frontend-users');
    }

    private function seedRoles(): void
    {
        $permissions = [
            // Letter Broadcast
            'view letter broadcast','create letter broadcast',
            // Universities
            'view universities', 'create universities', 'update universities', 'delete universities',
            // Faculties
            'view faculties', 'create faculties', 'update faculties', 'delete faculties',
            // Departments
            'view departments', 'create departments', 'update departments', 'delete departments', 'update department seats',
            // Users
            'view users', 'create users', 'update users', 'delete users', 'activate users', 'deactivate users',
            // Teachers
            'view teachers', 'create teachers', 'update teachers', 'delete teachers', 'assign teachers',
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
            //Roles & Permissions
            'view roles', 'create roles', 'update roles', 'delete roles',
            'view permissions', 'view user roles', 'create user roles', 'delete user roles',
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
                'view letters', 'update letters', 'raise letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
                'view reports','forward letters','view letter broadcast'
            ],

            // University
            'UNIVERSITY_ADMIN' => [
                'view faculties', 'create faculties', 'update faculties',
                'view departments', 'create departments', 'update departments',
                'view users', 'create users', 'update users', 'activate users', 'deactivate users',
                'view teachers', 'view students', 'view courses', 'view reports',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments',
                'view signatures', 'create signatures', 'forward letters','view letter broadcast'
            ],

            'UNIVERSITY_ADMIN_ADMINISTRATION' => [
                'view faculties', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments', 'forward letters',
            ],

            'UNIVERSITY_ADMIN_STUDENTS' => [
                'view faculties', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast'
            ],

            'UNIVERSITY_ADMIN_SCIENCE' => [
                'view faculties', 'view departments', 'view users',
                'view teachers', 'view students', 'view courses',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments','forward letters','view letter broadcast'
            ],

            // Faculty
            'DEAN' => [
                'view departments', 'create departments', 'update departments',
                'view teachers', 'create teachers', 'update teachers', 'assign teachers',
                'view students', 'view courses', 'create courses', 'update courses',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments',
                'view reports','forward letters','view letter broadcast'
            ],

            // Department
            'HEAD_OF_DEPARTMENT' => [
                'view teachers', 'assign teachers',
                'view students',
                'view courses', 'create courses', 'update courses',
                'assign course teachers', 'view course teachers',
                'update course teachers', 'delete course teachers',
                'update department seats', 'assign course students',
                'view course students',
                'update course students', 'delete course students',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments', 'forward letters','view letter broadcast'
            ],

            'lecturer' => [
                'view courses', 'view students',
                'view letters', 'create letters', 'raise letters',
                'upload attachments', 'download attachments','view letter broadcast'
            ],

            'student' => [
                'view courses',
                'view letters', 'create letters',
                'upload attachments', 'download attachments',
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
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?? $this->faker->phoneNumber(),
        ]);

        $role = Role::where('name', $roleName)->firstOrFail();
        $user->assignRole($role);

        UserScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);

        return $user;
    }

    private function seedMinistryAdmin(): void
    {
        $this->createScopedUser(
            name: $this->faker->name(),
            email: 'admin@ministry.gov',
            roleName: 'MINISTRY_ADMIN',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07701234567'
        );
    }

    private function seedUniversities(): void
    {
        University::factory()
            ->count(3)
            ->create(['academic_year_id' => $this->activeAcademicYear->id])
            ->each(function (University $university) {
                $admin = $this->createScopedUser(
                    name: $this->faker->name(),
                    email: "university-admin-{$university->id}@test.com",
                    roleName: 'UNIVERSITY_ADMIN',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );

                $university->update(['admin_id' => $admin->id]);

                $this->seedFaculties($university);
            });
    }

    private function seedFaculties(University $university): void
    {
        Faculty::factory()
            ->count(2)
            ->for($university)
            ->create()
            ->each(function (Faculty $faculty) {
                $admin = $this->createScopedUser(
                    name: $this->faker->name(),
                    email: "faculty-dean-{$faculty->id}@test.com",
                    roleName: 'DEAN',
                    scopeType: 'FACULTY',
                    scopeId: $faculty->id
                );

                $faculty->update(['admin_id' => $admin->id]);

                $this->seedDepartments($faculty);
            });
    }

    private function seedDepartments(Faculty $faculty): void
    {
        Department::factory()
            ->count(3)
            ->for($faculty)
            ->create()
            ->each(function (Department $department) {
                $admin = $this->createScopedUser(
                    name: $this->faker->name(),
                    email: "department-head-{$department->id}@test.com",
                    roleName: 'HEAD_OF_DEPARTMENT',
                    scopeType: 'DEPARTMENT',
                    scopeId: $department->id
                );

                $department->update(['admin_id' => $admin->id]);

                $this->seedTeachers($department);
                $this->seedStudents($department);
                $this->seedCourses($department);
            });
    }

    private function seedTeachers(Department $department): void
    {
        Teacher::factory()
            ->count(5)
            ->create()
            ->each(function (Teacher $teacher) use ($department) {
                $teacher->user->assignRole('lecturer');

                UserScope::create([
                    'user_id' => $teacher->user_id,
                    'role_id' => Role::where('name', 'lecturer')->firstOrFail()->id,
                    'scope_type' => 'DEPARTMENT',
                    'scope_id' => $department->id,
                ]);

                $department->teachers()->attach($teacher);
            });
    }

    private function seedStudents(Department $department): void
    {
        Student::factory()
            ->count(20)
            ->create(['department_id' => $department->id])
            ->each(function (Student $student) use ($department) {
                $student->user->assignRole('student');

                UserScope::create([
                    'user_id' => $student->user_id,
                    'role_id' => Role::where('name', 'student')->firstOrFail()->id,
                    'scope_type' => 'DEPARTMENT',
                    'scope_id' => $department->id,
                ]);
            });
    }

    private function seedCourses(Department $department): void
    {
        Course::factory()
            ->count(5)
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

            $course->teachers()->attach($teacher->id, ['role' => $roles[$index]]);
        }
    }

    private function seedCourseStudents(Course $course, Department $department): void
    {
        $students = Student::where('department_id', $department->id)
            ->inRandomOrder()
            ->limit($this->faker->numberBetween(8, 15))
            ->get();

        foreach ($students as $student) {
            $course->students()->attach($student->id, [
                'academic_year_id' => $this->activeAcademicYear->id,
                'grade' => $this->faker->optional()->numberBetween(50, 100),
                'enrolled_at' => now(),
            ]);
        }
    }

    private function seedLetters(): void
    {
        $users = User::all();

        Letter::factory()
            ->count(30)
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
}
