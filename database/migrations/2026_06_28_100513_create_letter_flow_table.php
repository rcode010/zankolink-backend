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
        Schema::create('letter_flow', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_id')->constrained()->onDelete('cascade');

            $table->string('action');

            $table->string('role')->nullable();

            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('scope_type')->nullable();

            $table->text('note')->nullable();

            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedBigInteger('from_recipient_id')->nullable();
            $table->unsignedBigInteger('to_recipient_id')->nullable();

            $table->timestamps();

            $table->index(['letter_id', 'created_at'], 'letter_flow_letter_created_idx');
            $table->index('actor_id', 'letter_flow_actor_id_idx');
            $table->index('from_recipient_id', 'letter_flow_from_recipient_idx');
            $table->index('to_recipient_id', 'letter_flow_to_recipient_idx');
            $table->index(['scope_type', 'scope_id'], 'letter_flow_scope_idx');
            $table->index('action', 'letter_flow_action_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_flow');
    }
};
