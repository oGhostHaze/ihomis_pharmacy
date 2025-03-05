<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    use HasFactory;
    protected $table = 'pharm_ticket_comments';

    protected $fillable = [
        'pharm_ticket_id',
        'user_id',
        'comment',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the ticket that the comment belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'pharm_ticket_id');
    }

    /**
     * Get the user who created the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachments for the comment
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'pharm_comment_id');
    }
}
