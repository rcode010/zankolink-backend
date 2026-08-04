<?php

namespace Database\Factories;

use App\Models\HighSchoolStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class HighSchoolStudentsFactory extends Factory
{
    protected $model = HighSchoolStudents::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
