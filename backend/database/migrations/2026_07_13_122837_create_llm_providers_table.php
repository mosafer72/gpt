<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {

    public function up(): void
    {

        Schema::create('llm_providers', function (Blueprint $table) {


            $table->id();


            // شناسه داخلی
            // openai
            // openrouter
            // deepseek
            $table->string('key')
                ->unique();



            // نام نمایشی
            $table->string('name');



            // کلید API
            $table->text('api_key');



            // آدرس پایه API
            // https://api.openai.com/v1
            $table->text('base_url')
                ->nullable();



            // فعال / غیرفعال
            $table->boolean('is_active')
                ->default(true);



            $table->timestamps();

        });

    }



    public function down(): void
    {

        Schema::dropIfExists('llm_providers');

    }

};
