<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The year is unique, so creating it outright breaks every re-run.
        AcademicYear::firstOrCreate(
            ['year' => '2026-2027'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-08-31',
                'semester' => 'fall',
                'is_active' => true,
            ]
        );
    }
}
