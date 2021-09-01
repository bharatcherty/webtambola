<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_tickets', function (Blueprint $table) {
            $table->id();
			$table->string('game_date');
			$table->string('game_time');
			$table->bigInteger('agent_id');
			$table->integer('ticket_number');
			$table->text('ticket');
			$table->integer('sheet_number');
			$table->string('sheet_type');
			$table->string('customer_name');
			$table->string('customer_phone');
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
        Schema::dropIfExists('game_tickets');
    }
}
