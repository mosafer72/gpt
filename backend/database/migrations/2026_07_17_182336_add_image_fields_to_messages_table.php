<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            // نوع پیام
            $table->string('type')
                ->default('text')
                ->after('role');

            // آدرس تصویر تولیدشده
            $table->text('image_url')
                ->nullable()
                ->after('content');

        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'image_url',
            ]);
        });
    }
};
