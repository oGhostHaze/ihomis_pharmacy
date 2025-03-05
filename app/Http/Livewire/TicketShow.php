<?php

namespace App\Http\Livewire;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketShow extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    public $ticket;
    public $comment = '';
    public $attachments = [];
    public $showAssignModal = false;
    public $assigneeId = null;

    protected $rules = [
        'comment' => 'required|string',
        'attachments.*' => 'nullable|file|max:10240', // 10MB max file size
    ];

    protected $listeners = ['refreshTicket' => '$refresh'];

    public function mount($ticketId)
    {
        $this->ticket = Ticket::with(['reporter', 'assignee', 'comments.user', 'comments.attachments', 'attachments'])
            ->findOrFail($ticketId);

        $this->authorize('view-tickets');

        if ($this->ticket->assignee_id) {
            $this->assigneeId = $this->ticket->assignee_id;
        }
    }

    public function addComment()
    {
        $this->validate([
            'comment' => 'required|string',
        ]);

        $comment = $this->ticket->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $this->comment,
        ]);

        // Handle attachments
        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('ticket-comments/' . $comment->id, 'public');
                $comment->attachments()->create([
                    'pharm_ticket_id' => $this->ticket->id,
                    'path' => $path,
                    'filename' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ]);
            }
        }

        // Record activity
        $this->ticket->activities()->create([
            'user_id' => auth()->id(),
            'activity' => 'comment_added',
            'properties' => [
                'pharm_comment_id' => $comment->id,
            ]
        ]);

        $this->reset(['comment', 'attachments']);
        $this->emit('alertSuccess', 'Comment added successfully');
    }

    public function updateStatus($status)
    {
        $this->authorize('manage-tickets');

        $oldStatus = $this->ticket->status;
        $this->ticket->status = $status;

        // If status is approved and no assignee, assign to current user
        if ($status == Ticket::STATUS_APPROVED && !$this->ticket->assignee_id) {
            $this->ticket->assignee_id = auth()->id();
            $this->assigneeId = auth()->id();
        }

        // If status is finished, set closed_at
        if ($status == Ticket::STATUS_FINISHED && !$this->ticket->closed_at) {
            $this->ticket->closed_at = now();
        }

        $this->ticket->save();

        // Add system comment
        $comment = $this->ticket->comments()->create([
            'user_id' => auth()->id(),
            'comment' => 'Changed status from ' . ucfirst($oldStatus) . ' to ' . ucfirst($status),
            'is_system' => true,
        ]);

        // Record activity
        $this->ticket->activities()->create([
            'user_id' => auth()->id(),
            'activity' => 'status_changed',
            'properties' => [
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]
        ]);

        $this->emit('alertSuccess', 'Ticket status updated successfully');
    }

    public function openAssignModal()
    {
        $this->authorize('manage-tickets');
        $this->showAssignModal = true;
    }

    public function assignTicket()
    {
        $this->authorize('manage-tickets');

        $oldAssigneeId = $this->ticket->assignee_id;
        $this->ticket->assignee_id = $this->assigneeId;
        $this->ticket->save();

        // Get assignee name
        $assigneeName = User::find($this->assigneeId)->name;

        // Add system comment
        $comment = $this->ticket->comments()->create([
            'user_id' => auth()->id(),
            'comment' => 'Assigned ticket to ' . $assigneeName,
            'is_system' => true,
        ]);

        // Record activity
        $this->ticket->activities()->create([
            'user_id' => auth()->id(),
            'activity' => 'assignee_changed',
            'properties' => [
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $this->assigneeId,
            ]
        ]);

        $this->showAssignModal = false;
        $this->emit('alertSuccess', 'Ticket assigned successfully');
    }

    public function render()
    {
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'developer', 'support']);
        })->get();

        return view('livewire.ticket-show', [
            'users' => $users,
        ]);
    }
}
