<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
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
     * Test Case 1: Successful User Registration
     * 
     * Scenario: User registers with valid data
     * Expected: Account created successfully with token
     */
    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => ['UID', 'Username', 'Email', 'Role'],
                         'access_token',
                         'token_type'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'token_type' => 'Bearer'
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'Username' => 'testuser',
            'Email' => 'test@example.com',
            'Role' => 'member',
            'Status' => 'active'
        ]);
    }

    /**
     * Test Case 2: Registration with Duplicate Email
     * 
     * Scenario: User tries to register with existing email
     * Expected: Validation error returned
     */
    public function test_registration_fails_with_duplicate_email()
    {
        // Create existing user
        User::factory()->create(['Email' => 'existing@example.com']);

        $userData = [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 3: Registration with Invalid Password Confirmation
     * 
     * Scenario: Password and confirmation don't match
     * Expected: Validation error returned
     */
    public function test_registration_fails_with_mismatched_passwords()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 4: Successful Login
     * 
     * Scenario: User logs in with correct credentials
     * Expected: Authentication token returned
     */
    public function test_user_can_login_with_valid_credentials()
    {        $user = User::factory()->create([
            'Email' => 'test@example.com',
            'Password' => 'password123', // Will be hashed by model mutator
            'Status' => 'active'
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user',
                         'access_token',
                         'token_type'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login successful'
                 ]);

        // Verify LastLoginAt was updated
        $this->assertDatabaseHas('users', [
            'UID' => $user->UID,
            'LastLoginAt' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Test Case 5: Login with Invalid Credentials
     * 
     * Scenario: User provides wrong password
     * Expected: Authentication fails with 401 status
     */
    public function test_login_fails_with_invalid_credentials()
    {        User::factory()->create([
            'Email' => 'test@example.com',
            'Password' => 'correctpassword' // Will be hashed by model mutator
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'The provided credentials are incorrect.'
                 ]);
    }

    /**
     * Test Case 6: Login with Banned Account
     * 
     * Scenario: Banned user tries to login
     * Expected: Access denied with 403 status
     */
    public function test_banned_user_cannot_login()
    {        User::factory()->create([
            'Email' => 'banned@example.com',
            'Password' => 'password123', // Will be hashed by model mutator
            'Status' => 'banned'
        ]);

        $loginData = [
            'email' => 'banned@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Your account has been banned. Please contact an administrator.'
                 ]);
    }

    /**
     * Test Case 7: Access Protected Route with Valid Token
     * 
     * Scenario: Authenticated user accesses protected endpoint
     * Expected: User data returned successfully
     */
    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => ['UID', 'Username', 'Email', 'Role']
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'UID' => $user->UID,
                             'Username' => $user->Username
                         ]
                     ]
                 ]);
    }

    /**
     * Test Case 8: Access Protected Route without Token
     * 
     * Scenario: Unauthenticated user tries to access protected endpoint
     * Expected: 401 Unauthorized response
     */
    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /**
     * Test Case 9: Successful Logout
     * 
     * Scenario: Authenticated user logs out
     * Expected: Token is revoked successfully
     */    public function test_authenticated_user_can_logout()
    {
        // Create user and get real token through login
        $user = User::factory()->create([
            'Email' => 'test@example.com',
            'Password' => 'password123'
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.access_token');

        // Logout using the real token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Successfully logged out'
                 ]);        // Verify token was revoked by trying to access protected route
        $this->refreshApplication(); // Clear any cached authentication state
        $protectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/user');
        $protectedResponse->assertStatus(401);
    }

    /**
     * Test Case 10: Token Persistence Across Requests
     * 
     * Scenario: Multiple API calls with same token
     * Expected: All requests should succeed with valid token
     */
    public function test_token_persists_across_multiple_requests()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // First request
        $response1 = $this->getJson('/api/user');
        $response1->assertStatus(200);

        // Second request with same token
        $response2 = $this->getJson('/api/user/profile');
        $response2->assertStatus(200);

        // Both should return same user data
        $this->assertEquals(
            $response1->json('data.user.UID'),
            $response2->json('data.UID')
        );
    }

    /**
     * Test Case 11: Role-Based Access Control
     * 
     * Scenario: Admin user accesses admin-only endpoint
     * Expected: Access granted for admin, denied for regular user
     */
    public function test_role_based_access_control()
    {
        // Test admin access
        $admin = User::factory()->create(['Role' => 'admin']);
        Sanctum::actingAs($admin);

        $adminResponse = $this->getJson('/api/admin/users');
        $adminResponse->assertStatus(200);

        // Test regular user access
        $regularUser = User::factory()->create(['Role' => 'member']);
        Sanctum::actingAs($regularUser);

        $userResponse = $this->getJson('/api/admin/users');
        $userResponse->assertStatus(403);
    }

    /**
     * Test Case 12: Password Security Requirements
     * 
     * Scenario: Registration with weak password
     * Expected: Validation error for password requirements
     */
    public function test_password_security_requirements()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123',  // Too short
            'password_confirmation' => '123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
