<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin role
        $superAdmin = Role::create(['name' => 'super_admin']);

        // Create Admin role
        $admin = Role::create(['name' => 'admin']);

        // Create basic permissions
        $permissions = [
            'view_dashboard',
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to super_admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign basic permissions to admin
        $admin->givePermissionTo([
            'view_dashboard',
            'view_any_user',
            'view_user',
        ]);

        // Create a super admin user if it doesn't exist
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $superAdminUser->assignRole('super_admin');
    }
}
