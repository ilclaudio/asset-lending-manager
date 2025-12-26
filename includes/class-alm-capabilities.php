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
	 * CPT capabilities for the Device post type.
	 *
	 * These are required by WordPress when using capability_type + map_meta_cap.
	 *
	 * @return string[]
	 */
	public static function get_device_cpt_caps() {
		return array(
			// To read.
			'read_alm_device',
			'read_private_alm_devices',
			// To modify.
			'edit_alm_device',
			'edit_alm_devices',
			'edit_others_alm_devices',
			'edit_published_alm_devices',
			'edit_private_alm_devices',
			// To delete.
			'delete_alm_device',
			'delete_alm_devices',
			'delete_others_alm_devices',
			'delete_published_alm_devices',
			'delete_private_alm_devices',
			// To publish.
			'publish_alm_devices',
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
	public static function get_device_domain_caps() {
		return array(
			ALM_VIEW_DEVICES,
			ALM_VIEW_DEVICE,
			ALM_EDIT_DEVICE,
		);
	}

	/**
	 * Return all capabilities managed by ALM.
	 *
	 * @return string[]
	 */
	public static function get_all_device_caps() {
		return array_merge(
			self::get_device_cpt_caps(),
			self::get_device_domain_caps()
		);
	}

}
