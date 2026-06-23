<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),

            // Default normal user/student
            'position' => 'DEPARTMENT',
            'phone' => $this->faker->phoneNumber(),
            'role_scope_id' => null,
            'role_scope_type' => 'student',

            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Ministry Admin
     */
    public function ministryAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'MINISTRY',
            'role_scope_id' => null,
            'role_scope_type' => 'MINISTRY_ADMIN',
        ]);
    }

    /**
     * Ministry Staff
     */
    public function ministryStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'MINISTRY',
            'role_scope_id' => null,
            'role_scope_type' => 'MINISTRY_STAFF',
        ]);
    }

    /**
     * University Admin
     */
    public function universityAdmin(int $universityId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'UNIVERSITY',
            'role_scope_id' => $universityId,
            'role_scope_type' => 'UNIVERSITY_ADMIN',
        ]);
    }

    /**
     * University Staff
     */
    public function universityStaff(int $universityId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'UNIVERSITY',
            'role_scope_id' => $universityId,
            'role_scope_type' => 'UNIVERSITY_STAFF',
        ]);
    }

    /**
     * Faculty Dean
     */
    public function dean(int $facultyId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'FACULTY',
            'role_scope_id' => $facultyId,
            'role_scope_type' => 'DEAN',
        ]);
    }

    /**
     * Department Head
     */
    public function departmentHead(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'DEPARTMENT',
            'role_scope_id' => $departmentId,
            'role_scope_type' => 'DEPARTMENT_HEAD',
        ]);
    }

    /**
     * Lecturer
     */
    public function lecturer(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'DEPARTMENT',
            'role_scope_id' => $departmentId,
            'role_scope_type' => 'lecturer',
        ]);
    }

    /**
     * Student
     */
    public function student(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'DEPARTMENT',
            'role_scope_id' => $departmentId,
            'role_scope_type' => 'student',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
