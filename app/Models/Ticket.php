<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'pharm_tickets';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'type',
        'reporter_id',
        'assignee_id',
        'due_date',
        'closed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Type constants
    const TYPE_BUG = 'bug';
    const TYPE_FEATURE = 'feature';
    const TYPE_UPDATE = 'update';

    /**
     * Get the user who reported the ticket
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user assigned to the ticket
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the comments for the ticket
     */
    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'pharm_ticket_id');
    }

    /**
     * Get the attachments for the ticket
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'pharm_ticket_id');
    }

    /**
     * Get the ticket history (activities)
     */
    public function activities()
    {
        return $this->hasMany(TicketActivity::class, 'pharm_ticket_id');
    }

    /**
     * Scope a query to only include pending tickets
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include approved tickets
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include ongoing tickets
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', self::STATUS_ONGOING);
    }

    /**
     * Scope a query to only include finished tickets
     */
    public function scopeFinished($query)
    {
        return $query->where('status', self::STATUS_FINISHED);
    }

    /**
     * Get the status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge badge-warning">Pending</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge badge-info">Approved</span>';
            case self::STATUS_ONGOING:
                return '<span class="badge badge-primary">Ongoing</span>';
            case self::STATUS_FINISHED:
                return '<span class="badge badge-success">Finished</span>';
            default:
                return '<span class="badge">Unknown</span>';
        }
    }

    /**
     * Get the priority badge HTML
     */
    public function getPriorityBadgeAttribute()
    {
        switch ($this->priority) {
            case self::PRIORITY_LOW:
                return '<span class="badge badge-success">Low</span>';
            case self::PRIORITY_MEDIUM:
                return '<span class="badge badge-info">Medium</span>';
            case self::PRIORITY_HIGH:
                return '<span class="badge badge-warning">High</span>';
            case self::PRIORITY_CRITICAL:
                return '<span class="badge badge-error">Critical</span>';
            default:
                return '<span class="badge">Unknown</span>';
        }
    }

    /**
     * Get the type badge HTML
     */
    public function getTypeBadgeAttribute()
    {
        switch ($this->type) {
            case self::TYPE_BUG:
                return '<span class="badge badge-error">Bug</span>';
            case self::TYPE_FEATURE:
                return '<span class="badge badge-success">Feature</span>';
            case self::TYPE_UPDATE:
                return '<span class="badge badge-info">Update</span>';
            default:
                return '<span class="badge">Unknown</span>';
        }
    }
}
