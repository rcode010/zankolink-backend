<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateTestUsersCommand extends Command
{
    protected $signature = 'zankolink:seed-frontend-users
                            {--fresh : Drop and re-create these users if they already exist}';

    protected $description = 'Seed one user per role with known credentials for frontend integration testing';

    private string $defaultPassword = 'Password@123';

    private array $emails = [
        'MINISTRY.ADMIN@zankolink.test',
        'MINISTRY.IMPORT.EXPORT.STAFF@zankolink.test',
        'MINISTRY.ADMINISTRATION.HEAD@zankolink.test',
        'UNIVERSITY.ADMIN@zankolink.test',
        'UNIVERSITY.ADMIN.ADMINISTRATION@zankolink.test',
        'UNIVERSITY.ADMIN.STUDENTS@zankolink.test',
        'UNIVERSITY.ADMIN.SCIENCE@zankolink.test',
        'DEAN@zankolink.test',
        'HEAD.OF.DEPARTMENT@zankolink.test',
        'lecturer@zankolink.test',
        'student@zankolink.test',
        'HIGH.SCHOOL.GRADUATE@zankolink.test',
    ];

    public function handle(): void
    {
        $this->info('');
        $this->info('  ZankoLink — Frontend Integration Users');
        $this->info('  ─────────────────────────────────────');
        $this->info('');

        if ($this->option('fresh')) {
            $this->wipePreviousUsers();
        }

        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            $this->error('  No active academic year found. Run db:seed first.');
            return;
        }

        $rows = [];

        // ── Ministry level ──────────────────────────────────────────────
        $rows[] = $this->makeUser(
            name: 'Ministry Admin',
            email: 'MINISTRY.ADMIN@zankolink.test',
            role: 'MINISTRY_ADMIN',
            scopeType: 'MINISTRY',
            scopeId: null,
        );

        $rows[] = $this->makeUser(
            name: 'Ministry Import Export Staff',
            email: 'MINISTRY.IMPORT.EXPORT.STAFF@zankolink.test',
            role: 'MINISTRY_IMPORT_EXPORT_STAFF',
            scopeType: 'MINISTRY',
            scopeId: null,
        );

        $rows[] = $this->makeUser(
            name: 'Ministry Administration Head',
            email: 'MINISTRY.ADMINISTRATION.HEAD@zankolink.test',
            role: 'MINISTRY_ADMINISTRATION_HEAD',
            scopeType: 'MINISTRY',
            scopeId: null,
        );

        // ── University level ─────────────────────────────────────────────
        $university = University::factory()->create([
            'academic_year_id' => $academicYear->id,
        ]);

        $universityAdmin = $this->makeUser(
            name: 'University Admin',
            email: 'UNIVERSITY.ADMIN@zankolink.test',
            role: 'UNIVERSITY_ADMIN',
            scopeType: 'UNIVERSITY',
            scopeId: $university->id,
        );
        $university->update(['admin_id' => User::where('email', 'UNIVERSITY.ADMIN@zankolink.test')->value('id')]);
        $rows[] = $universityAdmin;

        $rows[] = $this->makeUser(
            name: 'University Admin Administration',
            email: 'UNIVERSITY.ADMIN.ADMINISTRATION@zankolink.test',
            role: 'UNIVERSITY_ADMIN_ADMINISTRATION',
            scopeType: 'UNIVERSITY',
            scopeId: $university->id,
        );

        $rows[] = $this->makeUser(
            name: 'University Admin Students',
            email: 'UNIVERSITY.ADMIN.STUDENTS@zankolink.test',
            role: 'UNIVERSITY_ADMIN_STUDENTS',
            scopeType: 'UNIVERSITY',
            scopeId: $university->id,
        );

        $rows[] = $this->makeUser(
            name: 'University Admin Science',
            email: 'UNIVERSITY.ADMIN.SCIENCE@zankolink.test',
            role: 'UNIVERSITY_ADMIN_SCIENCE',
            scopeType: 'UNIVERSITY',
            scopeId: $university->id,
        );

        // ── Faculty level ────────────────────────────────────────────────
        $faculty = Faculty::factory()->create([
            'university_id' => $university->id,
        ]);

        $dean = $this->makeUser(
            name: 'Dean',
            email: 'DEAN@zankolink.test',
            role: 'DEAN',
            scopeType: 'FACULTY',
            scopeId: $faculty->id,
        );
        $faculty->update(['admin_id' => User::where('email', 'DEAN@zankolink.test')->value('id')]);
        $rows[] = $dean;

        // ── Department level ─────────────────────────────────────────────
        $department = Department::factory()->create([
            'faculty_id' => $faculty->id,
        ]);

        $deptHead = $this->makeUser(
            name: 'Head of Department',
            email: 'HEAD.OF.DEPARTMENT@zankolink.test',
            role: 'HEAD_OF_DEPARTMENT',
            scopeType: 'DEPARTMENT',
            scopeId: $department->id,
        );
        $department->update(['admin_id' => User::where('email', 'HEAD.OF.DEPARTMENT@zankolink.test')->value('id')]);
        $rows[] = $deptHead;

        $rows[] = $this->makeUser(
            name: 'Lecturer',
            email: 'lecturer@zankolink.test',
            role: 'lecturer',
            scopeType: 'DEPARTMENT',
            scopeId: $department->id,
        );

        $rows[] = $this->makeUser(
            name: 'Student',
            email: 'student@zankolink.test',
            role: 'student',
            scopeType: 'DEPARTMENT',
            scopeId: $department->id,
        );

        // ── Zankoline portal ─────────────────────────────────────────────
        $rows[] = $this->makeUser(
            name: 'High School Graduate',
            email: 'HIGH.SCHOOL.GRADUATE@zankolink.test',
            role: 'HIGH_SCHOOL_GRADUATE',
            scopeType: 'MINISTRY',
            scopeId: null,
        );

        // ── Print table ──────────────────────────────────────────────────
        $this->table(
            ['Role', 'Email', 'Password', 'Scope', 'Scope ID'],
            $rows
        );

        $this->info('');
        $this->warn('  ⚠  Do not run this command in production.');
        $this->info('');
    }

    private function makeUser(
        string $name,
        string $email,
        string $role,
        string $scopeType,
        ?int $scopeId,
    ): array {
        if ($this->option('fresh')) {
            User::where('email', $email)->forceDelete();
        }

        if (User::where('email', $email)->exists()) {
            $this->line("  <fg=yellow>SKIP</>  {$email} already exists — use --fresh to recreate.");
            return [$role, $email, $this->defaultPassword, $scopeType, $scopeId ?? 'null'];
        }

        $user = User::create([
            'name'                  => $name,
            'email'                 => $email,
            'password'              => Hash::make($this->defaultPassword),
            'phone'                 => '07700000000',
            'is_active'             => true,
            'is_two_factor_enabled' => false,
        ]);

        $roleModel = Role::where('name', $role)->firstOrFail();
        $user->assignRole($roleModel);

        UserScope::create([
            'user_id'    => $user->id,
            'role_id'    => $roleModel->id,
            'scope_type' => $scopeType,
            'scope_id'   => $scopeId,
        ]);

        $this->line("  <fg=green>OK</>    {$email}");

        return [$role, $email, $this->defaultPassword, $scopeType, $scopeId ?? 'null'];
    }

    private function wipePreviousUsers(): void
    {
        User::whereIn('email', $this->emails)->forceDelete();
        $this->line('  <fg=yellow>Wiped previous frontend users.</>');
    }
}
