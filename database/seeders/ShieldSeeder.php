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
        // Create Super Admin role if it doesn't exist
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);

        // Create Admin role if it doesn't exist
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // Create basic permissions
        $permissions = [
            'view_dashboard',
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            'page_VoteSeeder',
            'page_VoteManager',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super_admin
        $superAdmin->syncPermissions(Permission::all());

        // Assign basic permissions to admin
        $admin->syncPermissions([
            'view_dashboard',
            'view_any_user',
            'view_user',
            'page_VoteSeeder',
            'page_VoteManager',
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
