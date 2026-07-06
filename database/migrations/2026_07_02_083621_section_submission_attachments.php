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
        Schema::create('section_submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_submission_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->text('file_path');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_submission_attachments');
    }
};
