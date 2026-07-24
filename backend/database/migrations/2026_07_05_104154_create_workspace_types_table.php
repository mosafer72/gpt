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
        Schema::create('workspace_types', function (Blueprint $table) {

            $table->id();

            $table->string('key')->unique();

            // Research Workspace
            $table->string('name');


            $table->string('display_name');


            // توضیحات صفحه فروش
            $table->text('description')
                ->nullable();


            // آیکون
            $table->string('icon')
                ->nullable();


            // امکانات نمایشی
            $table->json('features')
                ->nullable();


            $table->boolean('is_active')
                ->default(true);


            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_types');
    }
};
