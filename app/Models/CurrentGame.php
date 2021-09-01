<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentGame extends Model
{
    use HasFactory;

	protected $fillable=[
        'game_date',
        'game_time',
        'game_date_time',
		'game_over_time',
        'booking_close',
		'last_game_date',
		'last_game_time',
		'last_game_datetime',
		'game_status',
		'change_required',
        'created_at',
        'updated_at',
    ];
}
