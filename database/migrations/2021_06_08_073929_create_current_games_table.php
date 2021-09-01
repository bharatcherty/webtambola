<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_games', function (Blueprint $table) {
            $table->id();
			$table->string('game_date');
			$table->string('game_time');
			$table->string('last_game_date');
			$table->string('last_game_time');
			$table->dateTime('last_game_datetime');
			$table->dateTime('game_date_time');
			$table->dateTime('game_over_time');
			$table->dateTime('booking_close');
			$table->string('game_status')->default("ACTIVE");
			$table->boolean('change_required')->default(0);
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
        Schema::dropIfExists('current_games');
    }
}
