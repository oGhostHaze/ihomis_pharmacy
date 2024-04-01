<?php

namespace App\Http\Livewire\References\Security;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ListPermissions extends Component
{
    use LivewireAlert;

    protected $listeners = ['add_permission', 'remove_permission'];
    public $role_permissions = [], $selected_role;

    public function render()
    {
        $roles = Role::where('name', '<>', 'Super Admin')->get();

        if($this->selected_role){
            $permissions = Permission::whereDoesntHave('roles', function (Builder $query) {
                $query->where('pharm_role_id', $this->selected_role->id);
            })->get();
        }else{
            $permissions = Permission::all();
        }

        return view('livewire.references.security.list-permissions', [
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    public function select_role(Role $role)
    {
        if($this->selected_role && $this->selected_role->id == $role->id){
            $this->reset();
        }else{
            $this->selected_role = $role;
            $this->role_permissions = $role->permissions;
        }
    }

    public function add_permission($permission)
    {
        $this->selected_role->givePermissionTo($permission);
        $this->role_permissions = $this->selected_role->permissions;
        $this->alert('success', 'Permission '.$permission.' has been assinged to '.$this->selected_role->name.'.');
    }

    public function remove_permission($permission)
    {
        $this->selected_role->revokePermissionTo($permission);
        $this->role_permissions = $this->selected_role->permissions;
        $this->alert('success', 'Permission '.$permission.' has been revoked from '.$this->selected_role->name.'.');
    }
}
