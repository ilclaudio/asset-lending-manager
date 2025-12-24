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

require_once 'class-alm-acf-device-adapter.php';
require_once 'class-alm-setup-manager.php';

/**
 * Class ALM_Device_Manager
 */
class ALM_Device_Manager {

	/**
	 * ACF Adapter instance.
	 *
	 * @var ALM_ACF_Adapter
	 */
	private $acf_adapter;

	/**
	 * Class initialization.
	 */
	public function __construct() {
		$this->acf_adapter = new ALM_ACF_Device_Adapter();
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// CPTs and taxonomies must be registered at runtime ( in the register() method).
		// However to create default taxonomies during the plugin activation.
		// we have to register CPT and taxonomies also here.
		$this->register_post_type();
		$this->register_taxonomies();
		// Create default terms.
		ALM_Setup_Manager::create_default_terms();
		// Flush rewrite rules now that CPT and taxonomies exist.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Do nothing.
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Register post types.
		add_action( 'init', array( $this, 'register_post_type' ) );
		// Register taxonomies.
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		// Register ACF fields after ACF is initialized.
		add_action(
			'acf/include_fields',
			array(
				$this->acf_adapter,
				'register_device_fields',
			)
		);
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
			'public'          => true,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'menu_icon'       => ALM_DEVICE_ICON,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => ALM_DEVICE_CPT_SLUG,
			'map_meta_cap'    => true,
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
				'labels'          => array(
					'name'          => __( 'Device Structures', 'asset-lending-manager' ),
					'singular_name' => __( 'Device Structure', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
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
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);

		// Device level: basic, intermediate, advanced, etc.
		register_taxonomy(
			ALM_DEVICE_LEVEL_TAXONOMY_SLUG,
			ALM_DEVICE_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Device Levels', 'asset-lending-manager' ),
					'singular_name' => __( 'Device Level', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
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
