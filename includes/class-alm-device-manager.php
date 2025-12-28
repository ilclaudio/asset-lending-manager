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

defined( 'ABSPATH' ) || exit;

require_once 'class-alm-acf-device-adapter.php';
require_once 'class-alm-installer.php';

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
		ALM_Installer::create_default_terms();
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
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'device' ),
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
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_DEVICE,
					'edit_terms'   => ALM_EDIT_DEVICE,
					'delete_terms' => ALM_EDIT_DEVICE,
					'assign_terms' => ALM_EDIT_DEVICE,
				),
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
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_DEVICE,
					'edit_terms'   => ALM_EDIT_DEVICE,
					'delete_terms' => ALM_EDIT_DEVICE,
					'assign_terms' => ALM_EDIT_DEVICE,
				),
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
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_DEVICE,
					'edit_terms'   => ALM_EDIT_DEVICE,
					'delete_terms' => ALM_EDIT_DEVICE,
					'assign_terms' => ALM_EDIT_DEVICE,
				),
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
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_DEVICE,
					'edit_terms'   => ALM_EDIT_DEVICE,
					'delete_terms' => ALM_EDIT_DEVICE,
					'assign_terms' => ALM_EDIT_DEVICE,
				),
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
		return has_term( 'kit', ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG, $device_id );
	}

	/**
	 * Check if a device is a component.
	 *
	 * @param int $device_id Device post ID.
	 * @return bool
	 */
	public function is_component( $device_id ) {
		return has_term( 'component', ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG, $device_id );
	}

	/**
	 * Get current device state.
	 *
	 * @param int $device_id Device post ID.
	 * @return string|null
	 */
	public function get_device_state( $device_id ) {
		$terms = get_the_terms( $device_id, ALM_DEVICE_STATE_TAXONOMY_SLUG );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		return $terms[0]->slug;
	}

	/**
	 * Get all published devices as wrapper objects.
	 *
	 * @param array $args Optional. Query arguments to customize the device retrieval.
	 *   Accepts any WP_Query parameter. Common options:
	 *   - 'posts_per_page': Number of devices to retrieve (default: -1 for all)
	 *   - 'orderby': Sort field (default: 'title')
	 *   - 'order': Sort direction (default: 'ASC')
	 *   - 'tax_query': Taxonomy query for filtering
	 * @return array Array of device wrapper objects from get_device_wrapper()
	 */
	public static function get_devices( $args = array() ) {
		// Default query arguments.
		$defaults = array(
			'post_type'      => ALM_DEVICE_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// Merge user args with defaults.
		$query_args = wp_parse_args( $args, $defaults );

		// Execute query.
		$query = new WP_Query( $query_args );

		// Prepare devices array using wrappers.
		$devices = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$device = self::get_device_wrapper( get_the_ID() );
				if ( $device ) {
					$devices[] = $device;
				}
			}
			wp_reset_postdata();
		}

		return $devices;
	}

	/**
	 * Return a view model of a device for frontend display.
	 *
	 * @param int $device_id ID of the device post.
	 * @return object|null Returns an object with title, permalink, thumbnail, and taxonomy arrays, or null if not found.
	 */
	public static function get_device_wrapper( $device_id ) {
		$device = get_post( $device_id );

		if ( ! $device || ALM_DEVICE_CPT_SLUG !== $device->post_type || 'publish' !== $device->post_status ) {
			return null;
		}

		$wrapper            = new stdClass();
		$wrapper->id        = $device->ID;
		$wrapper->title     = get_the_title( $device );
		$wrapper->permalink = get_permalink( $device );
		$wrapper->content   = apply_filters( 'the_content', $device->post_content );
		// Manage the device image.
		$thumbnail_size = 'thumbnail';
		if ( has_post_thumbnail( $device ) ) {
			$wrapper->thumbnail = get_the_post_thumbnail( $device, $thumbnail_size );
		} else {
			$wrapper->thumbnail = sprintf(
				'<img src="%s" alt="%s" class="alm-device-default-thumbnail">',
				esc_url( ALM_PLUGIN_URL . 'assets/img/default_device_color_bw.png' ),
				esc_attr( get_the_title( $device ) )
			);
		}

		// Load the main taxonomies.
		$taxonomies = array(
			ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG,
			ALM_DEVICE_TYPE_TAXONOMY_SLUG,
			ALM_DEVICE_STATE_TAXONOMY_SLUG,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $device, $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$wrapper->{$taxonomy} = wp_list_pluck( $terms, 'name' );
			} else {
				$wrapper->{$taxonomy} = array();
			}
		}

		return $wrapper;
	}
}
