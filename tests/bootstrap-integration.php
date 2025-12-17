<?php
/**
 * Integration Tests Bootstrap.
 */

// Get the tests directory
$alm_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $alm_tests_dir ) {
	$alm_tests_dir = 'C:/WordpressDEV/wordpress-develop/tests/phpunit';
}

// Verify the path exists
if ( ! file_exists( $alm_tests_dir . '/includes/functions.php' ) ) {
	die( "WordPress test library not found at: {$alm_tests_dir}\n" );
}

// Give access to tests_add_filter() function.
require_once $alm_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function alm_manually_load_plugin() {
	require dirname( __FILE__, 2 ) . '/asset-lending-manager.php';
}

// Add filter to load plugin before WordPress loads.
tests_add_filter( 'muplugins_loaded', 'alm_manually_load_plugin' );

// Start up the WP testing environment.
require $alm_tests_dir . '/includes/bootstrap.php';
