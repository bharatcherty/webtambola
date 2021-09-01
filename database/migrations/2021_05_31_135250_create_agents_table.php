<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
			$table->string('agent_name');
			$table->string('agent_address')->nullable();
			$table->string('agent_username');
			$table->string('agent_password');
			$table->double('commission_amount')->default(0);
			$table->string('agent_phone');
			$table->string('agent_whatsapp')->nullable();
			$table->boolean('active')->default(1);
			$table->boolean('agent_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agents');
    }
}
