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
        Schema::create('usages', function (Blueprint $table) {

             $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('conversation_id')->nullable()->constrained('chats')->nullOnDelete();

            $table->string('model')->nullable();

            $table->integer('prompt_tokens')->default(0);

            $table->integer('completion_tokens')->default(0);

            $table->integer('total_tokens')->default(0);

            $table->string('type')->default('chat');

            $table->timestamps();

            $table->index(['user_id', 'created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
