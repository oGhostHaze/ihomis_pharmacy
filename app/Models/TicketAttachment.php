<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;
    protected $table = 'pharm_ticket_attachments';

    protected $fillable = [
        'pharm_ticket_id',
        'pharm_comment_id',
        'path',
        'filename',
        'mime_type',
        'size',
    ];

    /**
     * Get the ticket that the attachment belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'pharm_ticket_id');
    }

    /**
     * Get the comment that the attachment belongs to
     */
    public function comment()
    {
        return $this->belongsTo(TicketComment::class);
    }

    /**
     * Get the size in human readable format
     */
    public function getHumanSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Determine if the attachment is an image
     */
    public function getIsImageAttribute()
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
        ]);
    }
}
