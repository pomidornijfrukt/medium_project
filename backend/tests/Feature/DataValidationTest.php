<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class DataValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create default roles
        Role::create(['RoleName' => 'admin', 'RoleDescription' => 'Administrator']);
        Role::create(['RoleName' => 'member', 'RoleDescription' => 'Regular member']);
        Role::create(['RoleName' => 'moderator', 'RoleDescription' => 'Moderator']);
    }

    /**
     * Test Case 1: Post Creation - Valid Data
     * 
     * Scenario: User creates post with valid data
     * Expected: Post created successfully
     */
    public function test_post_creation_with_valid_data()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postData = [
            'topic' => 'Kā mācīties Laravel',
            'content' => 'Laravel ir lielisks PHP ietvars, kas atvieglo tīmekļa izstrādi.',
            'status' => 'published',
            'tags' => ['laravel', 'php', 'programmēšana']
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'PostID',
                         'Topic',
                         'Content',
                         'Status',
                         'Author'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'Topic' => 'Kā mācīties Laravel',
                         'Content' => 'Laravel ir lielisks PHP ietvars, kas atvieglo tīmekļa izstrādi.',
                         'Status' => 'published',
                         'Author' => $user->UID
                     ]
                 ]);
    }

    /**
     * Test Case 2: Post Creation - Empty Topic
     * 
     * Scenario: User tries to create post without topic
     * Expected: Validation error for required topic field
     */
    public function test_post_creation_fails_with_empty_topic()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postData = [
            'topic' => '',
            'content' => 'Šis ir tests bez virsraksta.',
            'status' => 'published'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['topic']);
    }

    /**
     * Test Case 3: Post Creation - Topic Too Long
     * 
     * Scenario: User creates post with topic exceeding 255 characters
     * Expected: Validation error for topic length
     */
    public function test_post_creation_fails_with_long_topic()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $longTopic = str_repeat('A', 256); // 256 characters

        $postData = [
            'topic' => $longTopic,
            'content' => 'Šis ir tests ar pārāk garu virsrakstu.',
            'status' => 'published'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['topic']);
    }

    /**
     * Test Case 4: Post Creation - Empty Content
     * 
     * Scenario: User tries to create post without content
     * Expected: Validation error for required content field
     */
    public function test_post_creation_fails_with_empty_content()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postData = [
            'topic' => 'Tests bez satura',
            'content' => '',
            'status' => 'published'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['content']);
    }

    /**
     * Test Case 5: Post Creation - Invalid Status
     * 
     * Scenario: User creates post with invalid status
     * Expected: Validation error for status field
     */
    public function test_post_creation_fails_with_invalid_status()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postData = [
            'topic' => 'Tests ar nepareizu statusu',
            'content' => 'Šis ir tests ar nepareizu statusu.',
            'status' => 'invalid_status'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test Case 6: Post Creation - Tag Name Too Long
     * 
     * Scenario: User creates post with tag name exceeding 50 characters
     * Expected: Validation error for tag length
     */
    public function test_post_creation_fails_with_long_tag_name()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $longTag = str_repeat('A', 51); // 51 characters

        $postData = [
            'topic' => 'Tests ar pārāk garu tagu',
            'content' => 'Šis ir tests ar pārāk garu tagu.',
            'status' => 'published',
            'tags' => [$longTag]
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tags.0']);
    }

    /**
     * Test Case 7: User Profile Update - Valid Data
     * 
     * Scenario: User updates profile with valid username and email
     * Expected: Profile updated successfully
     */
    public function test_user_profile_update_with_valid_data()
    {
        $user = User::factory()->create([
            'Username' => 'old_username',
            'Email' => 'old@example.com'
        ]);
        Sanctum::actingAs($user);

        $updateData = [
            'username' => 'new_username',
            'email' => 'new@example.com'
        ];

        $response = $this->putJson('/api/user/profile', $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile updated successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'UID' => $user->UID,
            'Username' => 'new_username',
            'Email' => 'new@example.com'
        ]);
    }

    /**
     * Test Case 8: User Profile Update - Email Already Exists
     * 
     * Scenario: User tries to update email to one that already exists
     * Expected: Validation error for email uniqueness
     */
    public function test_user_profile_update_fails_with_existing_email()
    {
        $existingUser = User::factory()->create(['Email' => 'existing@example.com']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'email' => 'existing@example.com'
        ];

        $response = $this->putJson('/api/user/profile', $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 9: User Profile Update - Username Already Exists
     * 
     * Scenario: User tries to update username to one that already exists
     * Expected: Validation error for username uniqueness
     */
    public function test_user_profile_update_fails_with_existing_username()
    {
        $existingUser = User::factory()->create(['Username' => 'existing_user']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'username' => 'existing_user'
        ];

        $response = $this->putJson('/api/user/profile', $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }

    /**
     * Test Case 10: User Profile Update - Invalid Email Format
     * 
     * Scenario: User tries to update email with invalid format
     * Expected: Validation error for email format
     */
    public function test_user_profile_update_fails_with_invalid_email()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'email' => 'invalid-email-format'
        ];

        $response = $this->putJson('/api/user/profile', $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 11: Password Update - Valid Data
     * 
     * Scenario: User updates password with valid current password and new password
     * Expected: Password updated successfully
     */
    public function test_password_update_with_valid_data()
    {
        $user = User::factory()->create(['Password' => 'old_password']);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'old_password',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123'
        ];

        $response = $this->putJson('/api/user/password', $passwordData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Password updated successfully'
                 ]);
    }

    /**
     * Test Case 12: Password Update - Wrong Current Password
     * 
     * Scenario: User tries to update password with wrong current password
     * Expected: 401 error for incorrect current password
     */
    public function test_password_update_fails_with_wrong_current_password()
    {
        $user = User::factory()->create(['Password' => 'correct_password']);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'wrong_password',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123'
        ];

        $response = $this->putJson('/api/user/password', $passwordData);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Current password is incorrect'
                 ]);
    }

    /**
     * Test Case 13: Password Update - Password Too Short
     * 
     * Scenario: User tries to update password with less than 8 characters
     * Expected: Validation error for password length
     */
    public function test_password_update_fails_with_short_password()
    {
        $user = User::factory()->create(['Password' => 'old_password']);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'old_password',
            'password' => '123',
            'password_confirmation' => '123'
        ];

        $response = $this->putJson('/api/user/password', $passwordData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 14: Password Update - Password Confirmation Mismatch
     * 
     * Scenario: User tries to update password but confirmation doesn't match
     * Expected: Validation error for password confirmation
     */
    public function test_password_update_fails_with_mismatched_confirmation()
    {
        $user = User::factory()->create(['Password' => 'old_password']);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'old_password',
            'password' => 'new_password123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->putJson('/api/user/password', $passwordData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 15: Tag Creation - Valid Data
     * 
     * Scenario: Admin creates tag with valid name and description
     * Expected: Tag created successfully
     */
    public function test_tag_creation_with_valid_data()
    {
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $tagData = [
            'name' => 'vue-js',
            'description' => 'Vue.js ietvara saistītie ieraksti'
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tag created successfully',
                     'data' => [
                         'TagName' => 'vue-js',
                         'Description' => 'Vue.js ietvara saistītie ieraksti'
                     ]
                 ]);
    }

    /**
     * Test Case 16: Tag Creation - Name Too Long
     * 
     * Scenario: Admin tries to create tag with name exceeding 50 characters
     * Expected: Validation error for tag name length
     */
    public function test_tag_creation_fails_with_long_name()
    {
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $longTagName = str_repeat('A', 51); // 51 characters

        $tagData = [
            'name' => $longTagName,
            'description' => 'Apraksts'
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test Case 17: Tag Creation - Duplicate Name
     * 
     * Scenario: Admin tries to create tag with name that already exists
     * Expected: Validation error for tag name uniqueness
     */
    public function test_tag_creation_fails_with_duplicate_name()
    {
        Tag::create(['TagName' => 'existing-tag', 'Description' => 'Existing']);
        
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $tagData = [
            'name' => 'existing-tag',
            'description' => 'Mēģinājums dublēt'
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test Case 18: Tag Creation - Unauthorized User
     * 
     * Scenario: Regular member tries to create tag
     * Expected: 403 Forbidden error
     */
    public function test_tag_creation_fails_for_unauthorized_user()
    {
        $member = User::factory()->create(['Role' => 'member']);
        Sanctum::actingAs($member);

        $tagData = [
            'name' => 'unauthorized-tag',
            'description' => 'Neautorizēts lietotājs'
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'You are not authorized to create tags'
                 ]);
    }

    /**
     * Test Case 19: Tag Update - Valid Data
     * 
     * Scenario: Admin updates tag description with valid data
     * Expected: Tag description updated successfully
     */
    public function test_tag_update_with_valid_data()
    {
        $tag = Tag::create(['TagName' => 'test-tag', 'Description' => 'Vecais apraksts']);
        
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $updateData = [
            'description' => 'Jauns uzlabots apraksts'
        ];

        $response = $this->putJson('/api/tags/test-tag', $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Tag updated successfully',
                     'data' => [
                         'TagName' => 'test-tag',
                         'Description' => 'Jauns uzlabots apraksts'
                     ]
                 ]);
    }

    /**
     * Test Case 20: Tag Update - Description Too Long
     * 
     * Scenario: Admin tries to update tag with description exceeding 255 characters
     * Expected: Validation error for description length
     */
    public function test_tag_update_fails_with_long_description()
    {
        $tag = Tag::create(['TagName' => 'test-tag', 'Description' => 'Apraksts']);
        
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $longDescription = str_repeat('A', 256); // 256 characters

        $updateData = [
            'description' => $longDescription
        ];

        $response = $this->putJson('/api/tags/test-tag', $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['description']);
    }

    /**
     * Test Case 21: Admin User Role Update - Valid Data
     * 
     * Scenario: Admin updates user role with valid role name
     * Expected: User role updated successfully
     */
    public function test_admin_user_role_update_with_valid_data()
    {
        $targetUser = User::factory()->create(['Role' => 'member']);
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);        $roleData = [
            'role' => 'moderator'
        ];

        $response = $this->putJson("/api/admin/users/{$targetUser->UID}/role", $roleData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User role updated successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'UID' => $targetUser->UID,
            'Role' => 'moderator'
        ]);
    }

    /**
     * Test Case 22: Admin User Role Update - Invalid Role
     * 
     * Scenario: Admin tries to update user role with non-existent role
     * Expected: Validation error for invalid role
     */
    public function test_admin_user_role_update_fails_with_invalid_role()
    {
        $targetUser = User::factory()->create(['Role' => 'member']);
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);        $roleData = [
            'role' => 'nonexistent_role'
        ];

        $response = $this->putJson("/api/admin/users/{$targetUser->UID}/role", $roleData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);
    }

    /**
     * Test Case 23: Admin User Status Update - Valid Status
     * 
     * Scenario: Admin updates user status with valid status value
     * Expected: User status updated successfully
     */
    public function test_admin_user_status_update_with_valid_status()
    {
        $targetUser = User::factory()->create(['Status' => 'active']);
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);        $statusData = [
            'status' => 'banned'
        ];

        $response = $this->putJson("/api/admin/users/{$targetUser->UID}/status", $statusData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User status updated successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'UID' => $targetUser->UID,
            'Status' => 'banned'
        ]);
    }

    /**
     * Test Case 24: Admin User Status Update - Invalid Status
     * 
     * Scenario: Admin tries to update user status with invalid status value
     * Expected: Validation error for invalid status
     */
    public function test_admin_user_status_update_fails_with_invalid_status()
    {
        $targetUser = User::factory()->create(['Status' => 'active']);
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);        $statusData = [
            'status' => 'invalid_status'
        ];

        $response = $this->putJson("/api/admin/users/{$targetUser->UID}/status", $statusData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test Case 25: Post Update - Valid Data
     * 
     * Scenario: User updates own post with valid data
     * Expected: Post updated successfully
     */
    public function test_post_update_with_valid_data()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['Author' => $user->UID]);
        Sanctum::actingAs($user);

        $updateData = [
            'topic' => 'Atjaunināts virsraksts',
            'content' => 'Atjaunināts saturs ar jaunām idejām.',
            'status' => 'draft',
            'tags' => ['updated', 'test']
        ];

        $response = $this->putJson("/api/posts/{$post->PostID}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Post updated successfully',
                     'data' => [
                         'Topic' => 'Atjaunināts virsraksts',
                         'Content' => 'Atjaunināts saturs ar jaunām idejām.',
                         'Status' => 'draft'
                     ]
                 ]);
    }

    /**
     * Test Case 26: Post Update - Unauthorized User
     * 
     * Scenario: User tries to update another user's post
     * Expected: 403 Forbidden error
     */
    public function test_post_update_fails_for_unauthorized_user()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['Author' => $owner->UID]);
        Sanctum::actingAs($otherUser);

        $updateData = [
            'topic' => 'Mēģinājums mainīt',
            'content' => 'Neautorizēts mēģinājums.'
        ];

        $response = $this->putJson("/api/posts/{$post->PostID}", $updateData);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized to update this post'
                 ]);
    }

    /**
     * Test Case 27: Complex Validation - Multiple Fields Invalid
     * 
     * Scenario: User submits post with multiple validation errors
     * Expected: Multiple validation errors returned
     */
    public function test_post_creation_with_multiple_validation_errors()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $invalidPostData = [
            'topic' => '', // Empty - required
            'content' => '', // Empty - required
            'status' => 'invalid_status', // Invalid enum
            'tags' => [str_repeat('A', 51)] // Tag too long
        ];

        $response = $this->postJson('/api/posts', $invalidPostData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['topic', 'content', 'status', 'tags.0']);
    }

    /**
     * Test Case 28: Edge Case - Maximum Valid Lengths
     * 
     * Scenario: User creates post with maximum allowed lengths
     * Expected: Post created successfully
     */
    public function test_post_creation_with_maximum_valid_lengths()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $maxLengthTopic = str_repeat('A', 255); // Exactly 255 characters
        $maxLengthTag = str_repeat('B', 50); // Exactly 50 characters

        $postData = [
            'topic' => $maxLengthTopic,
            'content' => 'Valīds saturs ar maksimālo garumu.',
            'status' => 'published',
            'tags' => [$maxLengthTag]
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'Topic' => $maxLengthTopic,
                         'Status' => 'published'
                     ]
                 ]);
    }
}
