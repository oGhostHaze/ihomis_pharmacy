<?php

namespace App\Http\Livewire\References\Users;

use App\Models\Pharmacy\PharmLocation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $search = '';
    public $role_filter = '';
    public $location_filter = '';
    public $status_filter = '';
    public $per_page = 20;
    public $sort_field = 'name';
    public $sort_direction = 'asc';

    public $show_edit_modal = false;
    public $selected_user_id;
    public $selected_user_name = '';
    public $selected_role = '';
    public $selected_location = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'role_filter' => ['except' => ''],
        'location_filter' => ['except' => ''],
        'status_filter' => ['except' => ''],
        'per_page' => ['except' => 20],
        'sort_field' => ['except' => 'name'],
        'sort_direction' => ['except' => 'asc'],
    ];

    public function mount()
    {
        $this->authorizeAccess();
    }

    public function updating($property)
    {
        if (in_array($property, [
            'search',
            'role_filter',
            'location_filter',
            'status_filter',
            'per_page',
        ], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->authorizeAccess();

        $query = User::with(['location', 'roles'])->withTrashed();

        $query->when($this->search, function ($query) {
            $term = trim($this->search);
            $query->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%")
                    ->orWhere('employeeid', 'LIKE', "%{$term}%");
            });
        });

        $query->when($this->location_filter !== '', function ($query) {
            $query->where('pharm_location_id', $this->location_filter);
        });

        $query->when($this->role_filter === 'unassigned', function ($query) {
            $query->doesntHave('roles');
        });

        $query->when($this->role_filter !== '' && $this->role_filter !== 'unassigned', function ($query) {
            $query->whereHas('roles', function ($query) {
                $query->where('name', $this->role_filter);
            });
        });

        if ($this->status_filter === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($this->status_filter === 'inactive') {
            $query->whereNotNull('deleted_at');
        }

        $allowedSorts = ['id', 'name', 'email', 'employeeid', 'created_at'];
        $sortField = in_array($this->sort_field, $allowedSorts, true) ? $this->sort_field : 'name';
        $sortDirection = $this->sort_direction === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) $this->per_page, [10, 20, 50, 100], true) ? (int) $this->per_page : 20;

        return view('livewire.references.users.user-management', [
            'users' => $query->orderBy($sortField, $sortDirection)->paginate($perPage),
            'roles' => Role::where('name', '<>', 'Super Admin')->orderBy('name')->get(),
            'locations' => PharmLocation::orderBy('description')->get(),
        ]);
    }

    public function sortBy($field)
    {
        $this->authorizeAccess();

        if (!in_array($field, ['id', 'name', 'email', 'employeeid', 'created_at'], true)) {
            return;
        }

        if ($this->sort_field === $field) {
            $this->sort_direction = $this->sort_direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort_field = $field;
            $this->sort_direction = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'search',
            'role_filter',
            'location_filter',
            'status_filter',
            'per_page',
            'sort_field',
            'sort_direction',
        ]);
        $this->per_page = 20;
        $this->sort_field = 'name';
        $this->sort_direction = 'asc';
        $this->resetPage();
    }

    public function editUser($userId)
    {
        $this->authorizeAccess();
        $user = User::withTrashed()->with('roles')->findOrFail($userId);

        $this->selected_user_id = $user->id;
        $this->selected_user_name = $user->name;
        $this->selected_role = optional($user->roles->first())->name ?: '';
        $this->selected_location = (string) $user->pharm_location_id;
        $this->show_edit_modal = true;
        $this->resetValidation();
    }

    public function updateUser()
    {
        $this->authorizeAccess();
        $user = User::withTrashed()->findOrFail($this->selected_user_id);

        if ($user->hasRole('Super Admin')) {
            $this->addError('selected_role', 'The Super Admin account is protected.');

            return;
        }

        $roleNames = Role::where('name', '<>', 'Super Admin')->pluck('name')->all();
        $this->validate([
            'selected_role' => ['nullable', Rule::in($roleNames)],
            'selected_location' => ['required'],
        ]);

        if (!PharmLocation::whereKey($this->selected_location)->exists()) {
            $this->addError('selected_location', 'The selected pharmacy location is invalid.');

            return;
        }

        $user->pharm_location_id = $this->selected_location;
        $user->save();
        $user->syncRoles($this->selected_role ? [$this->selected_role] : []);

        $this->show_edit_modal = false;
        $this->alert('success', "Access settings updated for {$user->name}.");
    }

    public function toggleActive($userId)
    {
        $this->authorizeAccess();
        $user = User::withTrashed()->findOrFail($userId);

        if ((int) $user->id === (int) Auth::id()) {
            $this->alert('error', 'You cannot deactivate your own account.');

            return;
        }

        if ($user->hasRole('Super Admin')) {
            $this->alert('error', 'The Super Admin account is protected.');

            return;
        }

        if ($user->trashed()) {
            $user->restore();
            $this->alert('success', "{$user->name} has been reactivated.");
        } else {
            $user->delete();
            $this->alert('success', "{$user->name} has been deactivated.");
        }
    }

    private function authorizeAccess()
    {
        abort_unless(Auth::check() && Auth::user()->can('view-settings'), 403);
    }
}
