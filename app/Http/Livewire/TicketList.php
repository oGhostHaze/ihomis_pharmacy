<?php

namespace App\Http\Livewire;

use App\Models\Ticket;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketList extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    // Filters
    public $search = '';
    public $status = '';
    public $priority = '';
    public $type = '';
    public $dateRange = '';
    public $assignee = '';

    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'priority' => ['except' => ''],
        'type' => ['except' => ''],
        'dateRange' => ['except' => ''],
        'assignee' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->authorize('view-tickets');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->priority = '';
        $this->type = '';
        $this->dateRange = '';
        $this->assignee = '';
    }

    public function render()
    {
        $query = Ticket::query()
            ->with(['reporter', 'assignee'])
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('reporter', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('assignee', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status, function ($q) {
                return $q->where('status', $this->status);
            })
            ->when($this->priority, function ($q) {
                return $q->where('priority', $this->priority);
            })
            ->when($this->type, function ($q) {
                return $q->where('type', $this->type);
            })
            ->when($this->dateRange, function ($q) {
                list($start, $end) = explode(' to ', $this->dateRange);
                return $q->whereBetween('created_at', [$start, $end]);
            })
            ->when($this->assignee, function ($q) {
                if ($this->assignee === 'unassigned') {
                    return $q->whereNull('assignee_id');
                }
                return $q->where('assignee_id', $this->assignee);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $tickets = $query->paginate(10);

        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'developer', 'support']);
        })->get();

        return view('livewire.ticket-list', [
            'tickets' => $tickets,
            'users' => $users,
            'statusOptions' => [
                Ticket::STATUS_PENDING => 'Pending',
                Ticket::STATUS_APPROVED => 'Approved',
                Ticket::STATUS_ONGOING => 'Ongoing',
                Ticket::STATUS_FINISHED => 'Finished',
            ],
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
