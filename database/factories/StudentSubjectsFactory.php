<?php

namespace Database\Factories;

use App\Models\StudentSubject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentSubjectsFactory extends Factory
{
    protected $model = StudentSubject::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
