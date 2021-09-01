<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSetting extends Model
{
    use HasFactory;

	protected $fillable=[
		'game_name',
		'web_name',
		'rhyming_speech',
		'recent_numbers_position',
        'booking_open',
		'website_status',
		'ticket_play_status',
		'next_game_ticket_price',
        'game_freeze',
        'call_interval',
        'call_pitch',
        'call_speed',
        'game_terms_conditions',
		'booking_close_minute',
        'whatsapp_link',
		'ticket_purchase_notification',
		'game_start_notification',
		'prize_claim_notification',
		'game_end_notification',
		'notification_auth',
		'purchase_sms',
		'claim_sms',
		'sms_api',
		'sms_sender_id',
		'purchase_sms_message',
		'claims_sms_message',
        'created_at',
        'updated_at',
    ];
}
