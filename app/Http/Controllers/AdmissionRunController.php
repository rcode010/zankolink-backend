<?php

namespace App\Http\Controllers;

use App\Jobs\RunAdmissionJob;
use App\Models\AcademicYear;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Cache;

class AdmissionRunController extends Controller
{
    use ApiResponses;

    public function store()
    {
        $year = AcademicYear::where('is_active', 1)->firstOrFail();
        $key = RunAdmissionJob::key($year->id);

        $state = Cache::get($key);

        if (in_array($state['status'] ?? null, ['queued', 'running'], true)) {
            return $this->ok('An admission run is already in progress.', $state);
        }

        Cache::put($key, [
            'status'     => 'queued',
            'message'    => 'Waiting for a worker',
            'percent'    => 0,
            'updated_at' => now()->toIso8601String(),
        ], now()->addDay());

        RunAdmissionJob::dispatch($year->id);

        return $this->ok('Admission run queued.', [
            'academic_year_id' => $year->id,
        ]);
    }

    public function show()
    {
        $year = AcademicYear::where('is_active', 1)->firstOrFail();

        $state = Cache::get(RunAdmissionJob::key($year->id), [
            'status'     => 'idle',
            'message'    => null,
            'percent'    => null,
            'updated_at' => null,
        ]);

        return $this->ok('Admission run status.', $state);
    }
}
