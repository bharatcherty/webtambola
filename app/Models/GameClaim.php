<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameClaim extends Model
{
    use HasFactory;
	
	protected $fillable=[
        'game_date',
        'game_time',
        'prize_name',
		'prize_tag',
		'prize_amount',
        'claimed_ticket_id',
        'claimed_ticket_number',
        'checked_numbers',
        'created_at',
        'updated_at',
    ];
}
