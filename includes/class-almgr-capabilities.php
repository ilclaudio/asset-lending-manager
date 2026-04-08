<?php
/**
 * Centralized definition of all ALMGR capabilities.
 *
 * This class defines:
 * - CPT-level WordPress capabilities (used by core for CPT access control)
 * - Domain-level ALMGR capabilities (used by menus, frontend, business logic)
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Centralized definition of all ALMGR capabilities.
 */
class ALMGR_Capabilities {

	/**
	 * CPT capabilities for the Asset post type.
	 *
	 * These are required by WordPress when using capability_type + map_meta_cap.
	 *
	 * @return string[]
	 */
	public static function get_asset_cpt_caps() {
		return array(
			// To read.
			'read_almgr_asset',
			'read_private_almgr_assets',
			// To modify.
			'edit_almgr_asset',
			'edit_almgr_assets',
			'edit_others_almgr_assets',
			'edit_published_almgr_assets',
			'edit_private_almgr_assets',
			// To delete.
			'delete_almgr_asset',
			'delete_almgr_assets',
			'delete_others_almgr_assets',
			'delete_published_almgr_assets',
			'delete_private_almgr_assets',
			// To publish.
			'publish_almgr_assets',
		);
	}

	/**
	 * Domain-level ALMGR capabilities.
	 *
	 * These are used by:
	 * - menus
	 * - frontend
	 * - business logic
	 *
	 * @return string[]
	 */
	public static function get_asset_domain_caps() {
		return array(
			ALMGR_VIEW_ASSETS,
			ALMGR_VIEW_ASSET,
			ALMGR_EDIT_ASSET,
		);
	}

	/**
	 * Return all capabilities managed by ALMGR.
	 *
	 * @return string[]
	 */
	public static function get_all_asset_caps() {
		return array_merge(
			self::get_asset_cpt_caps(),
			self::get_asset_domain_caps()
		);
	}
}
