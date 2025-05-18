<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedPostTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->user1 = User::factory()->create(['Role' => 'user']);
        $this->user2 = User::factory()->create(['Role' => 'user']);
        $this->admin = User::factory()->create(['Role' => 'admin']);
    }

    public function test_can_create_linked_post()
    {
        // Create a main post
        $mainPost = Post::create([
            'Author' => $this->user1->UID,
            'Topic' => 'Main Test Post',
            'Content' => 'This is a main post content',
            'Status' => 'published',
            'PostType' => 'main'
        ]);

        // Authenticate as user2
        $this->actingAs($this->user2);

        // Create a linked post
        $response = $this->postJson('/api/posts', [
            'topic' => 'Linked Test Post',
            'content' => 'This is a linked post (comment) content',
            'status' => 'published',
            'parent_post_id' => $mainPost->PostID
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.PostType', 'linked');
        $response->assertJsonPath('data.ParentPostID', $mainPost->PostID);

        // Check that the linked post is visible when fetching the main post
        $showResponse = $this->getJson('/api/posts/' . $mainPost->PostID);
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.linkedPosts.0.Topic', 'Linked Test Post');
    }

    public function test_cannot_link_to_another_linked_post()
    {
        // Create a main post
        $mainPost = Post::create([
            'Author' => $this->user1->UID,
            'Topic' => 'Main Test Post',
            'Content' => 'This is a main post content',
            'Status' => 'published',
            'PostType' => 'main'
        ]);

        // Create a first-level linked post
        $linkedPost = Post::create([
            'Author' => $this->user2->UID,
            'Topic' => 'First Linked Post',
            'Content' => 'This is a first level linked post',
            'Status' => 'published',
            'PostType' => 'linked',
            'ParentPostID' => $mainPost->PostID
        ]);

        // Authenticate as user2
        $this->actingAs($this->user2);

        // Try to create a linked post to the already linked post
        $response = $this->postJson('/api/posts', [
            'topic' => 'Second Linked Post',
            'content' => 'This should fail',
            'status' => 'published',
            'parent_post_id' => $linkedPost->PostID
        ]);

        $response->assertStatus(422);
    }

    public function test_deleting_main_post_deletes_linked_posts()
    {
        // Create a main post
        $mainPost = Post::create([
            'Author' => $this->user1->UID,
            'Topic' => 'Main Test Post',
            'Content' => 'This is a main post content',
            'Status' => 'published',
            'PostType' => 'main'
        ]);

        // Create some linked posts
        $linkedPost1 = Post::create([
            'Author' => $this->user2->UID,
            'Topic' => 'Linked Post 1',
            'Content' => 'This is linked post 1',
            'Status' => 'published',
            'PostType' => 'linked',
            'ParentPostID' => $mainPost->PostID
        ]);

        $linkedPost2 = Post::create([
            'Author' => $this->user2->UID,
            'Topic' => 'Linked Post 2',
            'Content' => 'This is linked post 2',
            'Status' => 'published',
            'PostType' => 'linked',
            'ParentPostID' => $mainPost->PostID
        ]);

        // Authenticate as the author of the main post
        $this->actingAs($this->user1);

        // Delete the main post
        $response = $this->deleteJson('/api/posts/' . $mainPost->PostID);
        $response->assertStatus(200);

        // Check that the main post is soft deleted
        $this->assertEquals('deleted', Post::find($mainPost->PostID)->Status);

        // Check that all linked posts are also soft deleted
        $this->assertEquals('deleted', Post::find($linkedPost1->PostID)->Status);
        $this->assertEquals('deleted', Post::find($linkedPost2->PostID)->Status);
    }

    public function test_get_linked_posts_endpoint()
    {
        // Create a main post
        $mainPost = Post::create([
            'Author' => $this->user1->UID,
            'Topic' => 'Main Test Post',
            'Content' => 'This is a main post content',
            'Status' => 'published',
            'PostType' => 'main'
        ]);

        // Create several linked posts
        for ($i = 1; $i <= 5; $i++) {
            Post::create([
                'Author' => $this->user2->UID,
                'Topic' => "Linked Post $i",
                'Content' => "This is linked post $i",
                'Status' => 'published',
                'PostType' => 'linked',
                'ParentPostID' => $mainPost->PostID
            ]);
        }

        // Create a draft linked post (should only be visible to author and admin)
        $draftPost = Post::create([
            'Author' => $this->user2->UID,
            'Topic' => "Draft Linked Post",
            'Content' => "This is a draft linked post",
            'Status' => 'draft',
            'PostType' => 'linked',
            'ParentPostID' => $mainPost->PostID
        ]);

        // Test as guest (should only see published linked posts)
        $response = $this->getJson('/api/posts/' . $mainPost->PostID . '/linked');
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.data'); // Paginated result
        
        // Test as author of the linked posts (should see all linked posts including drafts)
        $this->actingAs($this->user2);
        $response = $this->getJson('/api/posts/' . $mainPost->PostID . '/linked');
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data.data'); // Paginated result including the draft
    }
}
