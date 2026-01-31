<?php
/**
 * ALM Setup Manager.
 *
 * Handles initial setup tasks for the Asset Lending Manager plugin.
 * Ensures that required default taxonomy terms exist.
 *
 * This class is intentionally idempotent: all operations can be safely
 * executed multiple times without causing side effects.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALM_Installer
 */
class ALM_Installer {

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// Do nothing.
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
	 * Create database tables needed by the plugin.
	 * This method is idempotent - safe to call multiple times.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'alm_loan_requests';
		// Check if table already exists.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			ALM_Logger::debug( 'Table alm_loan_requests already exists, skipping creation.' );
			return;
		}
		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			asset_id bigint(20) unsigned NOT NULL,
			requester_id bigint(20) unsigned NOT NULL,
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			request_date datetime NOT NULL,
			request_message text,
			status varchar(20) NOT NULL DEFAULT 'pending',
			response_date datetime DEFAULT NULL,
			response_message text,
			PRIMARY KEY  (id),
			KEY asset_id (asset_id),
			KEY requester_id (requester_id),
			KEY owner_id (owner_id),
			KEY status (status)
		) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		// Verify table was created.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			ALM_Logger::info( 'Table alm_loan_requests created successfully.' );
		} else {
			ALM_Logger::error( 'Failed to create table alm_loan_requests' );
		}
	}

	/**
	 * Public entry point.
	 *
	 * Ensures that all default taxonomy terms required by ALM
	 * are present in the system.
	 *
	 * This method can be safely called:
	 * - on plugin activation
	 * - during maintenance operations
	 * - after accidental data deletion
	 *
	 * @return void
	 */
	public static function create_default_terms(): void {

		// Asset structure.
		self::add_default_terms(
			ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => ALM_ASSET_COMPONENT_SLUG,
					'label' => __( 'Component', 'asset-lending-manager' ),
				),
				array(
					'slug'  => ALM_ASSET_KIT_SLUG,
					'label' => __( 'Kit', 'asset-lending-manager' ),
				),
			)
		);

		// Asset types.
		self::add_default_terms(
			ALM_ASSET_TYPE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'telescope',
					'label' => __( 'Telescope', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'ocular',
					'label' => __( 'Ocular', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'refractor',
					'label' => __( 'Refractor', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'optical-tube',
					'label' => __( 'Optical Tube', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'binoculars',
					'label' => __( 'Binoculars', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'tripod',
					'label' => __( 'Tripod', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'filter',
					'label' => __( 'Filter', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'accessory',
					'label' => __( 'Accessory', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'book',
					'label' => __( 'Book', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'magazine',
					'label' => __( 'Magazine', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'mount',
					'label' => __( 'Mount', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'generic',
					'label' => __( 'Generic', 'asset-lending-manager' ),
				),
			)
		);

		// Asset state.
		self::add_default_terms(
			ALM_ASSET_STATE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'on-loan',
					'label' => __( 'On loan', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'available',
					'label' => __( 'Available', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'maintenance',
					'label' => __( 'Maintenance', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'retired',
					'label' => __( 'Retired', 'asset-lending-manager' ),
				),
			)
		);

		// Asset level.
		self::add_default_terms(
			ALM_ASSET_LEVEL_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'basic',
					'label' => __( 'Basic', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'intermediate',
					'label' => __( 'Intermediate', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'advanced',
					'label' => __( 'Advanced', 'asset-lending-manager' ),
				),
			)
		);
	}

	/**
	 * Adds terms to a taxonomy if they do not exist.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param array  $terms         Array of arrays: [ 'slug' => string, 'label' => string ].
	 * @return void
	 */
	private static function add_default_terms( string $taxonomy_slug, array $terms ): void {
		if ( ! taxonomy_exists( $taxonomy_slug ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			$term_exists = term_exists( $term['slug'], $taxonomy_slug );
			if ( $term_exists ) {
				continue;
			}
			wp_insert_term(
				$term['label'],
				$taxonomy_slug,
				array(
					'slug' => $term['slug'],
				)
			);
		}
	}
}
