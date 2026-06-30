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
        Schema::create('letter_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['letter_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_stamps');
    }
};
