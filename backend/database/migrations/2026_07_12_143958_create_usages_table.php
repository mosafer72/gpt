<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


    public function up(): void
    {


        Schema::create('usages', function (Blueprint $table) {

            $table->id();


            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();


            $table->foreignId('ai_model_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();



            $table->string('provider')
                ->nullable();


            $table->string('model')
                ->nullable();



            $table->unsignedBigInteger('input_tokens')
                ->default(0);


            $table->unsignedBigInteger('output_tokens')
                ->default(0);


            $table->unsignedBigInteger('total_tokens')
                ->default(0);



            $table->decimal('input_cost', 10, 6)
                ->default(0);


            $table->decimal('output_cost', 10, 6)
                ->default(0);


            $table->decimal('total_cost', 10, 6)
                ->default(0);



            $table->string('status')
                ->default('success');



            $table->json('metadata')
                ->nullable();



            $table->timestamps();

        });


    }



    public function down(): void
    {

        Schema::dropIfExists('usages');

    }


};
