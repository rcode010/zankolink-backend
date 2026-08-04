<?php

namespace Database\Factories;

use App\Models\StudentContactInfo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentContactInfoFactory extends Factory
{
    protected $model = StudentContactInfo::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
