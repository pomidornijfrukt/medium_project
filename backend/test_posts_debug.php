<?php
// Simple script to test and populate posts

// Include Laravel autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Post;
use App\Models\Role;

try {
    echo "Testing Posts API...\n\n";
    
    // Check if roles exist
    $roleCount = Role::count();
    echo "Roles in database: $roleCount\n";
    
    if ($roleCount == 0) {
        echo "Creating default role...\n";
        Role::create(['RoleName' => 'user']);
    }
    
    // Check if users exist
    $userCount = User::count();
    echo "Users in database: $userCount\n";
    
    if ($userCount == 0) {
        echo "Creating test user...\n";
        $user = User::create([
            'UID' => 'test-user-' . uniqid(),
            'Username' => 'testuser',
            'Email' => 'test@example.com',
            'Password' => bcrypt('password'),
            'RoleID' => 1,
            'Status' => 'active'
        ]);
        echo "Created user: " . $user->Username . "\n";
    } else {
        $user = User::first();
        echo "Using existing user: " . $user->Username . "\n";
    }
    
    // Check if posts exist
    $postCount = Post::count();
    echo "Posts in database: $postCount\n";
    
    if ($postCount == 0) {
        echo "Creating sample posts...\n";
        
        $samplePosts = [
            [
                'Author' => $user->UID,
                'Topic' => 'Welcome to Our Forum',
                'Content' => 'This is the first post on our forum! We are excited to have you here.',
                'Status' => 'published',
                'PostType' => 'main'
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'Getting Started with Laravel',
                'Content' => 'Laravel is an amazing PHP framework that makes web development enjoyable.',
                'Status' => 'published',
                'PostType' => 'main'
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'Vue.js Tips and Tricks',
                'Content' => 'Vue.js is a progressive JavaScript framework perfect for building user interfaces.',
                'Status' => 'published',
                'PostType' => 'main'
            ]
        ];
        
        foreach ($samplePosts as $postData) {
            $post = Post::create($postData);
            echo "Created post: " . $post->Topic . "\n";
        }
    }
    
    // Now test the actual controller
    echo "\nTesting PostController...\n";
    
    $posts = Post::with(['author'])->where('Status', 'published')->paginate(10);
    echo "Found " . $posts->total() . " published posts\n";
    
    foreach ($posts as $post) {
        echo "- " . $post->Topic . " by " . ($post->author ? $post->author->Username : 'Unknown') . "\n";
    }
    
    echo "\nAPI response structure:\n";
    $response = [
        'success' => true,
        'message' => 'Posts retrieved successfully',
        'data' => $posts
    ];
    
    echo "Response keys: " . implode(', ', array_keys($response)) . "\n";
    echo "Data keys: " . implode(', ', array_keys($response['data']->toArray())) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
