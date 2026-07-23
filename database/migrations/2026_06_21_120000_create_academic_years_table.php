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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();

            $table->string('year');          // 2025-2026

            $table->date('start_date');
            $table->enum('semester',['fall','spring']);

            $table->date('end_date');

            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->unique('year', 'academic_years_year_unique');
            $table->index('is_active', 'academic_years_is_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
