<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

	protected $fillable=[
        'agent_name',
        'agent_address',
        'agent_username',
        'agent_password',
		'commission_amount',
        'agent_phone',
        'agent_whatsapp',
		'active',
        'agent_deleted',
        'created_at',
        'updated_at',
    ];
}
