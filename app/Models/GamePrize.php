<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamePrize extends Model
{
    use HasFactory;

	protected $fillable=[
        'prize_name',
        'prize_tag',
		'prize_count',
		'prize_amount',
		'enabled',
        'created_at',
        'updated_at',
    ];
}
