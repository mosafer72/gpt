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



            $table->foreignId('workspace_type_id')
                ->constrained()
                ->cascadeOnDelete();



            $table->string('name');



            /*
            |--------------------------------------------------------------------------
            | Legacy field
            | فعلا نگه می داریم چون در مدل داری
            |--------------------------------------------------------------------------
            */

            $table->string('type')
                ->nullable();




            $table->timestamp('started_at')
                ->nullable();




            /*
            |--------------------------------------------------------------------------
            | حذف نشد برای جلوگیری از migration مشکل
            | ولی دیگر منبع اعتبار نیست
            |--------------------------------------------------------------------------
            */

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
