<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('letter_number');
            $table->string('sender_type');
            $table->unsignedBigInteger('original_sender_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('receiver_type');
            $table->unsignedBigInteger('receiver_id');
            $table->enum('type', ['internal', 'directive', 'request', 'decision', 'appeal']);
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_read')->default(false);
            $table->string('academic_year');
            $table->boolean('is_archived')->default(false);
            $table->enum('status', ['draft', 'pending', 'sent', 'rejected'])->default('draft');
            $table->timestamps();

            $table->foreign('original_sender_id')->references('id')->on('Users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
