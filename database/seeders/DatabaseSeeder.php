<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMinistryAdmin();
        $this->seedUniversities();
    }

    private function seedMinistryAdmin(): void
    {
        User::factory()
            ->ministryAdmin()
            ->create([
                'name' => 'Ministry Admin',
                'email' => 'admin@ministry.gov',
                'phone' => '07701234567',
            ]);
    }

    private function seedUniversities(): void
    {
        University::factory()
            ->count(3)
            ->create()
            ->each(function (University $university) {
                $admin = User::factory()
                    ->universityAdmin($university->id)
                    ->create();

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
                $admin = User::factory()
                    ->dean($faculty->id)
                    ->create();

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
                $admin = User::factory()
                    ->departmentHead($department->id)
                    ->create();

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
