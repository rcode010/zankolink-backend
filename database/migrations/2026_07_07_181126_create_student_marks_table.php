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
        Schema::create('student_marks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_assessment_id')
                ->constrained('course_assessments')
                ->cascadeOnDelete();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->decimal('mark', 5, 2)->nullable();
            $table->text('feedback')->nullable();

            $table->foreignId('graded_by')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->enum('status', ['valid', 'voided', 'excused', 'absent', 'under_review'])->default('valid');
            $table->timestamps();

            $table->unique(['course_assessment_id', 'student_id'], 'student_mark_unique');
            $table->index(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_marks');
    }
};
