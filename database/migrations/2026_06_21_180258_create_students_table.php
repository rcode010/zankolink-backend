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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('enrollment_type', ['morning', 'parallel', 'evening']);
            $table->text('student_number');
            $table->unsignedTinyInteger('stage');
            $table->enum('status', ['active', 'inactive', 'on_leave', 'suspended', 'graduated'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status', 'deleted_at'], 'students_department_status_deleted_idx');
            $table->index(['department_id', 'stage'], 'students_department_stage_idx');
            $table->index('user_id', 'students_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
