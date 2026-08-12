<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admission run groups students into cohorts by grade average and then
     * walks them highest first. Without this index both the cohort manifest and
     * every window query fall back to a full scan of the table.
     */
    public function up(): void
    {
        Schema::table('high_school_students', function (Blueprint $table) {
            $table->index(['grade_average', 'id'], 'hss_grade_average_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('high_school_students', function (Blueprint $table) {
            $table->dropIndex('hss_grade_average_id_index');
        });
    }
};
