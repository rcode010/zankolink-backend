<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunAdmissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $academicYearId)
    {
        $this->onQueue('admission');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("admission:{$this->academicYearId}"))
                ->dontRelease()
                ->expireAfter(3600),
        ];
    }

    public function handle(): void
    {
        $this->progress('running', 'Starting', 0);

        // Placeholder — replaced by the allocator once the pipe is proven.
        for ($i = 1; $i <= 10; $i++) {
            sleep(1);
            $this->progress('running', "Cohort {$i} of 10", $i * 10);
        }

        $this->progress('completed', 'Done', 100);
    }

    public function failed(?Throwable $e): void
    {
        $this->progress('failed', $e?->getMessage() ?? 'Unknown error', null);
    }

    private function progress(string $status, string $message, ?int $percent): void
    {
        Cache::put(self::key($this->academicYearId), [
            'status'     => $status,
            'message'    => $message,
            'percent'    => $percent,
            'updated_at' => now()->toIso8601String(),
        ], now()->addDay());
    }

    public static function key(int $academicYearId): string
    {
        return "admission:run:{$academicYearId}";
    }
}
