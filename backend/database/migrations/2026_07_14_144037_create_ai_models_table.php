<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {

            $table->id();


            // شناسه مدل برای API
            // مثال: gpt-4.1-mini
            $table->string('key')
                ->unique();


            // نام نمایشی
            // مثال: GPT 4.1 Mini
            $table->string('name');


            // سرویس دهنده
            // openai, anthropic, deepseek
            $table->string('provider');


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


            // فعال یا غیرفعال
            $table->boolean('is_active')
                ->default(true);


            $table->timestamps();

        });



              Schema::create('plan_model', function (Blueprint $table) {


            $table->id();


            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('ai_model_id')
                ->constrained('ai_models')
                ->cascadeOnDelete();


            // مدل پیش فرض این پلن
            $table->boolean('is_default')
                ->default(false);


            $table->timestamps();


            $table->unique([
                'plan_id',
                'ai_model_id'
            ]);


        });


    }


    public function down(): void
    {
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('plan_model');

    }

};
