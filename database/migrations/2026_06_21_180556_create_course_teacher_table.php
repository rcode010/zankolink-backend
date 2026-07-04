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
        Schema::create('course_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('role', ['primary_lecturer', 'assistant_lecturer', 'lab_instructor']);
            $table->timestamps();

            $table->unique(['teacher_id', 'course_id', 'role'], 'course_teacher_unique');

            $table->index('course_id', 'course_teacher_course_id_idx');
            $table->index(['teacher_id', 'role'], 'course_teacher_teacher_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_teacher');
    }
};
