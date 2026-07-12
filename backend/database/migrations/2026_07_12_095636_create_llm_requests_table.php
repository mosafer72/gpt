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
        Schema::create('llm_requests', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('provider');
            // openai, gapgpt, local, ...

            $table->string('model');

            $table->string('conversation_id')->nullable();

            $table->json('messages');
            // کل payload ارسال شده به مدل

            $table->json('request_options')->nullable();
            // temperature, max_tokens,...

            $table->json('response')->nullable();
            // پاسخ کامل مدل

            $table->json('usage')->nullable();
            // prompt_tokens, completion_tokens

            $table->integer('latency_ms')->nullable();

            $table->string('status')
                ->default('pending');

            $table->text('error')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_requests');
    }
};
