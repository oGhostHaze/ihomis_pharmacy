<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Models\Ticket;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class TicketKanban extends Component
{
    use AuthorizesRequests;

    public $search = '';
    public $priority = '';
    public $type = '';
    public $assignee = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'priority' => ['except' => ''],
        'type' => ['except' => ''],
        'assignee' => ['except' => ''],
    ];

    protected $listeners = ['ticketMoved' => 'handleTicketMoved'];

    public function mount()
    {
        $this->authorize('view-tickets');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->priority = '';
        $this->type = '';
        $this->assignee = '';
    }

    public function handleTicketMoved($ticketId, $newStatus)
    {
        $ticket = Ticket::findOrFail($ticketId);

        if (auth()->user()->can('manage-tickets')) {
            $oldStatus = $ticket->status;
            $ticket->status = $newStatus;

            // If approved and not assigned, assign to current user
            if ($newStatus == Ticket::STATUS_APPROVED && !$ticket->assignee_id) {
                $ticket->assignee_id = auth()->id();
            }

            $ticket->save();

            // Record activity
            $ticket->activities()->create([
                'user_id' => auth()->id(),
                'activity' => 'status_changed',
                'properties' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            ]);

            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Ticket #{$ticket->id} moved to " . ucfirst($newStatus)
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'You do not have permission to update ticket status'
            ]);
        }
    }

    public function render()
    {
        $baseQuery = Ticket::query()
            ->with(['reporter', 'assignee'])
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->priority, function ($q) {
                return $q->where('priority', $this->priority);
            })
            ->when($this->type, function ($q) {
                return $q->where('type', $this->type);
            })
            ->when($this->assignee, function ($q) {
                if ($this->assignee === 'unassigned') {
                    return $q->whereNull('assignee_id');
                }
                return $q->where('assignee_id', $this->assignee);
            });

        $pendingTickets = (clone $baseQuery)->where('status', Ticket::STATUS_PENDING)->get();
        $approvedTickets = (clone $baseQuery)->where('status', Ticket::STATUS_APPROVED)->get();
        $ongoingTickets = (clone $baseQuery)->where('status', Ticket::STATUS_ONGOING)->get();
        $finishedTickets = (clone $baseQuery)->where('status', Ticket::STATUS_FINISHED)->get();

        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'developer', 'support']);
        })->get();

        return view('livewire.ticket-kanban', [
            'pendingTickets' => $pendingTickets,
            'approvedTickets' => $approvedTickets,
            'ongoingTickets' => $ongoingTickets,
            'finishedTickets' => $finishedTickets,
            'users' => $users,
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
