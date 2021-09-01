<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayedGame extends Model
{
    use HasFactory;

	protected $fillable=[
        'game_date',
        'game_time',
        'call_numbers',
        'called_numbers',
        'created_at',
        'updated_at',
    ];

}
