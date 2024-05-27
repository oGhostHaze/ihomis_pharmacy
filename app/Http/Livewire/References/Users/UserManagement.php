<?php

namespace App\Http\Livewire\References\Users;

use App\Models\User;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['update_role'];

    public $search;
    public $role_name;

    public function render()
    {
        $users = User::with('location')->where('email', 'LIKE', '%' . $this->search . '%')->orWhere('name', 'LIKE', '%' . $this->search . '%')->withTrashed();
        $roles = Role::where('name', '<>', 'Super Admin')->get();

        return view('livewire.references.users.user-management', [
            'users' => $users->paginate(20),
            'roles' => $roles,
        ]);
    }

    public function update_role(User $user)
    {
        // $user->assignRole($this->role_name);

        if ($user->syncRoles($this->role_name)) {
            $this->alert('success', $this->role_name . ' role assigned to user ' . $user->name);
        }
    }

    public function toggle_active($user_id)
    {
        if ($user = User::find($user_id)) {
            $user->delete();
            $this->alert('success', 'User has been deactivated!');
        } else {
            User::withTrashed()->find($user_id)->restore();
            $this->alert('success', 'User has been reactivated!');
        }
    }
}
