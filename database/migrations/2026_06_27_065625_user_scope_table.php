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
        Schema::create('user_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->enum('scope_type', ['MINISTRY', 'FACULTY', 'UNIVERSITY', 'DEPARTMENT']);
            $table->foreignId('scope_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_scopes_unique');

            $table->index(['user_id', 'scope_type', 'scope_id'], 'user_scopes_user_scope_scope_id_idx');
            $table->index(['scope_type', 'scope_id'], 'user_scopes_scope_type_scope_id_idx');
            $table->index(['role_id', 'scope_type', 'scope_id'], 'user_scopes_role_scope_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
    }
};
