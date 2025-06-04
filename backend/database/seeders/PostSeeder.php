<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, let's check if we have any users
        $user = User::first();
        
        if (!$user) {
            // Create a test user if none exists
            $user = User::create([
                'UID' => 'test-user-' . uniqid(),
                'Username' => 'testuser',
                'Email' => 'test@example.com',
                'Password' => bcrypt('password'),
                'RoleID' => 1, // Assuming role 1 exists
                'Status' => 'active'
            ]);
        }

        // Create some sample posts
        $posts = [
            [
                'Author' => $user->UID,
                'Topic' => 'Welcome to Our Forum',
                'Content' => 'This is the first post on our forum! We are excited to have you here. Share your thoughts, ask questions, and connect with the community.',
                'Status' => 'published',
                'PostType' => 'main',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'How to Get Started with Laravel',
                'Content' => 'Laravel is an amazing PHP framework that makes web development a joy. In this post, I will share some tips on how to get started with Laravel development...',
                'Status' => 'published',
                'PostType' => 'main',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2)
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'Vue.js Best Practices',
                'Content' => 'Vue.js is a progressive JavaScript framework that is perfect for building user interfaces. Here are some best practices to follow when working with Vue.js...',
                'Status' => 'published',
                'PostType' => 'main',
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5)
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'Database Design Tips',
                'Content' => 'Good database design is crucial for any application. Here are some tips to help you design better databases for your projects...',
                'Status' => 'published',
                'PostType' => 'main',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay()
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'API Development with Laravel',
                'Content' => 'Building robust APIs is essential for modern web applications. Laravel provides excellent tools for API development including Sanctum for authentication...',
                'Status' => 'published',
                'PostType' => 'main',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2)
            ]
        ];

        foreach ($posts as $postData) {
            Post::create($postData);
        }

        $this->command->info('Sample posts created successfully!');
    }
}
