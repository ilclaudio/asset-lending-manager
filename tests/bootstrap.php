<?php
/**
 * Unit Tests Bootstrap.
 */

// Fake ABSPATH for unit testing outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Load only the class under test.
require_once dirname( __DIR__ ) . '/includes/class-alm-plugin-manager.php';
