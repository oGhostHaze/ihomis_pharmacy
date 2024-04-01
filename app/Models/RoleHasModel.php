<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleHasModel extends Model
{
    use HasFactory;

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'pharm_permission_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'pharm_role_id');
    }
}
