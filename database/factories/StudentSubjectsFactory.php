<?php

namespace Database\Factories;

use App\Models\StudentSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentSubjectsFactory extends Factory
{
    protected $model = StudentSubjects::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
