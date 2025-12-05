<?php
/**
 * Database Test Cases
 * 
 * Tests for WordPress database operations and queries
 * 
 * Test Cases Covered:
 * - TC-BE-008: Database Query - Retrieve Posts
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_Query;

class DatabaseTest extends TestCase
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
        
        // Create a test user
        $this->test_user_id = \wp_create_user(
            'db_test_user_' . time() . '_' . rand(1000, 9999),
            'testpass123',
            'dbtest' . time() . '_' . rand(1000, 9999) . '@example.com'
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
                \wp_delete_post($post_id, true);
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
     * TC-BE-008: Test database query to retrieve posts
     * 
     * Objective: Verify database queries retrieve correct data
     * Expected: Posts are retrieved correctly and efficiently
     */
    public function test_database_query_retrieve_posts()
    {
        // Create multiple test posts
        $post_titles = [
            'Database Test Post 1',
            'Database Test Post 2',
            'Database Test Post 3'
        ];
        
        foreach ($post_titles as $title) {
            $post_id = \wp_insert_post(array(
                'post_title' => $title,
                'post_content' => 'Content for ' . $title,
                'post_status' => 'publish',
                'post_author' => $this->test_user_id
            ));
            $this->test_post_ids[] = $post_id;
        }
        
        // Query posts using WP_Query
        $query = new \WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $this->test_user_id
        ));
        
        // Verify query executed successfully
        // WP_Query doesn't have is_error property, check found_posts instead
        $this->assertGreaterThanOrEqual(0, $query->found_posts, 'Query should execute successfully');
        $this->assertGreaterThanOrEqual(3, $query->found_posts, 'Should find at least 3 posts');
        $this->assertGreaterThanOrEqual(3, count($query->posts), 'Should return at least 3 posts');
        
        // Verify posts match expected titles
        $found_titles = array_map(function($post) {
            return $post->post_title;
        }, $query->posts);
        
        foreach ($post_titles as $title) {
            $this->assertContains($title, $found_titles, "Should find post: $title");
        }
    }

    /**
     * Test WP_Query with specific post status
     * 
     * Objective: Verify queries can filter by post status
     * Expected: Only posts with specified status are returned
     */
    public function test_wp_query_with_post_status_filter()
    {
        // Create published posts
        $published_ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $post_id = \wp_insert_post(array(
                'post_title' => "Published Post $i",
                'post_content' => 'Published content',
                'post_status' => 'publish',
                'post_author' => $this->test_user_id
            ));
            $published_ids[] = $post_id;
            $this->test_post_ids[] = $post_id;
        }
        
        // Create draft posts
        $draft_ids = [];
        for ($i = 1; $i <= 2; $i++) {
            $post_id = \wp_insert_post(array(
                'post_title' => "Draft Post $i",
                'post_content' => 'Draft content',
                'post_status' => 'draft',
                'post_author' => $this->test_user_id
            ));
            $draft_ids[] = $post_id;
            $this->test_post_ids[] = $post_id;
        }
        
        // Query only published posts
        $published_query = new \WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $this->test_user_id
        ));
        
        // Verify only published posts are returned
        $this->assertEquals(3, $published_query->found_posts, 'Should find 3 published posts');
        foreach ($published_query->posts as $post) {
            $this->assertEquals('publish', $post->post_status, 'All returned posts should be published');
            $this->assertContains($post->ID, $published_ids, 'Post ID should be in published list');
        }
        
        // Query only draft posts
        $draft_query = new \WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => -1,
            'author' => $this->test_user_id
        ));
        
        // Verify only draft posts are returned
        $this->assertEquals(2, $draft_query->found_posts, 'Should find 2 draft posts');
        foreach ($draft_query->posts as $post) {
            $this->assertEquals('draft', $post->post_status, 'All returned posts should be drafts');
            $this->assertContains($post->ID, $draft_ids, 'Post ID should be in draft list');
        }
    }

    /**
     * Test database query performance
     * 
     * Objective: Verify query performance is acceptable
     * Expected: Query executes within reasonable time
     */
    public function test_database_query_performance()
    {
        // Create test posts
        for ($i = 1; $i <= 10; $i++) {
            $post_id = \wp_insert_post(array(
                'post_title' => "Performance Test Post $i",
                'post_content' => 'Content for performance testing',
                'post_status' => 'publish',
                'post_author' => $this->test_user_id
            ));
            $this->test_post_ids[] = $post_id;
        }
        
        // Measure query execution time
        $start_time = microtime(true);
        
        $query = new \WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $this->test_user_id
        ));
        
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        // Verify query executed and results are correct
        $this->assertGreaterThanOrEqual(10, $query->found_posts, 'Should find at least 10 posts');
        
        // Performance assertion: Query should complete within 1 second (1000ms)
        // This is a generous limit for unit tests
        $this->assertLessThan(1000, $execution_time, 'Query should complete within 1 second');
    }

    /**
     * Test global database object access
     * 
     * Objective: Verify global $wpdb object is accessible
     * Expected: Can execute direct SQL queries via $wpdb
     */
    public function test_global_wpdb_access()
    {
        global $wpdb;
        
        // Verify $wpdb is available
        $this->assertNotNull($wpdb, 'Global $wpdb object should be available');
        $this->assertObjectHasProperty('posts', $wpdb, '$wpdb should have posts table property');
        
        // Create a test post
        $post_id = \wp_insert_post(array(
            'post_title' => 'Direct Query Test',
            'post_content' => 'Testing direct database queries',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        ));
        $this->test_post_ids[] = $post_id;
        
        // Query directly using $wpdb
        $table_name = $wpdb->posts;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE ID = %d",
            $post_id
        ));
        
        // Verify direct query works
        $this->assertIsArray($results, 'Should return an array of results');
        $this->assertCount(1, $results, 'Should find exactly one post');
        $this->assertEquals($post_id, $results[0]->ID, 'Post ID should match');
    }

    /**
     * Test get_posts function
     * 
     * Objective: Verify get_posts() retrieves posts correctly
     * Expected: Posts are returned as array of post objects
     */
    public function test_get_posts_function()
    {
        // Create test posts
        for ($i = 1; $i <= 5; $i++) {
            $post_id = \wp_insert_post(array(
                'post_title' => "Get Posts Test $i",
                'post_content' => 'Content',
                'post_status' => 'publish',
                'post_author' => $this->test_user_id
            ));
            $this->test_post_ids[] = $post_id;
        }
        
        // Use get_posts() function
        $posts = \get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $this->test_user_id
        ));
        
        // Verify results
        $this->assertIsArray($posts, 'Should return an array');
        $this->assertGreaterThanOrEqual(5, count($posts), 'Should return at least 5 posts');
        
        // Verify each post is a WP_Post object
        foreach ($posts as $post) {
            $this->assertInstanceOf(\WP_Post::class, $post, 'Each result should be a WP_Post object');
        }
    }

    /**
     * Test database transaction support
     * 
     * Objective: Verify database operations can be rolled back if needed
     * Expected: Can test transaction-like behavior
     */
    public function test_database_operations_isolation()
    {
        // Create a post
        $post_id = \wp_insert_post(array(
            'post_title' => 'Isolation Test Post',
            'post_content' => 'Testing operation isolation',
            'post_status' => 'publish',
            'post_author' => $this->test_user_id
        ));
        $this->test_post_ids[] = $post_id;
        
        // Verify post exists
        $post = \get_post($post_id);
        $this->assertNotNull($post, 'Post should exist');
        
        // Delete the post
        \wp_delete_post($post_id, true);
        
        // Verify post is deleted (isolation)
        $deleted_post = \get_post($post_id);
        $this->assertNull($deleted_post, 'Post should be deleted and not exist');
    }
}

