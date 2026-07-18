<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('path');
            $table->string('url');
            $table->string('mime_type')->default('application/pdf');
            $table->unsignedBigInteger('size')->default(0);

            // متن استخراج شده از PDF
            $table->longText('extracted_text')->nullable();

            // خلاصه اولیه مقاله
            $table->text('summary')->nullable();

            // متادیتاهای احتمالی
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};
