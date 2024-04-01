<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $arrayOfPermissionNames = [
            'view-patients',
            'view-prescriptions',
            'view-stocks',
            'filter-stocks-location',
            'view-iotrans',
            'view-iotrans-limited',
            'view-deliveries',
            'view-eps',
            'view-references',

            'view-reports',
            'request-drugs',
            'issue-requested-drugs',
            'receive-requested-drugs',
        ];
        $permissions = collect($arrayOfPermissionNames)->map(function ($permission) {
            return ['name' => $permission, 'guard_name' => 'web'];
        });

        Permission::insert($permissions->toArray());

        Role::create(['name' => 'warehouse', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view-stocks',
                'filter-stocks-location',
                'view-iotrans',
                'view-deliveries',
                'view-references',
            ]);

        Role::create(['name' => 'dispensing', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view-patients',
                'view-prescriptions',
                'view-stocks',
                'view-iotrans-limited',
                'request-drugs',
            ]);


        // // create permissions
        // Permission::create(['name' => 'edit articles']);
        // Permission::create(['name' => 'delete articles']);
        // Permission::create(['name' => 'publish articles']);
        // Permission::create(['name' => 'unpublish articles']);

        // // create roles and assign created permissions

        // // this can be done as separate statements
        // $role = Role::create(['name' => 'writer']);
        // $role->givePermissionTo('edit articles');

        // // or may be done by chaining
        // $role = Role::create(['name' => 'moderator'])
        //     ->givePermissionTo(['publish articles', 'unpublish articles']);

        // $role = Role::create(['name' => 'super-admin']);
        // $role->givePermissionTo(Permission::all());
    }
}
