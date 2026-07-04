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
        Schema::create('letter_broadcast_attachements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_broadcast_id')->constrained('letter_broadcasts')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->string('file_path');
            $table->timestamps();

            $table->index('letter_broadcast_id', 'letter_broadcast_attachments_broadcast_id_idx');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_broadcast_attachements');
    }
};
