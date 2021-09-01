<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

	protected $fillable=[
        'ticket',
        'ticket_number',
        'sheet_number',
        'sheet_type',
        'created_at',
        'updated_at',
    ];
}
