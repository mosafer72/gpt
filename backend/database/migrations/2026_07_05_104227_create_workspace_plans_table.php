<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('workspace_plans', function (Blueprint $table) {


            $table->id();


            $table->string('name')
                ->unique();



            $table->foreignId('workspace_type_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->string('display_name');



            $table->text('description')
                ->nullable();



            $table->integer('price')
                ->default(0);



            $table->integer('duration_days')
                ->default(30);



            $table->integer('token_limit')
                ->default(0);



            $table->json('features')
                ->nullable();



            $table->json('capabilities')
                ->nullable();



            $table->boolean('is_active')
                ->default(true);



            $table->timestamps();

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('workspace_plans');
    }

};
