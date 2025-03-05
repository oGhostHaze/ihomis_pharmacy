<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TicketPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view-tickets',
            'create-tickets',
            'edit-tickets',
            'manage-tickets',
            'delete-tickets',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // // Assign permissions to existing roles
        // $adminRole = Role::findByName('admin');
        // $developerRole = Role::where('name', 'developer')->first();
        // $supportRole = Role::where('name', 'support')->first();

        // if ($adminRole) {
        //     $adminRole->givePermissionTo($permissions);
        // }

        // if ($developerRole) {
        //     $developerRole->givePermissionTo([
        //         'view-tickets',
        //         'create-tickets',
        //         'edit-tickets',
        //         'manage-tickets',
        //     ]);
        // }

        // if ($supportRole) {
        //     $supportRole->givePermissionTo([
        //         'view-tickets',
        //         'create-tickets',
        //         'edit-tickets',
        //     ]);
        // }

        // // Create a 'user' role if it doesn't exist
        // $userRole = Role::where('name', 'user')->first();
        // if (!$userRole) {
        //     $userRole = Role::create(['name' => 'user']);
        // }

        // // Assign basic ticket permissions to the user role
        // $userRole->givePermissionTo([
        //     'view-tickets',
        //     'create-tickets',
        // ]);
    }
}
