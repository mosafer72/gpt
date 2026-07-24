<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('document_chunks', function(Blueprint $table){


            $table->id();


            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->longText('content');


            $table->integer('chunk_index')
                ->default(0);



            $table->integer('tokens')
                ->default(0);



            $table->timestamps();


        });

    }



    public function down(): void
    {
        Schema::dropIfExists(
            'document_chunks'
        );
    }

};
