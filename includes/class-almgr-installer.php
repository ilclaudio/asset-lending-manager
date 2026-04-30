<?php
/**
 * ALMGR Setup Manager.
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
 * Class ALMGR_Installer
 */
class ALMGR_Installer {

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
		self::create_loan_requests_table();
		self::create_loan_requests_history_table();
	}

	/**
	 * Create loan requests table.
	 * This method is idempotent - safe to call multiple times.
	 *
	 * @return void
	 */
	public static function create_loan_requests_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'almgr_loan_requests';
		$table_exists    = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema existence check; SHOW TABLES has no WP abstraction and must reflect real-time state.
		$sql             = "CREATE TABLE $table_name (
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
			KEY status (status),
			KEY asset_status_request_date (asset_id, status, request_date),
			KEY requester_request_date (requester_id, request_date),
			KEY requester_status_request_date (requester_id, status, request_date)
		) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		// Verify table was created or updated.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema existence check; SHOW TABLES has no WP abstraction and must reflect real-time state.
			if ( $table_exists ) {
				ALMGR_Logger::info( 'Table almgr_loan_requests schema verified/updated successfully.' );
			} else {
				ALMGR_Logger::info( 'Table almgr_loan_requests created successfully.' );
			}
		} else {
			ALMGR_Logger::error( 'Failed to create table almgr_loan_requests' );
		}
	}

	/**
	 * Drop plugin database tables.
	 * Called on plugin uninstall.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;
		$tables = array(
			$wpdb->prefix . 'almgr_loan_requests_history',
			$wpdb->prefix . 'almgr_loan_requests',
		);
		foreach ( $tables as $table ) {
			if ( ! self::is_safe_table_identifier( $table ) ) {
				ALMGR_Logger::warning(
					'Skipping table drop due to invalid identifier.',
					array( 'table' => $table )
				);
				continue;
			}

			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Plugin uninstall schema cleanup; DROP TABLE has no WP abstraction; table name validated by is_safe_table_identifier().
			ALMGR_Logger::info( "Dropped table $table" );
		}
	}

	/**
	 * Validate SQL table identifier used for schema operations.
	 *
	 * Accepts only alphanumeric characters, underscore, and dollar sign.
	 *
	 * @param string $table Table name to validate.
	 * @return bool
	 */
	private static function is_safe_table_identifier( $table ) {
		return 1 === preg_match( '/^[A-Za-z0-9_$]+$/', $table );
	}


	/**
	 * Create loan requests history table.
	 * This method is idempotent - safe to call multiple times.
	 *
	 * @return void
	 */
	public static function create_loan_requests_history_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'almgr_loan_requests_history';
		$charset_collate = $wpdb->get_charset_collate();

		$table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema existence check; SHOW TABLES has no WP abstraction and must reflect real-time state.

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			loan_request_id bigint(20) unsigned NOT NULL,
			asset_id bigint(20) unsigned NOT NULL,
			requester_id bigint(20) unsigned NOT NULL,
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL,
			message text,
			changed_at datetime NOT NULL,
			changed_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY (id),
			KEY loan_request_id (loan_request_id),
			KEY asset_id (asset_id),
			KEY requester_id (requester_id),
			KEY owner_id (owner_id),
			KEY changed_by (changed_by),
			KEY status (status),
			KEY asset_changed_at (asset_id, changed_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Verify creation or schema update.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema existence check; SHOW TABLES has no WP abstraction and must reflect real-time state.
			if ( $table_exists ) {
				ALMGR_Logger::info( 'Table almgr_loan_requests_history schema verified/updated successfully.' );
			} else {
				ALMGR_Logger::info( 'Table almgr_loan_requests_history created successfully.' );
			}
		} else {
			ALMGR_Logger::error( 'Failed to create table almgr_loan_requests_history.' );
		}
	}

	/**
	 * Public entry point.
	 *
	 * Ensures that all default taxonomy terms required by ALMGR
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
			ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => ALMGR_ASSET_COMPONENT_SLUG,
					'label' => __( 'Component', 'asset-lending-manager' ),
				),
				array(
					'slug'  => ALMGR_ASSET_KIT_SLUG,
					'label' => __( 'Kit', 'asset-lending-manager' ),
				),
			)
		);

		// Asset types.
		self::add_default_terms(
			ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
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
			ALMGR_ASSET_STATE_TAXONOMY_SLUG,
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
			ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
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
