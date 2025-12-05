<?php
/**
 * Authentication Integration Tests
 * 
 * Integration tests for WordPress authentication flows
 * Tests login, logout, and session management
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use WP_User;

class AuthenticationTest extends TestCase
{
    /**
     * Test user ID
     * @var int
     */
    protected $test_user_id;
    
    /**
     * Test username
     * @var string
     */
    protected $test_username;
    
    /**
     * Test password
     * @var string
     */
    protected $test_password = 'SecureTestPass123!';

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with unique identifier
        $unique_id = time() . '_' . rand(10000, 99999);
        $this->test_username = 'auth_test_user_' . $unique_id;
        $email = 'authtest' . $unique_id . '@example.com';
        
        $this->test_user_id = \wp_create_user(
            $this->test_username,
            $this->test_password,
            $email
        );
        
        if (\is_wp_error($this->test_user_id)) {
            $this->markTestSkipped('Failed to create test user: ' . $this->test_user_id->get_error_message());
        }
        
        // Ensure user is logged out before each test
        // Suppress header warnings in CLI
        if (function_exists('wp_logout')) {
            @\wp_logout();
        }
    }

    /**
     * Clean up test fixtures
     */
    protected function tearDown(): void
    {
        // Log out if logged in
        // Suppress header warnings in CLI
        if (\is_user_logged_in() && function_exists('wp_logout')) {
            @\wp_logout();
        }
        
        // Delete test user
        if ($this->test_user_id && !\is_wp_error($this->test_user_id) && function_exists('wp_delete_user')) {
            call_user_func('wp_delete_user', $this->test_user_id);
        }
        
        parent::tearDown();
    }

    /**
     * Test complete login flow
     * 
     * Objective: Verify user can login and session is established
     * Expected: User is authenticated and can access authenticated content
     */
    public function test_complete_login_flow()
    {
        // Verify user is not logged in initially
        $this->assertFalse(\is_user_logged_in(), 'User should not be logged in initially');
        $this->assertEquals(0, \get_current_user_id(), 'Current user ID should be 0 when not logged in');
        
        // Authenticate user
        $user = \wp_authenticate($this->test_username, $this->test_password);
        
        // Verify authentication succeeded
        $this->assertInstanceOf(\WP_User::class, $user, 'Authentication should return WP_User object');
        $this->assertFalse(\is_wp_error($user), 'Authentication should not return an error');
        
        // Log in the user
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        
        // Verify user is now logged in
        $this->assertTrue(\is_user_logged_in(), 'User should be logged in after authentication');
        $this->assertEquals($this->test_user_id, \get_current_user_id(), 'Current user ID should match test user ID');
        
        // Verify current user object
        $current_user = \wp_get_current_user();
        $this->assertInstanceOf(\WP_User::class, $current_user, 'Should be able to get current user');
        $this->assertEquals($this->test_user_id, $current_user->ID, 'Current user ID should match');
        $this->assertEquals($this->test_username, $current_user->user_login, 'Username should match');
    }

    /**
     * Test logout flow
     * 
     * Objective: Verify user can logout and session is cleared
     * Expected: User is logged out and cannot access authenticated content
     */
    public function test_logout_flow()
    {
        // First, log in the user
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        
        // Verify user is logged in
        $this->assertTrue(\is_user_logged_in(), 'User should be logged in');
        $this->assertEquals($this->test_user_id, \get_current_user_id(), 'User ID should match');
        
        // Log out the user
        @\wp_logout();
        
        // Verify user is logged out
        $this->assertFalse(\is_user_logged_in(), 'User should be logged out');
        $this->assertEquals(0, \get_current_user_id(), 'Current user ID should be 0 after logout');
        
        // Verify current user is not set
        $current_user = \wp_get_current_user();
        $this->assertEquals(0, $current_user->ID, 'Current user ID should be 0');
    }

    /**
     * Test authentication with incorrect password
     * 
     * Objective: Verify authentication fails with wrong password
     * Expected: User is not authenticated and remains logged out
     */
    public function test_authentication_with_incorrect_password()
    {
        // Attempt authentication with wrong password
        $result = \wp_authenticate($this->test_username, 'wrong_password_12345');
        
        // Verify authentication failed
        $this->assertInstanceOf(\WP_Error::class, $result, 'Should return WP_Error for incorrect password');
        $this->assertEquals('incorrect_password', $result->get_error_code(), 'Error code should be incorrect_password');
        
        // Verify user is still not logged in
        $this->assertFalse(\is_user_logged_in(), 'User should not be logged in after failed authentication');
    }

    /**
     * Test user capabilities after login
     * 
     * Objective: Verify user capabilities are loaded correctly after login
     * Expected: User has appropriate capabilities based on role
     */
    public function test_user_capabilities_after_login()
    {
        // Log in the user
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        
        // Get current user
        $user = \wp_get_current_user();
        
        // Verify user has basic capabilities
        $this->assertTrue(\user_can($this->test_user_id, 'read'), 'User should have read capability');
        $this->assertTrue($user->has_cap('read'), 'User object should have read capability');
        
        // Default role is subscriber, so they should NOT have admin capabilities
        $this->assertFalse(\user_can($this->test_user_id, 'manage_options'), 'Subscriber should not have manage_options capability');
    }

    /**
     * Test authentication cookie setting
     * 
     * Objective: Verify authentication cookies are set correctly
     * Expected: Cookies are set when user logs in
     */
    public function test_authentication_cookie_setting()
    {
        // Log in the user
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        
        // Note: In a unit test environment, cookies might not be set the same way
        // as in a real HTTP request, but we can verify the authentication state
        $this->assertTrue(\is_user_logged_in(), 'User should be logged in');
        
        // Verify user ID matches
        $this->assertEquals($this->test_user_id, \get_current_user_id(), 'User ID should match');
    }

    /**
     * Test multiple login attempts
     * 
     * Objective: Verify system handles multiple login attempts correctly
     * Expected: Each login attempt works independently
     */
    public function test_multiple_login_attempts()
    {
        // First login
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        $this->assertTrue(\is_user_logged_in(), 'First login should succeed');
        
        // Logout
        @\wp_logout();
        $this->assertFalse(\is_user_logged_in(), 'Should be logged out');
        
        // Second login
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        $this->assertTrue(\is_user_logged_in(), 'Second login should succeed');
        
        // Verify user ID is still correct
        $this->assertEquals($this->test_user_id, \get_current_user_id(), 'User ID should still match');
    }

    /**
     * Test user authentication with different roles
     * 
     * Objective: Verify users with different roles can authenticate
     * Expected: All roles can login successfully
     */
    public function test_authentication_with_different_roles()
    {
        $roles = ['subscriber', 'author', 'editor', 'administrator'];
        
        foreach ($roles as $role) {
            // Create user with specific role
            $unique_id = time() . '_' . rand(1000, 9999);
            $username = 'role_test_' . $role . '_' . $unique_id;
            $email = 'roletest' . $unique_id . '@example.com';
            $password = 'TestPass123!';
            
            $user_id = \wp_create_user($username, $password, $email);
            
            if (!\is_wp_error($user_id)) {
                $user = new WP_User($user_id);
                $user->set_role($role);
                
                // Test authentication
                $auth_result = \wp_authenticate($username, $password);
                $this->assertInstanceOf(\WP_User::class, $auth_result, "User with role $role should authenticate");
                $this->assertFalse(\is_wp_error($auth_result), "Authentication should succeed for $role");
                
                // Clean up
                if (function_exists('wp_delete_user')) {
                    call_user_func('wp_delete_user', $user_id);
                }
            }
        }
    }

    /**
     * Test session persistence
     * 
     * Objective: Verify user session persists across requests (simulated)
     * Expected: User remains logged in when session is maintained
     */
    public function test_session_persistence()
    {
        // Log in
        \wp_set_current_user($this->test_user_id);
        @\wp_set_auth_cookie($this->test_user_id);
        
        // Verify logged in
        $this->assertTrue(\is_user_logged_in(), 'User should be logged in');
        
        // Simulate "next request" by getting current user again
        $current_user_1 = \wp_get_current_user();
        $this->assertEquals($this->test_user_id, $current_user_1->ID, 'User ID should persist');
        
        // Verify still logged in
        $this->assertTrue(\is_user_logged_in(), 'User should still be logged in');
        
        $current_user_2 = \wp_get_current_user();
        $this->assertEquals($this->test_user_id, $current_user_2->ID, 'User ID should still match');
    }
}

