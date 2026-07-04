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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->text('name');
            $table->foreignId('admin_id')->nullable();
            $table->boolean('is_active');
            $table->bigInteger('seat_available')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->index(['faculty_id', 'is_active', 'deleted_at'], 'departments_faculty_active_deleted_idx');
            $table->index('admin_id', 'departments_admin_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
