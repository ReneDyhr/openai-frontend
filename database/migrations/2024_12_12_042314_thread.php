<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->text('instructions');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('thread_id');
            $table->text('content');
            $table->json('annotations')->nullable();
            $table->json('attachments')->nullable();
            $table->string('role');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
        Schema::dropIfExists('messages');
    }
};
