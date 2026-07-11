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
        Schema::create('course_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->enum('type', ['quiz', 'assignment', 'final', 'midterm', 'project', 'activity']);
            $table->decimal('max_mark', 5, 2);
            $table->decimal('weight', 5, 2)->nullable();

            $table->dateTime('due_at')->nullable();
            $table->boolean('is_published')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'academic_year_id']);
            $table->index(['teacher_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_assesments');
    }
};
