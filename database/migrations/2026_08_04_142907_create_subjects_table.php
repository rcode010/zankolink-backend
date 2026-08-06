<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->enum('major_type', [
                'scientific',
                'literary',
                'general',
            ]);

            $table->unsignedTinyInteger('credit_number');

            $table->unsignedTinyInteger('year_level');

            $table->timestamps();

            $table->unique([
                'name',
                'major_type',
                'year_level',
            ], 'subjects_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
