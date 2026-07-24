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
        Schema::create('workspace_permissions', function (Blueprint $table) {

            $table->id();


            $table->string('key')
                ->unique();


            $table->string('name');


            $table->text('description')
                ->nullable();


            $table->timestamps();

        });
        Schema::create(
            'workspace_plan_permission',
            function (Blueprint $table) {

                $table->id();


                $table->foreignId(
                    'workspace_plan_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();


                $table->foreignId(
                    'workspace_permission_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();


                $table->timestamps();


            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_permissions');
    }
};
