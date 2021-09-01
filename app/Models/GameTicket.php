<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTicket extends Model
{
    use HasFactory;

	protected $fillable=[
        'game_date',
        'game_time',
        'agent_id',
        'ticket_number',
        'ticket',
		'sheet_number',
		'sheet_type',
        'customer_name',
        'customer_phone',
        'created_at',
        'updated_at',
    ];
}
