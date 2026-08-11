<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->integer('zankoline_capacity');
            $table->integer('parallel_capacity');
            $table->enum('governorate', ['Erbil', 'Sulaimani', 'Duhok', 'Halabja', 'Kirkuk']);
            $table->enum('major_type', ['scientific', 'literary']);
            $table->text('city');
            $table->decimal('minimum_grade_zankoline', 6, 3)->nullable();
            $table->decimal('minimum_grade_parallel', 6, 3)->nullable();

            $table->timestamps();

            $table->unique(['department_id', 'academic_year_id'], 'department_offering_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_offerings');
    }
};
