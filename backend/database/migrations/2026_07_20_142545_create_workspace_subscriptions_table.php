<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


    public function up(): void
    {


        Schema::create('workspace_subscriptions', function (Blueprint $table) {


            $table->id();



            /*
            |--------------------------------------------------------------------------
            | Owner
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();





            /*
            |--------------------------------------------------------------------------
            | Workspace
            |--------------------------------------------------------------------------
            */

            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();





            /*
            |--------------------------------------------------------------------------
            | Purchased Plan
            |--------------------------------------------------------------------------
            */

            $table->foreignId('workspace_plan_id')
                ->constrained()
                ->cascadeOnDelete();





            /*
            |--------------------------------------------------------------------------
            | Subscription Period
            |--------------------------------------------------------------------------
            */

            $table->timestamp('started_at')
                ->nullable();



            $table->timestamp('expires_at')
                ->nullable();





            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);





            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->string('transaction_id')
                ->nullable();





            $table->timestamps();





            /*
            |--------------------------------------------------------------------------
            | Performance
            |--------------------------------------------------------------------------
            */

            $table->index([

                'workspace_id',

                'is_active',

                'expires_at'

            ]);



        });


    }





    public function down(): void
    {

        Schema::dropIfExists(
            'workspace_subscriptions'
        );

    }


};
