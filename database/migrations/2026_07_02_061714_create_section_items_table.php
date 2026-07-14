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
        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('course_sections')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('material_file_type')->nullable(); // e.g. pdf, image, video, document, link
            $table->string('material_file_name')->nullable();
            $table->string('material_file_url')->nullable();
            $table->timestamps();


            $table->index(['section_id'], 'section_items_section_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_items');
    }
};
