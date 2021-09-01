<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_settings', function (Blueprint $table) {
            $table->id();
			$table->string('game_name');
			$table->string('web_name');
			$table->boolean('rhyming_speech');
			$table->string('recent_numbers_position');
			$table->boolean('booking_open');
			$table->boolean('website_status')->default(1);
			$table->string('ticket_play_status')->default('ALLTICKET');
			$table->double('next_game_ticket_price')->default(100);
			$table->boolean('game_freeze');
			$table->integer('call_interval');
			$table->integer('call_pitch');
			$table->integer('call_speed');
			$table->integer('booking_close_minute');
			$table->text('game_terms_conditions');
			$table->text('whatsapp_link');
			$table->boolean('ticket_purchase_notification')->default(1);
			$table->boolean('game_start_notification')->default(1);
			$table->boolean('prize_claim_notification')->default(1);
			$table->boolean('game_end_notification')->default(1);
			$table->text('notification_auth');
			$table->boolean('purchase_sms')->default(1);
			$table->boolean('claim_sms')->default(1);
			$table->string('sms_api');
			$table->string('sms_sender_id');
			$table->text('purchase_sms_message');
			$table->text('claims_sms_message');
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
        Schema::dropIfExists('game_settings');
    }
}
