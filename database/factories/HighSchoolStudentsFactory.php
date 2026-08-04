<?php

namespace Database\Factories;

use App\Models\HighSchoolStudent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class HighSchoolStudentsFactory extends Factory
{
    protected $model = HighSchoolStudent::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
