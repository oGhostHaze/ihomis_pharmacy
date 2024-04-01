<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemTicket extends Model
{
    use HasFactory;
    protected $connection = 'worker';
    protected $table = 'system_tickets';

    protected $fillable = [
        'title',
        'description',
        'photos',
        'status',
        'requested_by',
    ];
}
