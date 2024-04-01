<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemTicketCommentReply extends Model
{
    use HasFactory;
    protected $connection = 'worker';
    protected $table = 'system_tickets_comment_replies';

    protected $fillable = [
        'user_id',
        'system_ticket_comment_id',
        'comment',
        'attachments',
    ];
}