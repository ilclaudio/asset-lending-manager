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

// Remove ALMGR capabilities from roles that can receive plugin caps.
$almgr_roles_to_modify = array(
	'administrator',
	ALMGR_OPERATOR_ROLE,
	ALMGR_MEMBER_ROLE,
);

foreach ( $almgr_roles_to_modify as $almgr_role_name ) {
	$almgr_role = get_role( $almgr_role_name );
	if ( $almgr_role ) {
		foreach ( ALMGR_Capabilities::get_all_asset_caps() as $almgr_cap ) {
			$almgr_role->remove_cap( $almgr_cap );
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

// Optional full data cleanup, enabled only via wp-config.php.
// Example value: define( 'ALMGR_REMOVE_ALL_DATA', true ).
$almgr_remove_all_data = defined( 'ALMGR_REMOVE_ALL_DATA' ) && ALMGR_REMOVE_ALL_DATA;

if ( $almgr_remove_all_data ) {
	$almgr_asset_ids = get_posts(
		array(
			'post_type'              => ALMGR_ASSET_CPT_SLUG,
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $almgr_asset_ids as $almgr_asset_id ) {
		wp_delete_post( (int) $almgr_asset_id, true );
	}

	// Remove plugin post meta keys from all posts.
	delete_post_meta_by_key( '_almgr_current_owner' );
	delete_post_meta_by_key( '_almgr_removed_from_kit_ids' );
}
