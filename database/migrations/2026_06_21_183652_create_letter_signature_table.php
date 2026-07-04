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
        Schema::create('letter_signature', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_id')
                ->constrained('letters')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('comment')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'letter_id']);

            $table->index('letter_id', 'letter_signature_letter_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_signature');
    }
};
