<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('grade', 5, 2)->nullable();
            $table->enum('status', ['enrolled', 'passed', 'failed', 'withdrawn'])->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->timestamps();

            $table->unique(['course_id', 'student_id', 'academic_year_id'], 'course_student_unique');

            $table->index(['student_id', 'academic_year_id'], 'course_student_student_year_idx');
            $table->index(['course_id', 'academic_year_id'], 'course_student_course_year_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};
