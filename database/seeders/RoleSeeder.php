<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define roles
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $doctorRole = Role::create(['name' => 'doctor', 'guard_name' => 'api']);
        $receptionistRole = Role::create(['name' => 'receptionist', 'guard_name' => 'api']);

        // Define permissions
        Permission::create(['name' => 'manage appointments', 'guard_name' => 'api']);
        Permission::create(['name' => 'write prescriptions', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage patients', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage clinic', 'guard_name' => 'api']);

        // Assign permissions to roles
        $adminRole->givePermissionTo([
            'manage appointments',
            'write prescriptions',
            'manage patients',
            'manage clinic',
        ]);

        $doctorRole->givePermissionTo([
            'manage appointments',
            'write prescriptions',
            'manage patients',
        ]);

        $receptionistRole->givePermissionTo([
            'manage appointments',
            'manage patients',
        ]);
    }
}
