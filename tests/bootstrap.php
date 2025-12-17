<?php
/**
 * Unit Tests Bootstrap.
 */

// Fake ABSPATH for unit testing outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Load main plugin file.
require_once dirname( __DIR__ ) . '/includes/class-alm-plugin-manager.php';

// Load module classes.
require_once dirname( __DIR__ ) . '/includes/class-alm-settings-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-role-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-device-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-loan-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-notification-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-frontend-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-alm-plugin-manager.php';
