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
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->foreignId('admin_id')->nullable();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->text('location');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('established_year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};
