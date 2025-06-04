<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Tag;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        $this->createRoles();
        
        // Create default admin user
        $this->createAdminUser();
        
        // Create default tags
        $this->createDefaultTags();
    }
    
    private function createRoles()
    {
        $roles = [
            [
                'RoleName' => 'admin',
                'RoleDescription' => 'Administrator with full access to the forum'
            ],
            [
                'RoleName' => 'moderator', 
                'RoleDescription' => 'Moderator who can manage posts and users'
            ],
            [
                'RoleName' => 'member',
                'RoleDescription' => 'Regular forum member who can create posts, comment, and participate in discussions'
            ]
        ];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['RoleName' => $role['RoleName']],
                ['RoleDescription' => $role['RoleDescription']]
            );
        }
        
        $this->command->info('Default roles created successfully.');
    }
    
    private function createAdminUser()
    {
        $adminUser = User::firstOrCreate(
            ['Email' => 'admin@admin.com'],
            [
                'UID' => (string) Str::uuid(),
                'Username' => 'admin',
                'Password' => 'admin', // Will be hashed by model mutator
                'Role' => 'admin',
                'Status' => 'active',
            ]
        );
        
        $this->command->info('Admin user created successfully.');
        $this->command->info('Email: admin@admin.com');
        $this->command->info('Password: admin');
    }
    
    private function createDefaultTags()
    {
        $tags = [
            [
                'TagName' => 'AI',
                'Description' => 'Artificial Intelligence related posts and discussions'
            ],
            [
                'TagName' => 'suggestion',
                'Description' => 'Feature suggestions and improvements'
            ],
            [
                'TagName' => 'question',
                'Description' => 'Questions and help requests'
            ],
            [
                'TagName' => 'laravel',
                'Description' => 'Laravel framework related posts'
            ],
            [
                'TagName' => 'vue',
                'Description' => 'Vue.js frontend framework discussions'
            ],
            [
                'TagName' => 'php',
                'Description' => 'PHP programming language topics'
            ],
            [
                'TagName' => 'javascript',
                'Description' => 'JavaScript programming language topics'
            ],
            [
                'TagName' => 'tutorial',
                'Description' => 'Tutorials and learning materials'
            ],
            [
                'TagName' => 'discussion',
                'Description' => 'General discussions and conversations'
            ],
            [
                'TagName' => 'news',
                'Description' => 'News and announcements'
            ]
        ];
        
        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['TagName' => $tag['TagName']],
                ['Description' => $tag['Description']]
            );
        }
        
        $this->command->info('Default tags created successfully.');
    }
}
