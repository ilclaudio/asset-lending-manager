<?php
/**
 * Unit Tests Bootstrap.
 *
 * @package AssetLendingManager
 */

// Fake ABSPATH for unit testing outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Load main plugin class.
require_once dirname( __DIR__ ) . '/includes/class-almgr-plugin-manager.php';

// Load module classes.
require_once dirname( __DIR__ ) . '/includes/class-almgr-settings-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-role-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-asset-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-loan-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-notification-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-frontend-manager.php';
