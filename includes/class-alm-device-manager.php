<?php
/**
 * Asset Lending Manager - Device Manager
 *
 * Handles device domain logic.
 * Responsible for:
 * - Registering the alm_device Custom Post Type
 * - Registering device-related taxonomies
 * - Providing read-only helpers for device properties
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ALM_Device_Manager
 */
class ALM_Device_Manager {

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// Intentionally left empty.
		// CPTs and taxonomies must be registered at runtime.
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Intentionally left empty.
	}

	/**
	 * Register the alm_device Custom Post Type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => __( 'Devices', 'asset-lending-manager' ),
			'singular_name'      => __( 'Device', 'asset-lending-manager' ),
			'add_new'            => __( 'Add New', 'asset-lending-manager' ),
			'add_new_item'       => __( 'Add New Device', 'asset-lending-manager' ),
			'edit_item'          => __( 'Edit Device', 'asset-lending-manager' ),
			'new_item'           => __( 'New Device', 'asset-lending-manager' ),
			'view_item'          => __( 'View Device', 'asset-lending-manager' ),
			'search_items'       => __( 'Search Devices', 'asset-lending-manager' ),
			'not_found'          => __( 'No devices found', 'asset-lending-manager' ),
			'not_found_in_trash' => __( 'No devices found in Trash', 'asset-lending-manager' ),
			'menu_name'          => __( 'Devices', 'asset-lending-manager' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'menu_icon'       => ALM_DEVICE_ICON,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => ALM_DEVICE_CPT_SLUG,
			'map_meta_cap'    => true,
			'capabilities'    => array(
				'edit_posts'         => ALM_EDIT_DEVICE,
				'edit_others_posts'  => ALM_EDIT_DEVICE,
				'create_posts'       => ALM_CREATE_DEVICE,
				'publish_posts'      => ALM_EDIT_DEVICE,
				'delete_posts'       => ALM_EDIT_DEVICE,
				'read_private_posts' => ALM_VIEW_DEVICES,
			),
		);

		register_post_type( ALM_DEVICE_CPT_SLUG, $args );
	}

	/**
	 * Register device-related taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {

		// Logical device structure: component or kit.
		register_taxonomy(
			ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG,
			ALM_DEVICE_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Device Structures', 'asset-lending-manager' ),
					'singular_name' => __( 'Device Structure', 'asset-lending-manager' ),
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);

		// Device type (e.g. telescope, book, eyepiece).
		register_taxonomy(
			ALM_DEVICE_TYPE_TAXONOMY_SLUG,
			ALM_DEVICE_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Device Types', 'asset-lending-manager' ),
					'singular_name' => __( 'Device Type', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);

		// Device state: available, loaned, maintenance, etc.
		register_taxonomy(
			ALM_DEVICE_STATE_TAXONOMY_SLUG,
			ALM_DEVICE_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Device States', 'asset-lending-manager' ),
					'singular_name' => __( 'Device State', 'asset-lending-manager' ),
				),
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}

	/**
	 * Check if a device is a kit.
	 *
	 * @param int $device_id Device post ID.
	 * @return bool
	 */
	public function is_kit( $device_id ) {
		return has_term( 'kit', 'alm_device_type', $device_id );
	}

	/**
	 * Check if a device is a component.
	 *
	 * @param int $device_id Device post ID.
	 * @return bool
	 */
	public function is_component( $device_id ) {
		return has_term( 'component', 'alm_device_type', $device_id );
	}

	/**
	 * Get current device state.
	 *
	 * @param int $device_id Device post ID.
	 * @return string|null
	 */
	public function get_device_state( $device_id ) {
		$terms = get_the_terms( $device_id, 'alm_state' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		return $terms[0]->slug;
	}
}
