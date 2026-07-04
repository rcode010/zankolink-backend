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
        Schema::create('course_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');

            $table->string('status')->default('pending');

            $table->timestamps();

            $table->unique(['course_id', 'student_id', 'academic_year_id'], 'course_selections_unique');

            $table->index(['student_id', 'academic_year_id', 'status'], 'course_selections_student_year_status_idx');
            $table->index(['academic_year_id', 'status'], 'course_selections_year_status_idx');
            $table->index(['course_id', 'academic_year_id'], 'course_selections_course_year_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_selections');
    }
};
