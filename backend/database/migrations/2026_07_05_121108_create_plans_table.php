<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {

            $table->id();


            // free, basic, standard, premium
            $table->string('name')
                ->unique();


            // نام نمایشی
            $table->string('display_name');


            // مدل پیش فرض
            $table->string('model')
                ->default('gpt-4o-mini');


            // قیمت
            $table->decimal(
                'price',
                10,
                0
            )
                ->default(0);



            // مدت اعتبار
            $table->integer('duration_days')
                ->default(30);


            // محدودیت پیام روزانه
            $table->integer('daily_message_limit')
                ->default(10);


            /**
             * تنظیمات پلن
             *
             * {
             *   planner:true,
             *   memory:true,
             *   max_tokens:4000,
             *   temperature:0.7
             * }
             */

             $table->json('capabilities')
                ->nullable();

            $table->json('features')
                ->nullable();


            $table->boolean('is_active')
                ->default(true);


            $table->timestamps();

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
