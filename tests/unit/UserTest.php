<?php
/**
 * User Test Cases
 * 
 * Tests for WordPress user authentication and management functions
 * 
 * Test Cases Covered:
 * - TC-BE-001: User Authentication - Login (Valid Credentials)
 * - TC-BE-002: User Authentication - Invalid Credentials
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * Test user ID created during setUp
     * @var int
     */
    protected $test_user_id;
    
    /**
     * Test username
     * @var string
     */
    protected $test_username = 'test_user_' . __CLASS__;
    
    /**
     * Test email
     * @var string
     */
    protected $test_email = 'testuser@example.com';
    
    /**
     * Test password
     * @var string
     */
    protected $test_password = 'SecureTestPass123!';

    /**
     * Set up test fixtures
     * Runs before each test method
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user before each test with unique identifier
        $unique_id = time() . '_' . rand(10000, 99999);
        $this->test_user_id = \wp_create_user(
            $this->test_username . '_' . $unique_id,
            $this->test_password,
            $this->test_email . '_' . $unique_id
        );
        
        // Verify user was created
        if (\is_wp_error($this->test_user_id)) {
            $this->markTestSkipped('Failed to create test user: ' . $this->test_user_id->get_error_message());
        }
    }

    /**
     * Clean up test fixtures
     * Runs after each test method
     */
    protected function tearDown(): void
    {
        // Delete test user if it exists
        if ($this->test_user_id && !\is_wp_error($this->test_user_id)) {
            $user = \get_user_by('id', $this->test_user_id);
            if ($user && function_exists('wp_delete_user')) {
                call_user_func('wp_delete_user', $this->test_user_id);
            }
        }
        
        // Clean up any other test data
        // Note: wp_logout() sends headers which causes warnings in CLI, so we skip it
        // The user cleanup above is sufficient
        
        parent::tearDown();
    }

    /**
     * TC-BE-001: Test user authentication with valid credentials
     * 
     * Objective: Verify user can login with valid credentials
     * Expected: User is authenticated and session is established
     */
    public function test_user_authentication_with_valid_credentials()
    {
        // Get the test user
        $user = \get_user_by('id', $this->test_user_id);
        $this->assertInstanceOf(\WP_User::class, $user, 'Test user should exist');
        
        // Authenticate with valid credentials
        $authenticated_user = \wp_authenticate($user->user_login, $this->test_password);
        
        // Verify authentication succeeded
        $this->assertInstanceOf(\WP_User::class, $authenticated_user, 'Authentication should succeed with valid credentials');
        $this->assertEquals($this->test_user_id, $authenticated_user->ID, 'Authenticated user ID should match test user ID');
        $this->assertFalse(\is_wp_error($authenticated_user), 'Authentication should not return an error');
    }

    /**
     * TC-BE-002: Test user authentication with invalid password
     * 
     * Objective: Verify login fails with invalid credentials
     * Expected: Authentication fails with appropriate error
     */
    public function test_user_authentication_with_invalid_password()
    {
        // Get the test user
        $user = \get_user_by('id', $this->test_user_id);
        $this->assertInstanceOf(\WP_User::class, $user, 'Test user should exist');
        
        // Attempt authentication with invalid password
        $authenticated_user = \wp_authenticate($user->user_login, 'wrong_password_123');
        
        // Verify authentication failed
        $this->assertInstanceOf(\WP_Error::class, $authenticated_user, 'Authentication should fail with invalid password');
        $this->assertEquals('incorrect_password', $authenticated_user->get_error_code(), 'Error code should be incorrect_password');
    }

    /**
     * Test user authentication with non-existent username
     * 
     * Objective: Verify login fails with non-existent username
     * Expected: Authentication fails with appropriate error
     */
    public function test_user_authentication_with_nonexistent_username()
    {
        // Attempt authentication with non-existent username
        $authenticated_user = \wp_authenticate('nonexistent_user_' . time() . '_' . rand(10000, 99999), 'some_password');
        
        // Verify authentication failed
        $this->assertInstanceOf(\WP_Error::class, $authenticated_user, 'Authentication should fail with non-existent username');
        // WordPress may return different error codes, so check for any error
        $this->assertNotEmpty($authenticated_user->get_error_code(), 'Should have an error code');
    }

    /**
     * Test user creation
     * 
     * Objective: Verify new user can be created
     * Expected: User is created successfully with valid ID
     */
    public function test_user_creation()
    {
        $username = 'new_user_' . time() . '_' . rand(10000, 99999);
        $email = 'newuser' . time() . '_' . rand(10000, 99999) . '@example.com';
        $password = 'NewUserPass123!';
        
        // Create a new user
        $user_id = \wp_create_user($username, $password, $email);
        
        // Verify user was created
        $this->assertIsInt($user_id, 'User ID should be an integer');
        $this->assertGreaterThan(0, $user_id, 'User ID should be greater than 0');
        $this->assertFalse(\is_wp_error($user_id), 'User creation should not return an error');
        
        // Verify user exists
        $user = \get_user_by('id', $user_id);
        $this->assertInstanceOf(\WP_User::class, $user, 'Created user should exist');
        $this->assertEquals($username, $user->user_login, 'Username should match');
        $this->assertEquals($email, $user->user_email, 'Email should match');
        
        // Clean up - wp_delete_user should be available from WordPress
        // Use call_user_func to ensure function is found
        if (function_exists('wp_delete_user')) {
            call_user_func('wp_delete_user', $user_id);
        }
    }

    /**
     * Test user retrieval by ID
     * 
     * Objective: Verify user can be retrieved by ID
     * Expected: User object is returned correctly
     */
    public function test_get_user_by_id()
    {
        $user = \get_user_by('id', $this->test_user_id);
        
        $this->assertInstanceOf(\WP_User::class, $user, 'Should return WP_User object');
        $this->assertEquals($this->test_user_id, $user->ID, 'User ID should match');
    }

    /**
     * Test user retrieval by login
     * 
     * Objective: Verify user can be retrieved by username
     * Expected: User object is returned correctly
     */
    public function test_get_user_by_login()
    {
        // Get the created user's actual username
        $created_user = \get_user_by('id', $this->test_user_id);
        if ($created_user) {
            $user_by_login = \get_user_by('login', $created_user->user_login);
            $this->assertInstanceOf(\WP_User::class, $user_by_login, 'Should return WP_User object by login');
            $this->assertEquals($this->test_user_id, $user_by_login->ID, 'User ID should match');
        } else {
            $this->markTestSkipped('Test user not found');
        }
    }

    /**
     * Test user deletion
     * 
     * Objective: Verify user can be deleted
     * Expected: User is deleted successfully
     */
    public function test_user_deletion()
    {
        // Create a temporary user for deletion test with unique identifier
        $unique_id = time() . '_' . rand(10000, 99999);
        $temp_user_id = \wp_create_user('temp_user_' . $unique_id, 'temp_pass', 'temp' . $unique_id . '@example.com');
        
        if (!\is_wp_error($temp_user_id)) {
            // Delete the user - use call_user_func to ensure function is found
            if (function_exists('wp_delete_user')) {
                $deleted = call_user_func('wp_delete_user', $temp_user_id);
            } else {
                $this->markTestSkipped('wp_delete_user function not available');
                return;
            }
            
            // Verify user was deleted
            $this->assertInstanceOf(\WP_User::class, $deleted, 'Deletion should return WP_User object');
            
            // Verify user no longer exists
            $user = \get_user_by('id', $temp_user_id);
            $this->assertFalse($user, 'Deleted user should not exist');
        } else {
            $this->markTestSkipped('Failed to create test user for deletion: ' . $temp_user_id->get_error_message());
        }
    }

    /**
     * Test password hashing
     * 
     * Objective: Verify password is hashed correctly
     * Expected: Password hash is generated and verification works
     */
    public function test_password_hashing()
    {
        $plain_password = 'TestPassword123!';
        
        // Verify functions exist
        if (!function_exists('wp_hash_password') || !function_exists('wp_check_password')) {
            $this->markTestSkipped('Password hashing functions not available');
            return;
        }
        
        // Hash the password
        $hashed_password = \wp_hash_password($plain_password);
        
        // Verify hash was generated
        $this->assertIsString($hashed_password, 'Password hash should be a string');
        $this->assertNotEmpty($hashed_password, 'Password hash should not be empty');
        $this->assertNotEquals($plain_password, $hashed_password, 'Hash should not equal plain password');
        
        // Verify password check works
        $check = \wp_check_password($plain_password, $hashed_password);
        $this->assertTrue($check, 'Password check should pass with correct password');
        
        // Verify incorrect password fails
        $check_wrong = \wp_check_password('wrong_password', $hashed_password);
        $this->assertFalse($check_wrong, 'Password check should fail with incorrect password');
    }
}

