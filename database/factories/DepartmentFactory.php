<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
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
            'Public Law',
            'Private Law',
            'Political Science',
            'Business Administration',
            'Accounting and Finance',
            'English Translation',
            'Kurdish Literature',
            'Basic Education and Teaching',
            'Soil and Water Science',
            'Fine Arts and Design',
        ];

        return [

            'name' => $this->faker->randomElement($departments),
            'faculty_id' => Faculty::factory(),
            'admin_id' => null,
            'is_active' => true,
            'accepted_gender' => $this->faker->randomElement(['both', 'both', 'both', 'male', 'female']),
        ];
    }
}
