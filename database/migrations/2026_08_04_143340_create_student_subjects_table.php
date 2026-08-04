<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('high_school_students')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->decimal('grade', 6, 3);

            $table->timestamps();

            $table->unique([
                'student_id',
                'subject_id',
            ], 'student_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
    }
};
