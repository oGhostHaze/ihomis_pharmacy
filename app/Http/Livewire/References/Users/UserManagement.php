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
        $users = User::with('location')->where('email', 'LIKE', '%' . $this->search . '%');
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
            $this->reset();
        }
    }
}
