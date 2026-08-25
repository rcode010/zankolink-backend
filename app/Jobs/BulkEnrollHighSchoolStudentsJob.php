<?php

namespace App\Jobs;

use App\Mail\StudentAccountSetupMail;
use App\Models\HighSchoolStudent;
use App\Models\Student;
use App\Models\StudentAccountSetupToken;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class BulkEnrollHighSchoolStudentsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $highSchoolStudents;

    protected int $registrarUniversityId;

    public function __construct(array $highSchoolStudents, int $registrarUniversityId)
    {
        $this->highSchoolStudents = $highSchoolStudents;
        $this->registrarUniversityId = $registrarUniversityId;
    }

    public function handle(): void
    {
        $studentIds = array_column($this->highSchoolStudents, 'student_id');

        $students = HighSchoolStudent::with([
            'acceptedDepartmentOffering.department.faculty.university',
        ])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $tokens = DB::transaction(function () use ($students) {
            $tokensData = [];
            $generatedTokens = [];

            foreach ($this->highSchoolStudents as $student) {
                $highSchoolStudent = $students->get($student['student_id']);

                $studentUniversityId = $highSchoolStudent
                    ->acceptedDepartmentOffering
                    ->department
                    ->faculty
                    ->university_id;

                if ($studentUniversityId != $this->registrarUniversityId) {
                    throw new \Exception("Student {$highSchoolStudent->id} does not belong to your university.");
                }

                $highSchoolStudent->update(['status' => 'enrolled']);

                $user = User::create([
                    'email' => $student['email'],
                    'name' => $highSchoolStudent->name,
                    'phone' => $student['phone'],
                    'password' => Hash::make('koya2026'),
                ]);

                $user->assignRole('student');

                Student::create([
                    'user_id' => $user->id,
                    'department_id' => $student['department_id'],
                    'stage' => 1,
                    'enrollment_type' => $student['enrollment_type'],
                ]);

                $token = bin2hex(random_bytes(32));
                $expiresAt = now()->addMinutes(15);

                $tokensData[] = [
                    'user_id' => $user->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $generatedTokens[] = [
                    'email' => $student['email'],
                    'name' => $highSchoolStudent->name,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                ];
            }

            if (! empty($tokensData)) {
                StudentAccountSetupToken::insert($tokensData);
            }

            return $generatedTokens;
        });

        foreach ($tokens as $token) {
            $setupUrl = config('app.student_setup_url.url').'?token='.$token['token'];
            Mail::to($token['email'])->queue(new StudentAccountSetupMail(
                username: $token['name'],
                setupUrl: $setupUrl,
                expiresAt: $token['expires_at']->format('F j, Y \a\t g:i A'),
            ));
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Bulk enrollment failed', ['error' => $exception->getMessage()]);
    }
}
