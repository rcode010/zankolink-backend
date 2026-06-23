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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            //            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'position' => 'student',
            'phone' => $this->faker->phoneNumber(),
            'role_scope_id' => null,
            'role_scope_type' => null,
            'is_active' => true,

            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State helper to create a Ministry Admin (Global Superadmin)
     */
    public function ministryAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'admin',
            'role_scope_id' => null,
            'role_scope_type' => null, // Ministry has global access
        ]);
    }

    /**
     * State helper to create a University Admin
     * * NOTE: If you haven't run the alter migration to add 'university'
     * to your role_scope_type enum yet, this will use 'faculty' as a temporary fallback.
     */
    public function universityAdmin(int $universityId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'admin',
            'role_scope_id' => $universityId,
            'role_scope_type' => 'faculty',
        ]);
    }

    /**
     * State helper to create a Faculty Dean
     */
    public function dean(int $facultyId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'dean',
            'role_scope_id' => $facultyId,
            'role_scope_type' => 'faculty',
        ]);
    }

    /**
     * State helper to create a Department Head
     */
    public function headOfDepartment(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'head_of_department',
            'role_scope_id' => $departmentId,
            'role_scope_type' => 'department',
        ]);
    }

    /**
     * State helper to create a Teacher
     */
    public function teacher(int $scopeId, string $scopeType = 'course'): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'teacher',
            'role_scope_id' => $scopeId,
            'role_scope_type' => $scopeType, // Can be scoped to 'course' or 'department'
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
