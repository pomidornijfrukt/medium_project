<?php

/*
|--------------------------------------------------------------------------
| Test Script for Linked Posts Functionality
|--------------------------------------------------------------------------
|
| This script creates test data and demonstrates the linked posts functionality.
| You can run this script using Artisan Tinker:
| php artisan tinker --execute="require __DIR__.'/test_linked_posts.php';"
|
*/

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Clear existing test data
Post::where('Topic', 'like', 'Test: %')->delete();
User::where('Username', 'like', 'test%')->delete();

// Create test users
$user1 = User::create([
    'UID' => 'test-user-1',
    'Username' => 'testuser1',
    'Email' => 'test1@example.com',
    'Password' => Hash::make('password123'),
    'Role' => 'user',
    'Status' => 'active'
]);

$user2 = User::create([
    'UID' => 'test-user-2',
    'Username' => 'testuser2',
    'Email' => 'test2@example.com',
    'Password' => Hash::make('password123'),
    'Role' => 'user',
    'Status' => 'active'
]);

echo "Created test users: {$user1->Username} and {$user2->Username}\n";

// Create a main post
$mainPost = Post::create([
    'Author' => $user1->UID,
    'Topic' => 'Test: Main Article',
    'Content' => 'This is the content of the main article.',
    'Status' => 'published',
    'PostType' => 'main'
]);

echo "Created main post: {$mainPost->Topic} (ID: {$mainPost->PostID})\n";

// Create linked posts (comments)
$linkedPost1 = Post::create([
    'Author' => $user2->UID,
    'Topic' => 'Test: First comment',
    'Content' => 'This is the first comment on the main article.',
    'Status' => 'published',
    'PostType' => 'linked',
    'ParentPostID' => $mainPost->PostID
]);

$linkedPost2 = Post::create([
    'Author' => $user1->UID,
    'Topic' => 'Test: Reply from author',
    'Content' => 'Thank you for your comment!',
    'Status' => 'published',
    'PostType' => 'linked',
    'ParentPostID' => $mainPost->PostID
]);

$draftLinkedPost = Post::create([
    'Author' => $user2->UID,
    'Topic' => 'Test: Draft comment',
    'Content' => 'This is a draft comment that should only be visible to admins and the author.',
    'Status' => 'draft',
    'PostType' => 'linked',
    'ParentPostID' => $mainPost->PostID
]);

echo "Created linked posts:\n";
echo "1. {$linkedPost1->Topic} (ID: {$linkedPost1->PostID})\n";
echo "2. {$linkedPost2->Topic} (ID: {$linkedPost2->PostID})\n";
echo "3. {$draftLinkedPost->Topic} (ID: {$draftLinkedPost->PostID}) - Draft\n";

// Fetch the main post with its linked posts
$fetchedPost = Post::with(['author', 'linkedPosts.author'])->find($mainPost->PostID);

echo "\nFetched main post with published linked posts:\n";
echo "Main post: {$fetchedPost->Topic} by {$fetchedPost->author->Username}\n";
echo "Published linked posts count: " . $fetchedPost->linkedPosts->count() . "\n";

foreach ($fetchedPost->linkedPosts as $index => $linkedPost) {
    echo ($index + 1) . ". {$linkedPost->Topic} by {$linkedPost->author->Username}\n";
}

// Fetch all linked posts including drafts
$allLinkedPosts = Post::where('ParentPostID', $mainPost->PostID)->get();

echo "\nAll linked posts (including drafts):\n";
echo "Total linked posts count: " . $allLinkedPosts->count() . "\n";

foreach ($allLinkedPosts as $index => $linkedPost) {
    echo ($index + 1) . ". {$linkedPost->Topic} by {$linkedPost->author->Username} (Status: {$linkedPost->Status})\n";
}

echo "\nTest completed successfully!\n";
echo "You can now use these IDs to test the API endpoints:\n";
echo "Main post ID: {$mainPost->PostID}\n";
echo "Linked post IDs: {$linkedPost1->PostID}, {$linkedPost2->PostID}, {$draftLinkedPost->PostID}\n";
