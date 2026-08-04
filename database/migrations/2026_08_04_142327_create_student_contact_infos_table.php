<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_contact_infos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->unique()
                ->constrained('high_school_students')
                ->cascadeOnDelete();

            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('id_number')->unique();

            $table->enum('governorate', [
                'Erbil',
                'Sulaimani',
                'Duhok',
                'Halabja',
                'Kirkuk',
            ]);

            $table->text('home_address');

            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');

            $table->timestamps();

            $table->index('governorate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_contact_infos');
    }
};
