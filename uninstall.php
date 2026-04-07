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
	'alm_operator',
	'alm_member',
);

$almgr_legacy_caps = array(
	'read_alm_asset',
	'read_private_alm_assets',
	'edit_alm_asset',
	'edit_alm_assets',
	'edit_others_alm_assets',
	'edit_published_alm_assets',
	'edit_private_alm_assets',
	'delete_alm_asset',
	'delete_alm_assets',
	'delete_others_alm_assets',
	'delete_published_alm_assets',
	'delete_private_alm_assets',
	'publish_alm_assets',
	'alm_view_assets',
	'alm_view_asset',
	'alm_edit_asset',
);

foreach ( $alm_roles_to_modify as $alm_role_name ) {
	$alm_role = get_role( $alm_role_name );
	if ( $alm_role ) {
		foreach ( ALMGR_Capabilities::get_all_asset_caps() as $alm_cap ) {
			$alm_role->remove_cap( $alm_cap );
		}
		foreach ( $almgr_legacy_caps as $almgr_legacy_cap ) {
			$alm_role->remove_cap( $almgr_legacy_cap );
		}
	}
}

// Drop custom roles.
remove_role( ALMGR_MEMBER_ROLE );
remove_role( ALMGR_OPERATOR_ROLE );
remove_role( 'alm_member' );
remove_role( 'alm_operator' );

// Drop plugin tables through the installer helper (validated table identifiers).
ALMGR_Installer::drop_tables();

// Remove plugin options.
delete_option( 'almgr_settings' );
delete_option( 'alm_settings' );
