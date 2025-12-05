<?php
/**
 * PHPUnit Bootstrap File for WordPress Tests
 * 
 * This file initializes the testing environment for WordPress.
 * It loads WordPress core files from the cloned wordpress/ directory.
 * 
 * WordPress Location: ../wordpress/ (cloned from GitHub)
 */

// Load Composer autoloader (vendor may be in /tmp/vendor)
$vendor_paths = [
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
    '/tmp/vendor/autoload.php'
];

foreach ($vendor_paths as $vendor_path) {
    if (file_exists($vendor_path)) {
        require_once $vendor_path;
        break;
    }
}

// Define WordPress root path
// Try installed WordPress first, then fall back to cloned WordPress
$installed_wp_path = '/var/www/html';
$cloned_wp_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'wordpress';

// Check which WordPress to use
if (file_exists($installed_wp_path . DIRECTORY_SEPARATOR . 'wp-load.php')) {
    // Use installed WordPress (already configured with database)
    $wordpress_path = $installed_wp_path;
} elseif (file_exists($cloned_wp_path . DIRECTORY_SEPARATOR . 'wp-load.php')) {
    // Use cloned WordPress
    $wordpress_path = $cloned_wp_path;
} else {
    trigger_error(
        "WordPress not found at: {$installed_wp_path} or {$cloned_wp_path}\n",
        E_USER_ERROR
    );
}

// Define ABSPATH before loading WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', $wordpress_path . DIRECTORY_SEPARATOR);
}

// Load wp-tests-config.php if it exists (WordPress test standard)
$wp_tests_config = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'wp-tests-config.php';
if (file_exists($wp_tests_config)) {
    require_once $wp_tests_config;
}

// Set up test database configuration BEFORE loading WordPress
// WordPress needs these constants defined before wp-load.php is called
if (!defined('DB_NAME')) {
    $db_name = getenv('WP_TESTS_DB_NAME') ?: 'wordpress';
    $db_user = getenv('WP_TESTS_DB_USER') ?: 'wordpress';
    $db_password = getenv('WP_TESTS_DB_PASSWORD') ?: 'wordpress';
    $db_host = getenv('WP_TESTS_DB_HOST') ?: 'db'; // Use 'db' hostname from Docker network
    
    // These constants will be used if wp-config.php doesn't exist
    define('DB_NAME', $db_name);
    define('DB_USER', $db_user);
    define('DB_PASSWORD', $db_password);
    define('DB_HOST', $db_host);
}

// Set HTTP_HOST and REQUEST_URI to avoid warnings in CLI mode
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Prevent WordPress from redirecting or exiting in CLI mode
if (!defined('WP_CLI')) {
    define('WP_CLI', false);
}
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}
if (!defined('DOING_CRON')) {
    define('DOING_CRON', false);
}

// Define WordPress test constants
if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    $polyfills_path = dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills';
    if (file_exists($polyfills_path)) {
        define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path . '/phpunitpolyfills-autoload.php');
    }
}

// Load WordPress
if (!defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', false);
}

// Prevent WordPress from loading the main wp-config.php
// We'll define constants ourselves for testing
if (!defined('WP_INSTALLING')) {
    define('WP_INSTALLING', false);
}

// Prevent WordPress from calling exit() or die() during tests
// Override exit and die functions temporarily
if (!function_exists('_wp_test_exit_handler')) {
    function _wp_test_exit_handler($status = 0) {
        // Don't actually exit - just return
        // This allows PHPUnit to continue
        return;
    }
    
    // Override exit() and die() to prevent WordPress from stopping execution
    if (function_exists('runkit_function_redefine')) {
        runkit_function_redefine('exit', '', '_wp_test_exit_handler');
        runkit_function_redefine('die', '', '_wp_test_exit_handler');
    }
}

// Load WordPress core
// Suppress all output and errors during WordPress loading
ob_start();

// Set custom error handler to suppress header warnings and fatal errors
$old_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suppress header modification warnings in CLI
    if (strpos($errstr, 'Cannot modify header') !== false) {
        return true; // Suppress this warning
    }
    // Suppress other non-critical warnings
    if ($errno === E_WARNING || $errno === E_NOTICE) {
        return true;
    }
    return false;
}, E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Set shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Suppress fatal error output in tests
        ob_clean();
    }
});

try {
    // Check if WordPress needs installation - if so, skip it for tests
    if (!defined('WP_INSTALLING')) {
        define('WP_INSTALLING', false);
    }
    
    // Prevent WordPress from showing error pages
    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', false);
    }
    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', false);
    }
    
    @require_once $wordpress_path . DIRECTORY_SEPARATOR . 'wp-load.php';
} catch (Throwable $e) {
    ob_end_clean();
    restore_error_handler();
    // Log but don't throw - let tests run even if WordPress has issues
    if (function_exists('error_log')) {
        @error_log('WordPress loading warning: ' . $e->getMessage());
    }
}

restore_error_handler();
$output = ob_get_clean();
// Ignore all output from WordPress loading - including error pages

// Check if WordPress loaded successfully
if (!function_exists('wp_insert_post')) {
    trigger_error(
        "WordPress functions not available. WordPress may not have loaded correctly.",
        E_USER_WARNING
    );
}

// Include test helper functions
if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'test-helpers.php')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'test-helpers.php';
}

