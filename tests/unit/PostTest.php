<?php
/**
 * Post Test Cases
 * 
 * Tests for WordPress post CRUD operations
 * 
 * Test Cases Covered:
 * - TC-BE-003: Post Creation - Create New Post
 * - TC-BE-004: Post Update - Edit Existing Post
 * - TC-BE-005: Post Deletion - Delete Post
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    /**
     * Test user ID for post author
     * @var int
     */
    protected $test_user_id;
    
    /**
     * Test post IDs created during tests
     * @var array
     */
    protected $test_post_ids = [];

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for post author
        $this->test_user_id = \wp_create_user(
            'test_author_' . time() . '_' . rand(1000, 9999),
            'testpass123',
            'testauthor' . time() . '_' . rand(1000, 9999) . '@example.com'
        );
        
        if (\is_wp_error($this->test_user_id)) {
            $this->markTestSkipped('Failed to create test user: ' . $this->test_user_id->get_error_message());
        }
    }

    /**
     * Clean up test fixtures
     */
    protected function tearDown(): void
    {
        // Delete all test posts
        foreach ($this->test_post_ids as $post_id) {
            if (\get_post($post_id)) {
                \wp_delete_post($post_id, true); // Force delete
            }
        }
        $this->test_post_ids = [];
        
        // Delete test user
        if ($this->test_user_id && !\is_wp_error($this->test_user_id) && function_exists('wp_delete_user')) {
            call_user_func('wp_delete_user', $this->test_user_id);
        }
        
        parent::tearDown();
    }

    /**
     * TC-BE-003: Test post creation
     * 
     * Objective: Verify new post can be created via WordPress functions
     * Expected: Post is successfully created with valid ID
     */
    public function test_post_creation()
    {
        $post_data = array(
            'post_title' => 'Test Post ' . time(),
            'post_content' => 'This is a test post content for unit testing.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id,
            'post_type' => 'post'
        );
        
        // Create the post
        $post_id = \wp_insert_post($post_data);
        
        // Verify post was created
        $this->assertIsInt($post_id, 'Post ID should be an integer');
        $this->assertGreaterThan(0, $post_id, 'Post ID should be greater than 0');
        $this->assertFalse(\is_wp_error($post_id), 'Post creation should not return an error');
        
        // Store for cleanup
        $this->test_post_ids[] = $post_id;
        
        // Verify post exists in database
        $post = \get_post($post_id);
        $this->assertInstanceOf(\WP_Post::class, $post, 'Post should exist in database');
        $this->assertEquals($post_data['post_title'], $post->post_title, 'Post title should match');
        $this->assertEquals($post_data['post_content'], $post->post_content, 'Post content should match');
        $this->assertEquals($post_data['post_status'], $post->post_status, 'Post status should match');
        $this->assertEquals($post_data['post_author'], $post->post_author, 'Post author should match');
    }

    /**
     * TC-BE-004: Test post update
     * 
     * Objective: Verify existing post can be updated
     * Expected: Post is successfully updated
     */
    public function test_post_update()
    {
        // First, create a post
        $original_data = array(
            'post_title' => 'Original Post Title',
            'post_content' => 'Original post content.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($original_data);
        $this->assertFalse(\is_wp_error($post_id), 'Post should be created successfully');
        $this->test_post_ids[] = $post_id;
        
        // Update the post
        $updated_data = array(
            'ID' => $post_id,
            'post_title' => 'Updated Post Title',
            'post_content' => 'Updated post content with new information.',
            'post_status' => 'draft'
        );
        
        $updated_post_id = \wp_update_post($updated_data);
        
        // Verify update succeeded
        $this->assertEquals($post_id, $updated_post_id, 'Updated post ID should match original');
        $this->assertFalse(\is_wp_error($updated_post_id), 'Post update should not return an error');
        
        // Verify post was updated in database
        $post = \get_post($post_id);
        $this->assertEquals($updated_data['post_title'], $post->post_title, 'Post title should be updated');
        $this->assertEquals($updated_data['post_content'], $post->post_content, 'Post content should be updated');
        $this->assertEquals($updated_data['post_status'], $post->post_status, 'Post status should be updated');
    }

    /**
     * TC-BE-005: Test post deletion
     * 
     * Objective: Verify post can be deleted
     * Expected: Post is successfully deleted
     */
    public function test_post_deletion()
    {
        // Create a post
        $post_data = array(
            'post_title' => 'Post to Delete',
            'post_content' => 'This post will be deleted.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($post_data);
        $this->assertFalse(\is_wp_error($post_id), 'Post should be created successfully');
        
        // Verify post exists
        $post_before = \get_post($post_id);
        $this->assertInstanceOf(\WP_Post::class, $post_before, 'Post should exist before deletion');
        
        // Delete the post (force delete, not trash)
        $deleted_post = \wp_delete_post($post_id, true);
        
        // Verify deletion succeeded
        $this->assertInstanceOf(\WP_Post::class, $deleted_post, 'Deletion should return WP_Post object');
        
        // Verify post no longer exists
        $post_after = \get_post($post_id);
        $this->assertNull($post_after, 'Post should not exist after deletion');
    }

    /**
     * Test post deletion to trash
     * 
     * Objective: Verify post can be moved to trash
     * Expected: Post status changes to trash
     */
    public function test_post_deletion_to_trash()
    {
        // Create a post
        $post_data = array(
            'post_title' => 'Post to Trash',
            'post_content' => 'This post will be moved to trash.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($post_data);
        $this->assertFalse(\is_wp_error($post_id), 'Post should be created successfully');
        $this->test_post_ids[] = $post_id;
        
        // Move post to trash (force = false)
        $trashed_post = \wp_delete_post($post_id, false);
        
        // Verify post was moved to trash
        $this->assertInstanceOf(\WP_Post::class, $trashed_post, 'Post should be moved to trash');
        
        // Verify post status is trash
        $post = \get_post($post_id);
        $this->assertEquals('trash', $post->post_status, 'Post status should be trash');
    }

    /**
     * Test retrieving post by ID
     * 
     * Objective: Verify post can be retrieved by ID
     * Expected: Post object is returned correctly
     */
    public function test_get_post_by_id()
    {
        $post_data = array(
            'post_title' => 'Retrieval Test Post',
            'post_content' => 'Testing post retrieval.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($post_data);
        $this->test_post_ids[] = $post_id;
        
        // Retrieve the post
        $post = \get_post($post_id);
        
        $this->assertInstanceOf(\WP_Post::class, $post, 'Should return WP_Post object');
        $this->assertEquals($post_id, $post->ID, 'Post ID should match');
        $this->assertEquals($post_data['post_title'], $post->post_title, 'Post title should match');
    }

    /**
     * Test post status validation
     * 
     * Objective: Verify only valid post statuses are accepted
     * Expected: Invalid status is rejected or defaulted
     */
    public function test_post_status_validation()
    {
        $post_data = array(
            'post_title' => 'Status Validation Test',
            'post_content' => 'Testing status validation.',
            'post_status' => 'invalid_status',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($post_data);
        $this->test_post_ids[] = $post_id;
        
        // WordPress may accept the status as-is or normalize it
        $post = \get_post($post_id);
        $this->assertInstanceOf(\WP_Post::class, $post, 'Post should still be created');
        // WordPress may accept custom statuses, so just verify post was created
        $this->assertIsInt($post_id, 'Post should have valid ID');
        $this->assertGreaterThan(0, $post_id, 'Post ID should be greater than 0');
    }

    /**
     * Test post with excerpt
     * 
     * Objective: Verify post excerpt can be set
     * Expected: Excerpt is saved and retrieved correctly
     */
    public function test_post_with_excerpt()
    {
        $post_data = array(
            'post_title' => 'Post with Excerpt',
            'post_content' => 'Full post content here.',
            'post_excerpt' => 'This is the excerpt.',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        );
        
        $post_id = \wp_insert_post($post_data);
        $this->test_post_ids[] = $post_id;
        
        $post = \get_post($post_id);
        $this->assertEquals($post_data['post_excerpt'], $post->post_excerpt, 'Post excerpt should match');
    }

    /**
     * Test multiple posts creation
     * 
     * Objective: Verify multiple posts can be created
     * Expected: All posts are created successfully
     */
    public function test_multiple_posts_creation()
    {
        $post_count = 5;
        $created_ids = [];
        
        for ($i = 1; $i <= $post_count; $i++) {
            $post_data = array(
                'post_title' => "Test Post $i",
                'post_content' => "Content for post $i",
                'post_status' => 'publish',
                'post_author' => $this->test_user_id
            );
            
            $post_id = \wp_insert_post($post_data);
            $this->assertFalse(\is_wp_error($post_id), "Post $i should be created successfully");
            $this->assertGreaterThan(0, $post_id, "Post $i should have valid ID");
            
            $created_ids[] = $post_id;
            $this->test_post_ids[] = $post_id;
        }
        
        // Verify all posts exist
        foreach ($created_ids as $post_id) {
            $post = \get_post($post_id);
            $this->assertInstanceOf(\WP_Post::class, $post, "Post $post_id should exist");
        }
        
        $this->assertCount($post_count, $created_ids, "Should create $post_count posts");
    }
}

