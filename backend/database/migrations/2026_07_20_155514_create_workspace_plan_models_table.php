<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('workspace_plan_models', function (Blueprint $table) {

            $table->id();


            $table->foreignId('workspace_plan_id')
                ->constrained('workspace_plans')
                ->cascadeOnDelete();


            $table->foreignId('ai_model_id')
                ->constrained('ai_models')
                ->cascadeOnDelete();


            $table->timestamps();



            $table->unique([
                'workspace_plan_id',
                'ai_model_id'
            ]);

        });

    }



    public function down(): void
    {

        Schema::dropIfExists(
            'workspace_plan_models'
        );

    }

};
