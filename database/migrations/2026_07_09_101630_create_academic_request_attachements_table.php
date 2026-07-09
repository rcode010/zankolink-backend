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
        Schema::create('academic_request_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_request_id')
                ->constrained('academic_requests')
                ->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->text('file_path');

            $table->timestamps();

            $table->index('academic_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_request_attachments');
    }
};
