<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemTicketComment extends Model
{
    use HasFactory;
    protected $connection = 'worker';
    protected $table = 'system_tickets_comments';

    protected $fillable = [
        'user_id',
        'system_ticket_id',
        'comment',
        'attachments',
    ];
}
