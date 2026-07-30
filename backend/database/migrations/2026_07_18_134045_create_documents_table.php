<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


    public function up(): void
    {

        Schema::create('documents', function (Blueprint $table) {


            $table->id();



            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();




            $table->string('name');



            $table->string('path');



            $table->string('mime_type')
                ->nullable();



            $table->unsignedBigInteger('size')
                ->default(0);





            /*
            |--------------------------------------------------------------------------
            | Document Processing
            |--------------------------------------------------------------------------
            */


            $table->enum(
                'status',
                [

                    'uploaded',

                    'processing',

                    'completed',

                    'failed'

                ]
            )
            ->default('uploaded');





            /*
            |--------------------------------------------------------------------------
            | Translation Pipeline
            |--------------------------------------------------------------------------
            */


            $table->enum(
                'translation_status',
                [

                    'pending',

                    'processing',

                    'completed',

                    'failed'

                ]
            )
            ->default('pending');






            /*
            |--------------------------------------------------------------------------
            | Error Handling
            |--------------------------------------------------------------------------
            */


            $table->text('error')
                ->nullable();





            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */


            $table->json('metadata')
                ->nullable();





            $table->timestamps();



            $table->index([
                'workspace_id',
                'status'
            ]);



        });

    }





    public function down(): void
    {

        Schema::dropIfExists(
            'documents'
        );

    }


};
