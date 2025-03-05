<?php

namespace App\Http\Livewire;

use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketForm extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    public $ticket;
    public $ticketId;
    public $title;
    public $description;
    public $priority = 'medium';
    public $type = 'bug';
    public $attachments = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'priority' => 'required|in:low,medium,high,critical',
        'type' => 'required|in:bug,feature,update',
        'attachments.*' => 'nullable|file|max:10240', // 10MB max file size
    ];

    public function mount($ticketId = null)
    {
        if ($ticketId) {
            $this->ticketId = $ticketId;
            $this->ticket = Ticket::findOrFail($ticketId);

            // Authorize the action
            if (!auth()->user()->can('edit-tickets') && $this->ticket->reporter_id !== auth()->id()) {
                $this->authorize('edit-tickets');
            }

            $this->title = $this->ticket->title;
            $this->description = $this->ticket->description;
            $this->priority = $this->ticket->priority;
            $this->type = $this->ticket->type;
        } else {
            $this->authorize('create-tickets');
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->ticketId) {
            // Update existing ticket
            $this->ticket->title = $this->title;
            $this->ticket->description = $this->description;
            $this->ticket->priority = $this->priority;
            $this->ticket->type = $this->type;
            $this->ticket->save();

            // Record activity
            $this->ticket->activities()->create([
                'user_id' => auth()->id(),
                'activity' => 'ticket_updated',
                'properties' => [
                    'title' => $this->title,
                    'priority' => $this->priority,
                    'type' => $this->type,
                ]
            ]);

            $message = 'Ticket updated successfully';
        } else {
            // Create new ticket
            $ticket = new Ticket();
            $ticket->title = $this->title;
            $ticket->description = $this->description;
            $ticket->priority = $this->priority;
            $ticket->type = $this->type;
            $ticket->status = Ticket::STATUS_PENDING; // Default status
            $ticket->reporter_id = auth()->id();
            $ticket->save();

            $this->ticket = $ticket;
            $this->ticketId = $ticket->id;

            // Record activity
            $ticket->activities()->create([
                'user_id' => auth()->id(),
                'activity' => 'ticket_created',
                'properties' => [
                    'title' => $this->title,
                    'priority' => $this->priority,
                    'type' => $this->type,
                ]
            ]);

            $message = 'Ticket created successfully';
        }

        // Handle attachments
        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('ticket-attachments/' . $this->ticketId, 'public');
                $this->ticket->attachments()->create([
                    'path' => $path,
                    'filename' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ]);
            }
        }

        $this->emit('alertSuccess', $message);

        if (!$this->ticketId) {
            // Reset form if creating new ticket
            $this->reset(['title', 'description', 'priority', 'type', 'attachments']);
            return redirect()->route('tickets.show', $this->ticket->id);
        }

        // Reset attachments
        $this->attachments = [];
    }

    public function render()
    {
        return view('livewire.ticket-form', [
            'priorityOptions' => [
                Ticket::PRIORITY_LOW => 'Low',
                Ticket::PRIORITY_MEDIUM => 'Medium',
                Ticket::PRIORITY_HIGH => 'High',
                Ticket::PRIORITY_CRITICAL => 'Critical',
            ],
            'typeOptions' => [
                Ticket::TYPE_BUG => 'Bug',
                Ticket::TYPE_FEATURE => 'Feature',
                Ticket::TYPE_UPDATE => 'Update',
            ],
        ]);
    }
}
