<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRoles = [
            [
                'RoleName' => 'admin',
                'RoleDescription' => 'Administrator with full access to manage the forum, users, and content'
            ],
            [
                'RoleName' => 'member',
                'RoleDescription' => 'Regular forum member who can create posts, comment, and participate in discussions'
            ],
            [
                'RoleName' => 'moderator',
                'RoleDescription' => 'Moderator who can manage posts, comments, and help maintain forum quality'
            ],
            [
                'RoleName' => 'guest',
                'RoleDescription' => 'Guest user with limited read-only access to public content'
            ]
        ];

        foreach ($defaultRoles as $role) {
            Role::firstOrCreate(
                ['RoleName' => $role['RoleName']],
                $role
            );
        }

        $this->command->info('Default roles created successfully!');
    }
}
