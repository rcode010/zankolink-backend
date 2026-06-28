<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedRoles();

        // لێرەدا یەکەمجار ساڵی ئەکادیمی بانگ دەدەین تاوەکو زانکۆکان ئێرەر نەدەن
        $this->call(AcademicYearSeeder::class);

        $this->seedMinistryAdmin();
        $this->seedUniversities();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function seedRoles(): void
    {
        $permissions = [
            // Universities
            'view universities', 'create universities', 'update universities', 'delete universities',
            // Faculties
            'view faculties', 'create faculties', 'update faculties', 'delete faculties',
            // Departments
            'view departments', 'create departments', 'update departments', 'delete departments',
            // Users
            'view users', 'create users', 'update users', 'delete users', 'activate users', 'deactivate users',
            // Teachers
            'view teachers', 'create teachers', 'update teachers', 'delete teachers', 'assign teachers',
            // Students
            'view students', 'create students', 'update students', 'delete students', 'assign students',
            // Courses
            'view courses', 'create courses', 'update courses', 'delete courses', 'assign course teachers', 'assign course students',
            // Letters
            'view letters', 'create letters', 'update letters', 'raise letters',
            // Attachments
            'upload attachments', 'download attachments', 'delete attachments',
            // Signatures
            'view signatures', 'create signatures',
            // Reports
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'MINISTRY_ADMIN' => $permissions,
            'MINISTRY_STAFF' => [
                'view universities', 'view faculties', 'view departments', 'view users', 'view reports',
                'view letters', 'create letters', 'raise letters', 'upload attachments', 'download attachments',
                'view signatures', 'create signatures',
            ],
            'UNIVERSITY_ADMIN' => [
                'view faculties', 'create faculties', 'update faculties', 'view departments', 'create departments',
                'update departments', 'view users', 'create users', 'update users', 'activate users', 'deactivate users',
                'view teachers', 'view students', 'view courses', 'view reports', 'view letters', 'create letters',
                'raise letters', 'upload attachments', 'download attachments', 'view signatures', 'create signatures',
            ],
            'UNIVERSITY_STAFF' => [
                'view faculties', 'view departments', 'view users', 'view teachers', 'view students',
                'view courses', 'view letters', 'create letters', 'raise letters', 'upload attachments', 'download attachments',
            ],
            'DEAN' => [
                'view departments', 'create departments', 'update departments', 'view teachers', 'create teachers',
                'update teachers', 'assign teachers', 'view students', 'view courses', 'create courses', 'update courses',
                'view letters', 'create letters', 'raise letters', 'upload attachments', 'download attachments', 'view reports',
            ],
            'DEPARTMENT_HEAD' => [
                'view teachers', 'assign teachers', 'view students', 'assign students', 'view courses', 'create courses',
                'update courses', 'assign course teachers', 'assign course students', 'view letters', 'create letters',
                'raise letters', 'upload attachments', 'download attachments',
            ],
            'lecturer' => [
                'view courses', 'view students', 'view letters', 'create letters', 'raise letters', 'upload attachments', 'download attachments',
            ],
            'student' => [
                'view courses', 'view letters', 'create letters', 'upload attachments', 'download attachments',
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
            'phone' => $phone ?? fake()->phoneNumber(),
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
            name: fake()->name(),
            email: 'admin@ministry.gov',
            roleName: 'MINISTRY_ADMIN',
            scopeType: 'MINISTRY',
            scopeId: null,
            phone: '07701234567'
        );
    }

    private function seedUniversities(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        University::factory()
            ->count(3)
            ->create([
                'academic_year_id' => $academicYear->id,
            ])
            ->each(function (University $university) {
                // لێرەدا مێتۆدەکە بە تەواوی چاککراوە و دووبارەبوونەوەکە نەماوە
                $admin = $this->createScopedUser(
                    name: fake()->name(),
                    email: "university-admin-{$university->id}@test.com",
                    roleName: 'UNIVERSITY_ADMIN',
                    scopeType: 'UNIVERSITY',
                    scopeId: $university->id
                );

                $university->update([
                    'admin_id' => $admin->id,
                ]);

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
                    name: fake()->name(),
                    email: "faculty-dean-{$faculty->id}@test.com",
                    roleName: 'DEAN',
                    scopeType: 'FACULTY',
                    scopeId: $faculty->id
                );

                $faculty->update([
                    'admin_id' => $admin->id,
                ]);

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
                    name: fake()->name(),
                    email: "department-head-{$department->id}@test.com",
                    roleName: 'DEPARTMENT_HEAD',
                    scopeType: 'DEPARTMENT',
                    scopeId: $department->id
                );

                $department->update([
                    'admin_id' => $admin->id,
                ]);

                $this->seedCourses($department);
            });
    }

    private function seedCourses(Department $department): void
    {
        Course::factory()
            ->count(5)
            ->for($department)
            ->create();
    }
}