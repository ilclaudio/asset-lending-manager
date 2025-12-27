<?php
/**
 * Plugin Uninstaller.
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once ALM_PLUGIN_DIR . 'plugin-config.php';

// Remove custom roles.
remove_role( ALM_MEMBER_ROLE );
remove_role( ALM_OPERATOR_ROLE );


// Remove capabilities.
// foreach ( $roles_to_modify as $role_name ) {
// 		$role = get_role( $role_name );
// 		if ( $role ) {
// 				foreach ( $caps_to_remove as $cap ) {
// 						$role->remove_cap( $cap );
// 				}
// 		}
// }


// Remove custom tables.
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}alm_devices" );
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}alm_logs" );
