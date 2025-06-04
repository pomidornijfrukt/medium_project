<?php

echo "Starting tag relationship test...\n";

require_once 'vendor/autoload.php';
echo "Autoloader loaded...\n";

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
echo "App bootstrapped...\n";

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "Kernel bootstrapped...\n";

use App\Models\User;
use App\Models\Role;
use App\Models\Post;
use App\Models\Tag;

try {
    echo "Testing tag relationships...\n";
      // Create a role first
    $role = Role::firstOrCreate([
        'RoleName' => 'User'
    ], [
        'RoleDescription' => 'Regular user role'
    ]);
    echo "Role created: " . $role->RoleName . "\n";
    
    // Create a test user
    $user = User::firstOrCreate([
        'email' => 'test@example.com'
    ], [
        'UserName' => 'testuser',
        'FirstName' => 'Test',
        'LastName' => 'User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'RoleName' => $role->RoleName
    ]);
    echo "User created: " . $user->UserName . " (UID: " . $user->UID . ")\n";
    
    // Create a test tag
    $tag = Tag::firstOrCreate([
        'TagName' => 'Laravel'
    ], [
        'Description' => 'Laravel framework related posts'
    ]);
    echo "Tag created: " . $tag->TagName . "\n";
    
    // Create a test post
    $post = Post::firstOrCreate([
        'Topic' => 'Test Post with Tags'
    ], [
        'Author' => $user->UID,
        'Topic' => 'Test Post with Tags',
        'Content' => 'This is a test post to verify tag relationships work properly.',
        'Status' => 'published',
        'PostType' => 'original'
    ]);
    echo "Post created: " . $post->Topic . " (PostID: " . $post->PostID . ")\n";
    
    // Test attaching tags to post
    try {
        $post->tags()->attach($tag->TagName);
        echo "Tag attached to post successfully!\n";
        
        // Test retrieving tags from post
        $postTags = $post->tags;
        echo "Post tags count: " . $postTags->count() . "\n";
        foreach ($postTags as $postTag) {
            echo "- Tag: " . $postTag->TagName . " (" . $postTag->Description . ")\n";
        }
        
        // Test retrieving posts from tag
        $tagPosts = $tag->posts;
        echo "Tag posts count: " . $tagPosts->count() . "\n";
        foreach ($tagPosts as $tagPost) {
            echo "- Post: " . $tagPost->Topic . "\n";
        }
        
        echo "\n✅ Tag relationships are working correctly!\n";
        
    } catch (Exception $e) {
        echo "❌ Error with tag relationship: " . $e->getMessage() . "\n";
        echo "Full error: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Full error: " . $e->getTraceAsString() . "\n";
}
