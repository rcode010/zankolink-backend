<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_offering_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_offering_id')->constrained()->cascadeOnDelete();
            $table->enum('locality_type', ['internal', 'external']);
            $table->integer('capacity');
            $table->timestamps();

            $table->unique(['department_offering_id', 'locality_type'], 'quotas_unique_locality_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_offering_quotas');
    }
};
