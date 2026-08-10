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
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->enum('accepted_gender', ['male', 'female', 'both'])->nullable();
            $table->bigInteger('seat_available')->nullable();
            $table->timestamps();
            $table->timestamp('course_selection_starts_at')->nullable();
            $table->timestamp('course_selection_ends_at')->nullable();
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
