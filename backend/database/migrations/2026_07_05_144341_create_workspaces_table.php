<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


    public function up(): void
    {

        Schema::create('workspaces', function (Blueprint $table) {


            $table->id();


            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->foreignId('workspace_plan_id')
                ->constrained('workspace_plans')
                ->cascadeOnDelete();



            $table->string('name');


            $table->string('type');



            $table->timestamp('started_at')
                ->nullable();


            $table->timestamp('expires_at')
                ->nullable();



            $table->boolean('is_active')
                ->default(true);



            $table->timestamps();


        });

    }



    public function down(): void
    {

        Schema::dropIfExists('workspaces');

    }


};
