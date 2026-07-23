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
            $table->enum('semester', ['fall', 'spring'])->default('fall');
            $table->integer('seats')->nullable();
            $table->text('name');
            $table->text('code');
            $table->bigInteger('credit_hours');
            $table->bigInteger('year_level');
            $table->boolean('is_active')->default(true);
            $table->string('color');
            $table->timestamps();

            $table->index(['department_id', 'is_active'], 'courses_department_active_deleted_idx');
            $table->index(['department_id', 'type', 'is_active'], 'courses_department_type_active_idx');
            $table->index(['department_id', 'year_level'], 'courses_department_year_level_idx');
            $table->unique(['department_id', 'color'], 'courses_department_color_unique');
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
