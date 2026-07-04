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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('type', ['mandatory', 'elective'])->default('mandatory');
            $table->integer('seats')->nullable();
            $table->text('name');
            $table->text('code');
            $table->bigInteger('credit_hours');
            $table->bigInteger('year_level');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active', 'deleted_at'], 'courses_department_active_deleted_idx');
            $table->index(['department_id', 'type', 'is_active'], 'courses_department_type_active_idx');
            $table->index(['department_id', 'year_level'], 'courses_department_year_level_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
