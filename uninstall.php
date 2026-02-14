<?php
/**
 * Plugin Uninstaller.
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/plugin-config.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-capabilities.php';

// Remove ALM capabilities from roles that can receive plugin caps.
$alm_roles_to_modify = array(
	'administrator',
	ALM_OPERATOR_ROLE,
	ALM_MEMBER_ROLE,
);

foreach ( $alm_roles_to_modify as $alm_role_name ) {
	$alm_role = get_role( $alm_role_name );
	if ( $alm_role ) {
		foreach ( ALM_Capabilities::get_all_asset_caps() as $cap ) {
			$alm_role->remove_cap( $cap );
		}
	}
}

// Drop custom roles.
remove_role( ALM_MEMBER_ROLE );
remove_role( ALM_OPERATOR_ROLE );

// Drop plugin tables.
global $wpdb;
$alm_tables = array(
	$wpdb->prefix . 'alm_loan_requests_history',
	$wpdb->prefix . 'alm_loan_requests',
);

foreach ( $alm_tables as $alm_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `$alm_table`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
}

// Remove plugin options.
delete_option( 'alm_settings' );
