<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


    public function up(): void
    {


        Schema::create('token_wallets', function (Blueprint $table) {


            $table->id();



            $table->foreignId('workspace_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();



            $table->unsignedBigInteger('total_tokens')
                ->default(0);



            $table->unsignedBigInteger('used_tokens')
                ->default(0);



            $table->unsignedBigInteger('remaining_tokens')
                ->default(0);



            $table->timestamps();



        });


    }



    public function down(): void
    {

        Schema::dropIfExists('token_wallets');

    }


};
