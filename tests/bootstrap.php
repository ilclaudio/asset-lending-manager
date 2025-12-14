<?php
/**
 * Unit Tests Bootstrap.
 */

// Fake ABSPATH for unit testing outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Include the plugin.
// require_once dirname( __DIR__ ) . '/asset-lending-manager.php';

// Load only the class under test.
require_once dirname( __DIR__ ) . '/includes/class-plugin-manager.php';
