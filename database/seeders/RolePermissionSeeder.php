<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Must reset cached roles/permissions before seeding
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'manage users',
            'view logs',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->syncPermissions(Permission::all());

        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->syncPermissions(['view posts', 'create posts', 'edit posts']);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions(['view posts']);

        // Create superadmin account
        $admin = User::firstOrCreate(
            ['email' => 'admin@blog.com'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('superadmin');

        // Create one editor and one viewer for testing
        $editor = User::firstOrCreate(
            ['email' => 'editor@blog.com'],
            ['name' => 'Editor User', 'password' => bcrypt('password')]
        );
        $editor->assignRole('editor');

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@blog.com'],
            ['name' => 'Viewer User', 'password' => bcrypt('password')]
        );
        $viewer->assignRole('viewer');
    }
}
