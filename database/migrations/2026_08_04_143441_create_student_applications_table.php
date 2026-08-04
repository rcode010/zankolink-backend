<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('high_school_students')
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            $table->enum('status', [
                'draft',
                'submitted',
                'processing',
                'accepted',
                'rejected',
            ])->default('draft');

            $table->json('draft_choices')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->unique([
                'student_id',
                'academic_year_id',
            ], 'student_application_unique');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
