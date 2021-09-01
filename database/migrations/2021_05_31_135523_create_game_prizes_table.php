<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamePrizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_prizes', function (Blueprint $table) {
            $table->id();
			$table->string('prize_name');
			$table->string('prize_tag');
			$table->integer('prize_count');
			$table->double('prize_amount')->default(0);
			$table->boolean('enabled')->default(1);
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
        Schema::dropIfExists('game_prizes');
    }
}
