<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {

    public function up(): void
    {

        Schema::create('ai_models', function (Blueprint $table) {


            $table->id();



            // کلید داخلی سیستم
            // gpt-mini
            // deepseek-chat
            $table->string('key')
                ->unique();



            // نام نمایشی
            // GPT 4.1 Mini
            $table->string('name');



            // نام واقعی API
            // gpt-4.1-mini
            $table->string('model_name');



            // Provider مربوطه
            $table->foreignId('provider_id')
                ->constrained('llm_providers')
                ->cascadeOnDelete();



            // هزینه ورودی هر میلیون توکن
            $table->decimal(
                'input_cost',
                10,
                4
            )
                ->default(0);



            // هزینه خروجی هر میلیون توکن
            $table->decimal(
                'output_cost',
                10,
                4
            )
                ->default(0);



            // فعال بودن مدل
            $table->boolean('is_active')
                ->default(true);


            $table->string('type')
                ->default('chat');



            $table->timestamps();


        });

        Schema::create('workspace_model', function (Blueprint $table) {


            $table->id();


            $table->foreignId(
                'workspace_plan_id'
            )
                ->constrained()
                ->cascadeOnDelete();



            $table->foreignId(
                'ai_model_id'
            )
                ->constrained()
                ->cascadeOnDelete();



            $table->boolean(
                'is_default'
            )
                ->default(false);



            $table->json(
                'settings'
            )
                ->nullable();



            $table->timestamps();


        });
         Schema::create('plan_model', function (Blueprint $table) {


            $table->id();



            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->foreignId('ai_model_id')
                ->constrained()
                ->cascadeOnDelete();



            // مدل پیش فرض پلن
            $table->boolean('is_default')
                ->default(false);



            // محدودیت اختصاصی مدل در این پلن
            // مثلا:
            // max_tokens:2000
            // daily_limit:50

            $table->json('settings')
                ->nullable();



            $table->timestamps();



            $table->unique([
                'plan_id',
                'ai_model_id'
            ]);

        });

    }



    public function down(): void
    {

        Schema::dropIfExists('plan_model');
        Schema::dropIfExists('ai_models');

    }

};
