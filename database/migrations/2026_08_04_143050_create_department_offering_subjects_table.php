<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_offering_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_offering_id')
                ->constrained('department_offerings')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('credit');

            $table->decimal('minimum_grade', 6, 3);

            $table->timestamps();

            $table->unique([
                'department_offering_id',
                'subject_id',
            ], 'department_offering_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_offering_subjects');
    }
};
