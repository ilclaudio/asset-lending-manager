<?php
/**
 * Asset Lending Manager - Asset Manager
 *
 * Handles asset domain logic.
 * Responsible for:
 * - Registering the almgr_asset Custom Post Type
 * - Registering asset-related taxonomies
 * - Providing read-only helpers for asset properties
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-almgr-acf-asset-adapter.php';
require_once 'class-almgr-installer.php';

/**
 * Class ALMGR_Asset_Manager
 */
class ALMGR_Asset_Manager {

	/**
	 * ACF Adapter instance.
	 *
	 * @var ALMGR_ACF_Asset_Adapter
	 */
	private $acf_adapter;

	/**
	 * Class initialization.
	 */
	public function __construct() {
		$this->acf_adapter = new ALMGR_ACF_Asset_Adapter();
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
		ALMGR_Installer::create_default_terms();
		// Flush rewrite rules now that CPT and taxonomies exist.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Flush rewrite rules so the CPT slug is removed from WordPress routing.
		flush_rewrite_rules();
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
	 * Register the almgr_asset Custom Post Type.
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
			'menu_icon'       => ALMGR_ASSET_ICON,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => ALMGR_ASSET_CPT_SLUG,
			'map_meta_cap'    => true,
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'asset' ),
		);

		register_post_type( ALMGR_ASSET_CPT_SLUG, $args );
	}

	/**
	 * Register asset-related taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {

		// Logical asset structure: component or kit.
		register_taxonomy(
			ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
			ALMGR_ASSET_CPT_SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'Asset Structures', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Structure', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities'      => array(
					'manage_terms' => ALMGR_EDIT_ASSET,
					'edit_terms'   => ALMGR_EDIT_ASSET,
					'delete_terms' => ALMGR_EDIT_ASSET,
					'assign_terms' => ALMGR_EDIT_ASSET,
				),
			)
		);

		// Asset type (e.g. telescope, book, eyepiece).
		register_taxonomy(
			ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			ALMGR_ASSET_CPT_SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'Asset Types', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Type', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities'      => array(
					'manage_terms' => ALMGR_EDIT_ASSET,
					'edit_terms'   => ALMGR_EDIT_ASSET,
					'delete_terms' => ALMGR_EDIT_ASSET,
					'assign_terms' => ALMGR_EDIT_ASSET,
				),
			)
		);

		// Asset state: available, loaned, maintenance, etc.
		register_taxonomy(
			ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			ALMGR_ASSET_CPT_SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'Asset States', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset State', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'capabilities'      => array(
					'manage_terms' => ALMGR_EDIT_ASSET,
					'edit_terms'   => ALMGR_EDIT_ASSET,
					'delete_terms' => ALMGR_EDIT_ASSET,
					'assign_terms' => ALMGR_EDIT_ASSET,
				),
			)
		);

		// Asset level: basic, intermediate, advanced, etc.
		register_taxonomy(
			ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
			ALMGR_ASSET_CPT_SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'Asset Levels', 'asset-lending-manager' ),
					'singular_name' => __( 'Asset Level', 'asset-lending-manager' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'capabilities'      => array(
					'manage_terms' => ALMGR_EDIT_ASSET,
					'edit_terms'   => ALMGR_EDIT_ASSET,
					'delete_terms' => ALMGR_EDIT_ASSET,
					'assign_terms' => ALMGR_EDIT_ASSET,
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

		if ( ! $asset || ALMGR_ASSET_CPT_SLUG !== $asset->post_type || 'publish' !== $asset->post_status ) {
			return null;
		}

		$wrapper            = new stdClass();
		$wrapper->id        = $asset->ID;
		$wrapper->title     = get_the_title( $asset );
		$wrapper->permalink = get_permalink( $asset );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress filter.
		$wrapper->content = apply_filters( 'the_content', $asset->post_content );
		// Manage the asset image.
		$thumbnail_size = 'thumbnail';
		if ( has_post_thumbnail( $asset ) ) {
			$wrapper->thumbnail = get_the_post_thumbnail( $asset, $thumbnail_size );
		} else {
			$wrapper->thumbnail = sprintf(
				'<img src="%s" alt="%s" class="alm-asset-default-thumbnail">',
				esc_url( ALMGR_PLUGIN_URL . 'assets/img/default_asset_color_bw.png' ),
				esc_attr( get_the_title( $asset ) )
			);
		}

		// Load the main taxonomies.
		$taxonomies = array(
			ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
			ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $asset, $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				// State labels are translated at runtime from slug, not from stored term name.
				if ( ALMGR_ASSET_STATE_TAXONOMY_SLUG === $taxonomy ) {
					$wrapper->almgr_state       = array();
					$wrapper->almgr_state_slugs = wp_list_pluck( $terms, 'slug' );
					foreach ( $terms as $term ) {
						$term_slug              = (string) $term->slug;
						$term_name              = (string) $term->name;
						$wrapper->almgr_state[] = self::get_state_label( $term_slug, $term_name );
					}
				} elseif ( ALMGR_ASSET_LEVEL_TAXONOMY_SLUG === $taxonomy ) {
					$wrapper->almgr_level = array();
					foreach ( $terms as $term ) {
						$term_slug              = (string) $term->slug;
						$term_name              = (string) $term->name;
						$wrapper->almgr_level[] = self::get_level_label( $term_slug, $term_name );
					}
				} else {
					$wrapper->{$taxonomy} = wp_list_pluck( $terms, 'name' );
				}
			} else {
				$wrapper->{$taxonomy} = array();
				if ( ALMGR_ASSET_STATE_TAXONOMY_SLUG === $taxonomy ) {
					$wrapper->almgr_state       = array();
					$wrapper->almgr_state_slugs = array();
				}
				if ( ALMGR_ASSET_LEVEL_TAXONOMY_SLUG === $taxonomy ) {
					$wrapper->almgr_level = array();
				}
			}
		}

		// Load current owner.
		$owner_id            = (int) get_post_meta( $asset->ID, '_almgr_current_owner', true );
		$wrapper->owner_id   = $owner_id;
		$owner_data          = $owner_id > 0 ? get_userdata( $owner_id ) : false;
		$wrapper->owner_name = $owner_data ? $owner_data->display_name : '';

		// Load parent kit membership (only populated when this asset is a component).
		$wrapper->parent_kits = array();
		if ( has_term( ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, $asset_id ) ) {
			$almgr_kit_query = new WP_Query(
				array(
					'post_type'      => ALMGR_ASSET_CPT_SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'components',
							'value'   => '"' . $asset_id . '"',
							'compare' => 'LIKE',
						),
					),
				)
			);
			foreach ( $almgr_kit_query->posts as $almgr_kit_id ) {
				$almgr_kit_id             = (int) $almgr_kit_id;
				$wrapper->parent_kits[] = array(
					'id'        => $almgr_kit_id,
					'title'     => get_the_title( $almgr_kit_id ),
					'permalink' => get_permalink( $almgr_kit_id ),
				);
			}
			wp_reset_postdata();
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
		$order             = array(
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
		$translated_labels = array(
			'manufacturer'         => __( 'Manufacturer', 'asset-lending-manager' ),
			'model'                => __( 'Model', 'asset-lending-manager' ),
			'data_acquisto'        => __( 'Purchase date', 'asset-lending-manager' ),
			'cost'                 => __( 'Cost', 'asset-lending-manager' ),
			'dimensions'           => __( 'Dimensions', 'asset-lending-manager' ),
			'weight'               => __( 'Weight', 'asset-lending-manager' ),
			'location'             => __( 'Location', 'asset-lending-manager' ),
			'user_manual'          => __( 'User manual', 'asset-lending-manager' ),
			'technical_data_sheet' => __( 'Technical data sheet', 'asset-lending-manager' ),
			'serial_number'        => __( 'Serial number', 'asset-lending-manager' ),
			'external_code'        => __( 'External code', 'asset-lending-manager' ),
			'notes'                => __( 'Notes', 'asset-lending-manager' ),
			'components'           => __( 'Components', 'asset-lending-manager' ),
		);
		$field_objects     = ALMGR_ACF_Asset_Adapter::get_custom_fields( $asset_id );
		// Build an array with the fields ordered based on $order.
		if ( ! empty( $field_objects ) ) {
			foreach ( $order as $field_name ) {
				if ( ! isset( $field_objects[ $field_name ] ) ) {
					continue;
				}
				$field = $field_objects[ $field_name ];
				$label = isset( $translated_labels[ $field_name ] )
					? $translated_labels[ $field_name ]
					: ( isset( $field['label'] ) ? (string) $field['label'] : $field_name );
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
			if ( has_term( ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, $asset_id ) ) {
				$args       = array(
					'post_type'      => ALMGR_ASSET_CPT_SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'components',
							'value'   => '"' . $asset_id . '"',
							'compare' => 'LIKE',
						),
					),
				);
				$kit_result = new WP_Query( $args );
				if ( $kit_result->have_posts() ) {
					$item = array(
						'name'  => 'kit',
						'label' => __( 'Membership kit', 'asset-lending-manager' ),
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
	 * Return the human-readable identifier code for an asset.
	 *
	 * The code is composed of the ALMGR_ASSET_CODE_PREFIX constant followed by a
	 * hyphen and the WordPress post ID zero-padded to 8 digits.
	 * Example: prefix "ALMGR" + asset ID 45 -> "ALMGR-00000045".
	 *
	 * The code is always computed at runtime from the post ID (which never
	 * changes in WordPress) so no storage is needed.
	 *
	 * @param int $asset_id WordPress post ID of the asset.
	 * @return string Human-readable asset code.
	 */
	public static function get_asset_code( $asset_id ) {
		$prefix   = ALMGR_ASSET_CODE_PREFIX;
		$instance = ALMGR_Plugin_Manager::get_instance();
		if ( $instance ) {
			$settings = $instance->get_module( 'settings' );
			if ( $settings ) {
				$prefix = (string) $settings->get( 'asset.code_prefix', ALMGR_ASSET_CODE_PREFIX );
			}
		}
		return sprintf( ALMGR_ASSET_CODE_FORMAT, $prefix, (int) $asset_id );
	}

	/**
	 * Return the WordPress post ID from a human-readable asset code.
	 *
	 * Reverse of get_asset_code(). Extracts the numeric part after the last
	 * hyphen, validates the post exists and is a published almgr_asset.
	 *
	 * @param string $code Human-readable asset code (e.g. "ALMGR-00000052").
	 * @return int Post ID on success, 0 on failure.
	 */
	public static function get_asset_id_from_code( $code ) {
		$code = sanitize_text_field( $code );
		$pos  = strrpos( $code, '-' );
		if ( false === $pos ) {
			return 0;
		}
		$post_id = absint( substr( $code, $pos + 1 ) );
		if ( $post_id <= 0 ) {
			return 0;
		}
		$post = get_post( $post_id );
		if ( ! $post || ALMGR_ASSET_CPT_SLUG !== $post->post_type || 'publish' !== $post->post_status ) {
			return 0;
		}
		return $post_id;
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

	/**
	 * Return translated state label from state slug.
	 *
	 * This avoids locale-dependent display issues when taxonomy terms were created
	 * in a different language and their DB names are not aligned with current locale.
	 *
	 * @param string $state_slug State slug (e.g. available, on-loan, maintenance, retired).
	 * @param string $fallback   Fallback label (usually stored term name).
	 * @return string
	 */
	public static function get_state_label( $state_slug, $fallback = '' ) {
		$state_slug = (string) $state_slug;
		$fallback   = (string) $fallback;

		$labels = array(
			'available'   => __( 'Available', 'asset-lending-manager' ),
			'on-loan'     => __( 'On loan', 'asset-lending-manager' ),
			'maintenance' => __( 'Maintenance', 'asset-lending-manager' ),
			'retired'     => __( 'Retired', 'asset-lending-manager' ),
		);

		if ( isset( $labels[ $state_slug ] ) ) {
			return $labels[ $state_slug ];
		}

		return '' !== $fallback ? $fallback : $state_slug;
	}

	/**
	 * Return translated level label from level slug.
	 *
	 * @param string $level_slug Level slug (e.g. basic, intermediate, advanced).
	 * @param string $fallback   Fallback label (usually stored term name).
	 * @return string
	 */
	public static function get_level_label( $level_slug, $fallback = '' ) {
		$level_slug = (string) $level_slug;
		$fallback   = (string) $fallback;

		$labels = array(
			'basic'        => __( 'Basic', 'asset-lending-manager' ),
			'intermediate' => __( 'Intermediate', 'asset-lending-manager' ),
			'advanced'     => __( 'Advanced', 'asset-lending-manager' ),
		);

		if ( isset( $labels[ $level_slug ] ) ) {
			return $labels[ $level_slug ];
		}

		return '' !== $fallback ? $fallback : $level_slug;
	}
}
