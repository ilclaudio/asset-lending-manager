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
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-capabilities.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-logger.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-installer.php';

// Remove ALM capabilities from roles that can receive plugin caps.
$alm_roles_to_modify = array(
	'administrator',
	ALMGR_OPERATOR_ROLE,
	ALMGR_MEMBER_ROLE,
);

foreach ( $alm_roles_to_modify as $alm_role_name ) {
	$alm_role = get_role( $alm_role_name );
	if ( $alm_role ) {
		foreach ( ALMGR_Capabilities::get_all_asset_caps() as $alm_cap ) {
			$alm_role->remove_cap( $alm_cap );
		}
	}
}

// Drop custom roles.
remove_role( ALMGR_MEMBER_ROLE );
remove_role( ALMGR_OPERATOR_ROLE );

// Drop plugin tables through the installer helper (validated table identifiers).
ALMGR_Installer::drop_tables();

// Remove plugin options.
delete_option( 'almgr_settings' );
