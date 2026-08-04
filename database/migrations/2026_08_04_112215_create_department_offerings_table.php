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
            $table->enum('track_type', ['zankoline', 'parallel'])->default('zankoline');
            $table->enum('governorate', ['Erbil', 'Sulaimani', 'Duhok', 'Halabja', 'Kirkuk']);
            $table->enum('major_type',['Scientific','literary']);
            $table->text('city');
            $table->decimal('minimum_grade', 6, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_offerings');
    }
};
