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
require_once ALM_PLUGIN_DIR . 'includes/class-alm-logger.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-installer.php';

// Remove ALM capabilities from roles that can receive plugin caps.
$alm_roles_to_modify = array(
	'administrator',
	ALM_OPERATOR_ROLE,
	ALM_MEMBER_ROLE,
);

foreach ( $alm_roles_to_modify as $alm_role_name ) {
	$alm_role = get_role( $alm_role_name );
	if ( $alm_role ) {
		foreach ( ALM_Capabilities::get_all_asset_caps() as $alm_cap ) {
			$alm_role->remove_cap( $alm_cap );
		}
	}
}

// Drop custom roles.
remove_role( ALM_MEMBER_ROLE );
remove_role( ALM_OPERATOR_ROLE );

// Drop plugin tables through the installer helper (validated table identifiers).
ALM_Installer::drop_tables();

// Remove plugin options.
delete_option( 'alm_settings' );
