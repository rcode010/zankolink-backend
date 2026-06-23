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
            $table->string('letter_number')->unique();
            $table->foreignId('original_sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->enum('type', ['internal', 'directive', 'request', 'decision', 'appeal']);
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_read')->default(false);
            $table->string('academic_year');
            $table->boolean('is_archived')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

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
