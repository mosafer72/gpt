<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {


public function up(): void
{


Schema::create('workspace_subscriptions', function(Blueprint $table){


$table->id();



$table->foreignId('user_id')
->constrained()
->cascadeOnDelete();



$table->foreignId('workspace_id')
->constrained()
->cascadeOnDelete();



$table->foreignId('workspace_plan_id')
->constrained()
->cascadeOnDelete();



$table->timestamp('started_at');


$table->timestamp('expires_at')
->nullable();



$table->boolean('is_active')
->default(true);



$table->string('transaction_id')
->nullable();



$table->timestamps();


});


}



public function down(): void
{

Schema::dropIfExists('workspace_subscriptions');

}


};
