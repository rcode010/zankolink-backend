<?php

use App\Enums\ApplicationStep;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('high_school_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('code')->unique();
            $table->enum('major_type', [
                'scientific',
                'literary',
            ]);

            $table->enum('gender', [
                'male',
                'female',
            ]);
            $table->boolean('is_active')->default(true);
            $table->enum('status', [
                'draft',
                'submitted',
                'accepted',
                'rejected',
                'enrolled',
            ])->default('draft');
            $table->string('password');

            $table->decimal('grade_average', 6, 3);
            $table->decimal('grade_10', 6, 3);
            $table->decimal('grade_11', 6, 3);

            $table->foreignId('accepted_department_offering_id')
                ->nullable()
                ->constrained('department_offerings')
                ->nullOnDelete();
            $table->timestamps();
            $table->index('status');
            $table->enum(
                'current_step',
                array_column(ApplicationStep::cases(), 'value')
            )->default(ApplicationStep::GUIDE->value);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('high_school_students');
    }
};
