<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number');
            $table->foreignId('original_sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->enum('type', [
                'hire_teacher',
                'fire_teacher',
                'create_department',
                'close_department',
                'open_faculty',
                'close_faculty',
                'open_university',
                'close_university',
                'remove_student'
            ]);
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_read')->default(false);
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->boolean('is_archived')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('qr_code_path')->nullable();
            $table->uuid('letter_uuid')->unique()->nullable();
            $table->string('verification_hash')->nullable();
            $table->timestamps();

            $table->unique(['letter_number', 'academic_year_id']);

            $table->index(['receiver_id', 'status', 'created_at'], 'letters_receiver_status_created_idx');
            $table->index(['receiver_id', 'is_read', 'created_at'], 'letters_receiver_read_created_idx');
            $table->index(['sender_id', 'created_at'], 'letters_sender_created_idx');
            $table->index(['original_sender_id', 'created_at'], 'letters_original_sender_created_idx');
            $table->index(['academic_year_id', 'status'], 'letters_academic_year_status_idx');
            $table->index(['status', 'type'], 'letters_status_type_idx');
            $table->index('verification_hash', 'letters_verification_hash_idx');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
