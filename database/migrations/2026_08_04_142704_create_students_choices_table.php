<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students_choices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('high_school_students')
                ->cascadeOnDelete();

            $table->foreignId('department_offering_id')
                ->constrained('department_offerings')
                ->cascadeOnDelete();

            $table->decimal('score', 6, 3);

            $table->boolean('is_local');

            $table->unsignedTinyInteger('preference_order');

            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
            ])->default('pending');

            $table->timestamps();

            $table->unique(
                ['student_id', 'preference_order'],
                'student_choice_order_unique'
            );

            $table->unique(
                ['student_id', 'department_offering_id'],
                'student_choice_department_unique'
            );

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students_choices');
    }
};
