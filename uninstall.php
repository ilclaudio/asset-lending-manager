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

// Drop custom roles.
remove_role( ALM_MEMBER_ROLE );
remove_role( ALM_OPERATOR_ROLE );

// Drop capabilities.
foreach ( $alm_roles_to_modify as $alm_role_name ) {
	$alm_role = get_role( $alm_role_name );
	if ( $alm_role ) {
		foreach ( $caps_to_remove as $cap ) {
			$alm_role->remove_cap( $cap );
		}
	}
}


/**
 * Drop plugin database tables.
 * Called on plugin uninstall.
 *
*/
// require_once plugin_dir_path( __FILE__ ) . 'includes/class-alm-installer.php';
// ALM_Installer::drop_tables();
