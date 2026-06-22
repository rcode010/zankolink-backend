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
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('letter_id');
            $table->string('file_name');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->string('file_url'); // Path to your Cloud Storage
            $table->timestamps();

            $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_signature');
    }
};
