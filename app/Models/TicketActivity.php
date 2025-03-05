<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketActivity extends Model
{
    use HasFactory;
    protected $table = 'pharm_ticket_activities';

    protected $fillable = [
        'pharm_ticket_id',
        'user_id',
        'activity',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the ticket that the activity belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'pharm_ticket_id');
    }

    /**
     * Get the user who performed the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
