<?php
/**
 * Asset Lending Manager - Asset Manager
 *
 * Handles asset domain logic.
 * Responsible for:
 * - Registering the alm_asset Custom Post Type
 * - Registering asset-related taxonomies
 * - Providing read-only helpers for asset properties
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-alm-acf-asset-adapter.php';
require_once 'class-alm-installer.php';

/**
 * Class ALM_Asset_Manager
 */
class ALM_Asset_Manager {

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
		$this->acf_adapter = new ALM_ACF_Asset_Adapter();
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
				'register_asset_fields',
			)
		);
	}

	/**
	 * Register the alm_asset Custom Post Type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => __( 'Assets', 'asset-lending-manager' ),
			'singular_name'      => __( 'Asset', 'asset-lending-manager' ),
			'add_new'            => __( 'Add New', 'asset-lending-manager' ),
			'add_new_item'       => __( 'Add New Asset', 'asset-lending-manager' ),
			'edit_item'          => __( 'Edit Asset', 'asset-lending-manager' ),
			'new_item'           => __( 'New Asset', 'asset-lending-manager' ),
			'view_item'          => __( 'View Asset', 'asset-lending-manager' ),
			'search_items'       => __( 'Search Assets', 'asset-lending-manager' ),
			'not_found'          => __( 'No assets found', 'asset-lending-manager' ),
			'not_found_in_trash' => __( 'No assets found in Trash', 'asset-lending-manager' ),
			'menu_name'          => __( 'Assets', 'asset-lending-manager' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => true,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'menu_icon'       => ALM_ASSET_ICON,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => ALM_ASSET_CPT_SLUG,
			'map_meta_cap'    => true,
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'asset' ),
		);

		register_post_type( ALM_ASSET_CPT_SLUG, $args );
	}

	/**
	 * Register asset-related taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {

		// Logical asset structure: component or kit.
		register_taxonomy(
			ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
			ALM_ASSET_CPT_SLUG,
			array(
				'labels'          => array(
					'name'          => __( 'Asset Structures', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Structure', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_ASSET,
					'edit_terms'   => ALM_EDIT_ASSET,
					'delete_terms' => ALM_EDIT_ASSET,
					'assign_terms' => ALM_EDIT_ASSET,
				),
			)
		);

		// Asset type (e.g. telescope, book, eyepiece).
		register_taxonomy(
			ALM_ASSET_TYPE_TAXONOMY_SLUG,
			ALM_ASSET_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Asset Types', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Type', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_ASSET,
					'edit_terms'   => ALM_EDIT_ASSET,
					'delete_terms' => ALM_EDIT_ASSET,
					'assign_terms' => ALM_EDIT_ASSET,
				),
			)
		);

		// Asset state: available, loaned, maintenance, etc.
		register_taxonomy(
			ALM_ASSET_STATE_TAXONOMY_SLUG,
			ALM_ASSET_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Asset States', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset State', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_ASSET,
					'edit_terms'   => ALM_EDIT_ASSET,
					'delete_terms' => ALM_EDIT_ASSET,
					'assign_terms' => ALM_EDIT_ASSET,
				),
			)
		);

		// Asset level: basic, intermediate, advanced, etc.
		register_taxonomy(
			ALM_ASSET_LEVEL_TAXONOMY_SLUG,
			ALM_ASSET_CPT_SLUG,
			array(
				'labels' => array(
					'name'          => __( 'Asset Levels', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Level', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'capabilities' => array(
					'manage_terms' => ALM_EDIT_ASSET,
					'edit_terms'   => ALM_EDIT_ASSET,
					'delete_terms' => ALM_EDIT_ASSET,
					'assign_terms' => ALM_EDIT_ASSET,
				),
			)
		);

	}

	/**
	 * Return a view model of a asset for frontend display.
	 *
	 * @param int $asset_id ID of the asset post.
	 * @return object|null Returns an object with title, permalink, thumbnail, and taxonomy arrays, or null if not found.
	 */
	public static function get_asset_wrapper( $asset_id ) {
		$asset = get_post( $asset_id );

		if ( ! $asset || ALM_ASSET_CPT_SLUG !== $asset->post_type || 'publish' !== $asset->post_status ) {
			return null;
		}

		$wrapper            = new stdClass();
		$wrapper->id        = $asset->ID;
		$wrapper->title     = get_the_title( $asset );
		$wrapper->permalink = get_permalink( $asset );
		$wrapper->content   = apply_filters( 'the_content', $asset->post_content );
		// Manage the asset image.
		$thumbnail_size = 'thumbnail';
		if ( has_post_thumbnail( $asset ) ) {
			$wrapper->thumbnail = get_the_post_thumbnail( $asset, $thumbnail_size );
		} else {
			$wrapper->thumbnail = sprintf(
				'<img src="%s" alt="%s" class="alm-asset-default-thumbnail">',
				esc_url( ALM_PLUGIN_URL . 'assets/img/default_asset_color_bw.png' ),
				esc_attr( get_the_title( $asset ) )
			);
		}

		// Load the main taxonomies.
		$taxonomies = array(
			ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
			ALM_ASSET_TYPE_TAXONOMY_SLUG,
			ALM_ASSET_STATE_TAXONOMY_SLUG,
			ALM_ASSET_LEVEL_TAXONOMY_SLUG,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $asset, $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$wrapper->{$taxonomy} = wp_list_pluck( $terms, 'name' );
			} else {
				$wrapper->{$taxonomy} = array();
			}
		}

		return $wrapper;
	}


	/**
	 * Return an array containing all the custom asset fields.
	 *
	 * @param [type] $asset_id - The asset id.
	 * @return array
	 */
	public static function get_asset_custom_fields( $asset_id ) {
		$asset_fields = array();
		/**
		 * ACF fields (ordered as defined in adapter).
		 */
		$order = array(
			'manufacturer',
			'model',
			'data_acquisto',
			'cost',
			'dimensions',
			'weight',
			'location',
			'user_manual',
			'technical_data_sheet',
			'serial_number',
			'external_code',
			'notes',
			'components',
		);
		$field_objects = array();
		// $manufacturer_value = (string) get_field( 'manufacturer', $asset_id );
		// Get all the custom fields for this asset.
		if ( function_exists( 'get_field_objects' ) ) {
			$tmp = get_field_objects( $asset_id );
			if ( is_array( $tmp ) ) {
				$field_objects = $tmp;
			}
		}
		// Build an array with the fields ordered based on $order.
		if ( ! empty( $field_objects ) ) {
			foreach ( $order as $field_name ) {
				if ( ! isset( $field_objects[ $field_name ] ) ) {
					continue;
				}
				$field = $field_objects[ $field_name ];
				$label = isset( $field['label'] ) ? (string) $field['label'] : $field_name;
				$value = $field['value'] ?? null;
				// Normalize empty values.
				$is_empty = ( null === $value || '' === $value || ( is_array( $value ) && empty( $value ) ) );
				if ( $is_empty ) {
					continue;
				}
				$asset_fields[] = array(
					'name'  => $field_name,
					'label' => $label,
					'type'  => isset( $field['type'] ) ? (string) $field['type'] : '',
					'value' => $value,
				);
			}
			// Add a field 'kit' if this asset is a component of a kit.
			if ( has_term( ALM_ASSET_COMPONENT_SLUG, ALM_ASSET_STRUCTURE_TAXONOMY_SLUG, $asset_id ) ) {
				$args = array(
					'post_type'      => ALM_ASSET_CPT_SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => $field_name,
							'value'   => '"' . $asset_id . '"',
							'compare' => 'LIKE',
						),
					),
				);
				$kit_result = new WP_Query( $args );
				if ( $kit_result->have_posts() ) {
					$item = array(
						'name'  => 'kit',
						'label' => 'Membership kit',
						'type'  => 'post_object',
						'value' => $kit_result->posts,
					);
					array_push( $asset_fields, $item );
				}
			}
		}
		return $asset_fields;
	}

	/**
	 * Return the classes related to the asset states.
	 *
	 * @return array
	 */
	public static function get_state_classes() {
		return array(
			'available'   => 'alm-state-available',
			'on-loan'     => 'alm-state-on-loan',
			'maintenance' => 'alm-state-maintenance',
			'retired'     => 'alm-state-retired',
		);
	}
}
