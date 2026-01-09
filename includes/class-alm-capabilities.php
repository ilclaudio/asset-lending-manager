<?php
defined( 'ABSPATH' ) || exit;

/**
 * Centralized definition of all ALM capabilities.
 *
 * This class defines:
 * - CPT-level WordPress capabilities (used by core for CPT access control)
 * - Domain-level ALM capabilities (used by menus, frontend, business logic)
 */
class ALM_Capabilities {

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
			'read_alm_asset',
			'read_private_alm_assets',
			// To modify.
			'edit_alm_asset',
			'edit_alm_assets',
			'edit_others_alm_assets',
			'edit_published_alm_assets',
			'edit_private_alm_assets',
			// To delete.
			'delete_alm_asset',
			'delete_alm_assets',
			'delete_others_alm_assets',
			'delete_published_alm_assets',
			'delete_private_alm_assets',
			// To publish.
			'publish_alm_assets',
		);
	}

	/**
	 * Domain-level ALM capabilities.
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
			ALM_VIEW_ASSETS,
			ALM_VIEW_ASSET,
			ALM_EDIT_ASSET,
		);
	}

	/**
	 * Return all capabilities managed by ALM.
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
