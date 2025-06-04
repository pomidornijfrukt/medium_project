<?php
// Script to check and fix post statuses

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

try {
    echo "Checking post statuses...\n\n";
    
    $allPosts = Post::all();
    echo "Total posts: " . $allPosts->count() . "\n";
    
    foreach ($allPosts as $post) {
        echo "Post ID: " . $post->PostID . "\n";
        echo "Topic: " . $post->Topic . "\n";
        echo "Status: " . $post->Status . "\n";
        echo "Author: " . $post->Author . "\n";
        echo "Created: " . $post->created_at . "\n";
        echo "---\n";
        
        // Update status to published if it's not
        if ($post->Status !== 'published') {
            echo "Updating post status to published...\n";
            $post->Status = 'published';
            $post->save();
            echo "Updated!\n";
        }
    }
    
    // Create additional sample posts if needed
    $publishedCount = Post::where('Status', 'published')->count();
    echo "\nPublished posts: $publishedCount\n";
    
    if ($publishedCount < 3) {
        echo "Creating additional sample posts...\n";
        $user = \App\Models\User::first();
        
        $additionalPosts = [
            [
                'Author' => $user->UID,
                'Topic' => 'Advanced Laravel Techniques',
                'Content' => 'Explore advanced Laravel features including Eloquent relationships, query optimization, and custom middleware.',
                'Status' => 'published',
                'PostType' => 'main'
            ],
            [
                'Author' => $user->UID,
                'Topic' => 'Frontend Development Best Practices',
                'Content' => 'Learn about modern frontend development practices including component architecture and state management.',
                'Status' => 'published',
                'PostType' => 'main'
            ]
        ];
        
        foreach ($additionalPosts as $postData) {
            $existing = Post::where('Topic', $postData['Topic'])->first();
            if (!$existing) {
                $post = Post::create($postData);
                echo "Created: " . $post->Topic . "\n";
            }
        }
    }
    
    echo "\nFinal check - Published posts: " . Post::where('Status', 'published')->count() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
