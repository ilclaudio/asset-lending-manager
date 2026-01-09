<?php
/**
 * Configuration data of the plugin.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
define( 'ALM_VERSION', '0.1.0' );
define( 'ALM_PLUGIN_FILE', __FILE__ );
define( 'ALM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALM_TEXT_DOMAIN', 'asset-lending-manager' );

// Main menu settings.
define( 'ALM_SLUG_MAIN_MENU', 'alm' );

// Permissions.
define( 'ALM_VIEW_ASSETS', 'alm_view_assets' );
define( 'ALM_VIEW_ASSET', 'alm_view_asset' );
define( 'ALM_EDIT_ASSET', 'alm_edit_asset' );

// Asset CPT.
define( 'ALM_ASSET_CPT_SLUG', 'alm_asset' );
define( 'ALM_MAIN_MENU_ICON', 'dashicons-hammer' );
define( 'ALM_ASSET_ICON', 'dashicons-hammer' );

// Asset structures.
define( 'ALM_ASSET_KIT_SLUG', 'kit' );
define( 'ALM_ASSET_COMPONENT_SLUG', 'component' );

// Taxonomies.
define( 'ALM_ASSET_STRUCTURE_TAXONOMY_SLUG', 'alm_structure' );
define( 'ALM_ASSET_TYPE_TAXONOMY_SLUG', 'alm_type' );
define( 'ALM_ASSET_STATE_TAXONOMY_SLUG', 'alm_state' );
define( 'ALM_ASSET_LEVEL_TAXONOMY_SLUG', 'alm_level' );
define(
	'ALM_CUSTOM_TAXONOMIES',
	array(
		ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
		ALM_ASSET_TYPE_TAXONOMY_SLUG,
		ALM_ASSET_STATE_TAXONOMY_SLUG,
		ALM_ASSET_LEVEL_TAXONOMY_SLUG,
	)
);

// Roles and permissions.
define( 'ALM_MEMBER_ROLE', 'alm_member' );
define( 'ALM_OPERATOR_ROLE', 'alm_operator' );

// Autocomplete.
define( 'ALM_AUTOCOMPLETE_MAX_RESULTS', 5 );
define( 'ALM_AUTOCOMPLETE_DESC_LENGTH', 20 );