<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // basic, standard, premium, enterprise
            $table->string('display_name'); // پایه، متوسط، پیشرفته، ویژه
            $table->string('model'); // gpt-4o-mini, gpt-4o, gpt-4.5, gpt-4-turbo
            $table->decimal('price', 10, 0); // قیمت به ریال (تومان * ۱۰)
            $table->integer('duration_days'); // تعداد روزهای اعتبار
            $table->text('features')->nullable(); // ویژگی‌ها به صورت JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
