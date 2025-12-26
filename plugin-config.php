<?php
/**
 * Configuration data of the plugin.
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'ALM_VERSION', '0.1.0' );
define( 'ALM_PLUGIN_FILE', __FILE__ );
define( 'ALM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALM_TEXT_DOMAIN', 'asset-lending-manager' );

// Main menu settings.
define( 'ALM_SLUG_MAIN_MENU', 'alm' );

// Permissions.
define( 'ALM_VIEW_DEVICES', 'alm_view_devices' );
define( 'ALM_VIEW_DEVICE', 'alm_view_device' );
define( 'ALM_EDIT_DEVICE', 'alm_edit_device' );

// Device CPT.
define( 'ALM_DEVICE_CPT_SLUG', 'alm_device' );
define( 'ALM_MAIN_MENU_ICON', 'dashicons-hammer' );
define( 'ALM_DEVICE_ICON', 'dashicons-hammer' );

// Taxonomies.
define( 'ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG', 'alm_structure' );
define( 'ALM_DEVICE_TYPE_TAXONOMY_SLUG', 'alm_type' );
define( 'ALM_DEVICE_STATE_TAXONOMY_SLUG', 'alm_state' );
define( 'ALM_DEVICE_LEVEL_TAXONOMY_SLUG', 'alm_level' );
define(
	'ALM_CUSTOM_TAXONOMIES',
	array(
		ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG,
		ALM_DEVICE_TYPE_TAXONOMY_SLUG,
		ALM_DEVICE_STATE_TAXONOMY_SLUG,
		ALM_DEVICE_LEVEL_TAXONOMY_SLUG,
	)
);

// Roles and permissions.
define( 'ALM_MEMBER_ROLE', 'alm_member' );
define( 'ALM_OPERATOR_ROLE', 'alm_operator' );
