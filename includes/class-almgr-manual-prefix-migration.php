<?php
/**
 * Manual one-shot migration helper for the alm_ -> almgr_ prefix transition.
 *
 * Purpose:
 * - Migrate legacy plugin settings from `alm_settings` to `almgr_settings`.
 * - Ensure the new roles `almgr_member` and `almgr_operator` exist.
 * - Ensure the new `almgr_*` capabilities are assigned to administrators and new roles.
 * - Add the new roles to users who still have the legacy roles `alm_member` and `alm_operator`.
 *
 * Safety characteristics:
 * - Manual only: never runs automatically during normal plugin bootstrap.
 * - Protected: requires wp-admin and an authenticated user with `manage_options`.
 * - Idempotent: safe to run multiple times on the same site.
 * - Non-destructive: does not remove legacy roles and does not delete the old
 *   `alm_settings` option.
 *
 * How to trigger:
 * - Visit `/wp-admin/?almgr_run_prefix_migration=1`
 *
 * Removal plan:
 * - After the two existing sites have been checked and migrated successfully,
 *   this file and its bootstrap line in the main plugin file can be removed.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manual migration runner for legacy prefixed settings and roles.
 */
class ALMGR_Manual_Prefix_Migration {

	/**
	 * Query arg used to trigger the migration.
	 *
	 * @var string
	 */
	const QUERY_ARG = 'almgr_run_prefix_migration';

	/**
	 * Legacy option name.
	 *
	 * @var string
	 */
	const LEGACY_SETTINGS_OPTION = 'alm_settings';

	/**
	 * New option name.
	 *
	 * @var string
	 */
	const NEW_SETTINGS_OPTION = 'almgr_settings';

	/**
	 * Legacy and new asset post type slugs.
	 *
	 * @var string
	 */
	const LEGACY_ASSET_POST_TYPE = 'alm_asset';
	const NEW_ASSET_POST_TYPE    = 'almgr_asset';

	/**
	 * Register the manual trigger.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_run' ) );
	}

	/**
	 * Run the migration only when explicitly requested from wp-admin.
	 *
	 * @return void
	 */
	public static function maybe_run() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Manual one-shot trigger, additionally gated by is_admin() and manage_options.
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run this migration.', 'asset-lending-manager' ), 403 );
		}

		$report = self::run();
		self::render_report( $report );
	}

	/**
	 * Execute the migration steps and collect a human-readable report.
	 *
	 * @return array<int, string>
	 */
	private static function run() {
		$report = array();

		$report[] = 'Manual prefix migration started.';
		$report   = array_merge( $report, self::migrate_settings_option() );
		$report   = array_merge( $report, self::ensure_new_roles_and_capabilities() );
		$report   = array_merge( $report, self::migrate_users_to_new_roles() );
		$report   = array_merge( $report, self::migrate_asset_post_type() );
		$report   = array_merge( $report, self::migrate_asset_taxonomies() );
		$report   = array_merge( $report, self::migrate_asset_meta_keys() );
		$report   = array_merge( $report, self::migrate_loan_tables() );
		$report   = array_merge( $report, self::migrate_post_content_identifiers() );
		$report   = array_merge( $report, self::refresh_rewrite_rules() );
		$report[] = 'Manual prefix migration completed.';

		return $report;
	}

	/**
	 * Migrate legacy settings option to the new option name when needed.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_settings_option() {
		$messages      = array();
		$legacy_exists = get_option( self::LEGACY_SETTINGS_OPTION, null );
		$new_exists    = get_option( self::NEW_SETTINGS_OPTION, null );

		if ( null === $legacy_exists ) {
			$messages[] = 'Settings: legacy option alm_settings not found.';
			return $messages;
		}

		if ( null !== $new_exists ) {
			$messages[] = 'Settings: almgr_settings already exists, no copy needed.';
			return $messages;
		}

		if ( is_array( $legacy_exists ) ) {
			update_option( self::NEW_SETTINGS_OPTION, $legacy_exists );
			$messages[] = 'Settings: copied alm_settings to almgr_settings.';
		} else {
			$messages[] = 'Settings: legacy option found but not copied because its value is not an array.';
		}

		return $messages;
	}

	/**
	 * Refresh rewrite rules so new prefixed routes become active immediately.
	 *
	 * @return array<int, string>
	 */
	private static function refresh_rewrite_rules() {
		flush_rewrite_rules();
		return array( 'Rewrite rules: flushed for almgr-prefixed routes.' );
	}

	/**
	 * Ensure new roles exist and receive the new capabilities.
	 *
	 * @return array<int, string>
	 */
	private static function ensure_new_roles_and_capabilities() {
		$messages     = array();
		$role_manager = new ALMGR_Role_Manager();

		$member_exists_before   = (bool) get_role( ALMGR_MEMBER_ROLE );
		$operator_exists_before = (bool) get_role( ALMGR_OPERATOR_ROLE );

		$role_manager->activate();

		$messages[] = $member_exists_before
			? 'Roles: almgr_member already existed.'
			: 'Roles: created almgr_member.';
		$messages[] = $operator_exists_before
			? 'Roles: almgr_operator already existed.'
			: 'Roles: created almgr_operator.';
		$messages[] = 'Capabilities: ensured almgr_* capabilities for administrators and new roles.';

		return $messages;
	}

	/**
	 * Add the new roles to users who still have legacy roles.
	 *
	 * Legacy roles are intentionally kept to avoid destructive behavior.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_users_to_new_roles() {
		$messages = array();

		$user_updates = array(
			array(
				'legacy_role' => 'alm_member',
				'new_role'    => ALMGR_MEMBER_ROLE,
			),
			array(
				'legacy_role' => 'alm_operator',
				'new_role'    => ALMGR_OPERATOR_ROLE,
			),
		);

		foreach ( $user_updates as $migration ) {
			$legacy_role   = $migration['legacy_role'];
			$new_role      = $migration['new_role'];
			$query         = new WP_User_Query(
				array(
					'role'   => $legacy_role,
					'fields' => 'all',
				)
			);
			$processed     = 0;
			$already_had   = 0;
			$users_updated = 0;

			foreach ( $query->get_results() as $user ) {
				++$processed;
				if ( in_array( $new_role, (array) $user->roles, true ) ) {
					++$already_had;
					continue;
				}

				$user->add_role( $new_role );
				++$users_updated;
			}

			$messages[] = sprintf(
				'Users: legacy role %1$s -> new role %2$s. Processed: %3$d, updated: %4$d, already aligned: %5$d.',
				$legacy_role,
				$new_role,
				$processed,
				$users_updated,
				$already_had
			);
		}

		return $messages;
	}

	/**
	 * Rename legacy asset post type values in wp_posts.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_asset_post_type() {
		global $wpdb;

		$messages = array();
		$table    = $wpdb->posts;

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot migration update.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET post_type = %s WHERE post_type = %s",
				self::NEW_ASSET_POST_TYPE,
				self::LEGACY_ASSET_POST_TYPE
			)
		);
		/* phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

		if ( false === $updated ) {
			$messages[] = 'Posts: failed to migrate post_type from alm_asset to almgr_asset.';
			return $messages;
		}

		$messages[] = sprintf( 'Posts: post_type alm_asset -> almgr_asset. Updated: %d.', (int) $updated );
		return $messages;
	}

	/**
	 * Rename legacy asset taxonomy identifiers in wp_term_taxonomy.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_asset_taxonomies() {
		global $wpdb;

		$messages     = array();
		$table        = $wpdb->term_taxonomy;
		$taxonomy_map = array(
			'alm_structure' => 'almgr_structure',
			'alm_type'      => 'almgr_type',
			'alm_state'     => 'almgr_state',
			'alm_level'     => 'almgr_level',
		);

		foreach ( $taxonomy_map as $legacy => $new ) {
			/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot migration update.
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET taxonomy = %s WHERE taxonomy = %s",
					$new,
					$legacy
				)
			);
			/* phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

			if ( false === $updated ) {
				$messages[] = sprintf( 'Taxonomies: failed for %1$s -> %2$s.', $legacy, $new );
				continue;
			}

			$messages[] = sprintf( 'Taxonomies: %1$s -> %2$s. Updated: %3$d.', $legacy, $new, (int) $updated );
		}

		return $messages;
	}

	/**
	 * Rename legacy post meta keys used by ALM assets.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_asset_meta_keys() {
		global $wpdb;

		$messages = array();
		$table    = $wpdb->postmeta;
		$meta_map = array(
			'_alm_current_owner'        => '_almgr_current_owner',
			'_alm_removed_from_kit_ids' => '_almgr_removed_from_kit_ids',
		);

		foreach ( $meta_map as $legacy => $new ) {
			/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot migration update.
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET meta_key = %s WHERE meta_key = %s",
					$new,
					$legacy
				)
			);
			/* phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

			if ( false === $updated ) {
				$messages[] = sprintf( 'Post meta: failed for %1$s -> %2$s.', $legacy, $new );
				continue;
			}

			$messages[] = sprintf( 'Post meta: %1$s -> %2$s. Updated: %3$d.', $legacy, $new, (int) $updated );
		}

		return $messages;
	}

	/**
	 * Migrate loan tables from legacy names to new names.
	 *
	 * Behavior:
	 * - If only legacy table exists: rename it.
	 * - If both tables exist: merge missing rows from legacy into new table.
	 * - If only new table exists: no action.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_loan_tables() {
		$messages = array();

		$table_pairs = array(
			array(
				'legacy' => 'alm_loan_requests',
				'new'    => 'almgr_loan_requests',
			),
			array(
				'legacy' => 'alm_loan_requests_history',
				'new'    => 'almgr_loan_requests_history',
			),
		);

		foreach ( $table_pairs as $pair ) {
			$messages = array_merge(
				$messages,
				self::migrate_table_pair( $pair['legacy'], $pair['new'] )
			);
		}

		return $messages;
	}

	/**
	 * Migrate a single legacy/new table pair.
	 *
	 * @param string $legacy_suffix Legacy table suffix without WordPress prefix.
	 * @param string $new_suffix    New table suffix without WordPress prefix.
	 * @return array<int, string>
	 */
	private static function migrate_table_pair( $legacy_suffix, $new_suffix ) {
		global $wpdb;

		$messages     = array();
		$legacy_table = $wpdb->prefix . $legacy_suffix;
		$new_table    = $wpdb->prefix . $new_suffix;

		$legacy_exists = self::table_exists( $legacy_table );
		$new_exists    = self::table_exists( $new_table );

		if ( ! $legacy_exists && ! $new_exists ) {
			$messages[] = sprintf( 'Tables: neither %1$s nor %2$s exists.', $legacy_suffix, $new_suffix );
			return $messages;
		}

		if ( ! $legacy_exists && $new_exists ) {
			$messages[] = sprintf( 'Tables: %1$s already in use, legacy %2$s not found.', $new_suffix, $legacy_suffix );
			return $messages;
		}

		if ( $legacy_exists && ! $new_exists ) {
			if ( ! self::is_safe_table_identifier( $legacy_table ) || ! self::is_safe_table_identifier( $new_table ) ) {
				$messages[] = sprintf( 'Tables: skipped rename %1$s -> %2$s due to invalid table identifier.', $legacy_suffix, $new_suffix );
				return $messages;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are internal and validated.
			$renamed = $wpdb->query( "RENAME TABLE `$legacy_table` TO `$new_table`" );
			if ( false === $renamed ) {
				$messages[] = sprintf( 'Tables: failed to rename %1$s -> %2$s.', $legacy_suffix, $new_suffix );
			} else {
				$messages[] = sprintf( 'Tables: renamed %1$s -> %2$s.', $legacy_suffix, $new_suffix );
			}

			return $messages;
		}

		$merged_rows = self::merge_legacy_table_rows( $legacy_table, $new_table, $legacy_suffix );
		if ( false === $merged_rows ) {
			$messages[] = sprintf( 'Tables: failed to merge legacy %1$s into %2$s.', $legacy_suffix, $new_suffix );
		} else {
			$messages[] = sprintf(
				'Tables: merged legacy %1$s into %2$s. Inserted missing rows: %3$d.',
				$legacy_suffix,
				$new_suffix,
				(int) $merged_rows
			);
		}

		return $messages;
	}

	/**
	 * Merge legacy rows into the new table when both tables exist.
	 *
	 * @param string $legacy_table  Full legacy table name.
	 * @param string $new_table     Full new table name.
	 * @param string $legacy_suffix Legacy table suffix used for shape selection.
	 * @return int|false Number of inserted rows, or false on failure.
	 */
	private static function merge_legacy_table_rows( $legacy_table, $new_table, $legacy_suffix ) {
		global $wpdb;

		if ( ! self::is_safe_table_identifier( $legacy_table ) || ! self::is_safe_table_identifier( $new_table ) ) {
			return false;
		}

		if ( 'alm_loan_requests' === $legacy_suffix ) {
			$sql = "INSERT INTO `$new_table` (id, asset_id, requester_id, owner_id, request_date, request_message, status, response_date, response_message)
				SELECT l.id, l.asset_id, l.requester_id, l.owner_id, l.request_date, l.request_message, l.status, l.response_date, l.response_message
				FROM `$legacy_table` l
				LEFT JOIN `$new_table` n ON n.id = l.id
				WHERE n.id IS NULL";
		} elseif ( 'alm_loan_requests_history' === $legacy_suffix ) {
			$sql = "INSERT INTO `$new_table` (id, loan_request_id, asset_id, requester_id, owner_id, status, message, changed_at, changed_by)
				SELECT l.id, l.loan_request_id, l.asset_id, l.requester_id, l.owner_id, l.status, l.message, l.changed_at, l.changed_by
				FROM `$legacy_table` l
				LEFT JOIN `$new_table` n ON n.id = l.id
				WHERE n.id IS NULL";
		} else {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Table names are internal and validated.
		return $wpdb->query( $sql );
	}

	/**
	 * Migrate legacy identifiers embedded in post_content.
	 *
	 * @return array<int, string>
	 */
	private static function migrate_post_content_identifiers() {
		$messages     = array();
		$replacements = array(
			'[alm_asset_list'  => '[almgr_asset_list',
			'[alm_asset_view'  => '[almgr_asset_view',
			'/wp-json/alm/v1/' => '/wp-json/almgr/v1/',
			'/alm/v1/'         => '/almgr/v1/',
		);

		foreach ( $replacements as $legacy => $new ) {
			$updated = self::replace_in_post_content( $legacy, $new );
			if ( false === $updated ) {
				$messages[] = sprintf( 'Content: failed replacement %1$s -> %2$s.', $legacy, $new );
				continue;
			}

			$messages[] = sprintf( 'Content: replaced %1$s -> %2$s. Updated posts: %3$d.', $legacy, $new, (int) $updated );
		}

		return $messages;
	}

	/**
	 * Replace occurrences in wp_posts.post_content using SQL REPLACE.
	 *
	 * @param string $legacy Legacy token.
	 * @param string $replacement New token.
	 * @return int|false Number of updated rows, or false on failure.
	 */
	private static function replace_in_post_content( $legacy, $replacement ) {
		global $wpdb;

		$table       = $wpdb->posts;
		$like_legacy = '%' . $wpdb->esc_like( $legacy ) . '%';

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot migration update.
		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET post_content = REPLACE(post_content, %s, %s)
				WHERE post_content LIKE %s",
				$legacy,
				$replacement,
				$like_legacy
			)
		);
		/* phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name Full table name.
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-shot migration existence check.
		return ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );
	}

	/**
	 * Validate SQL table identifier used for schema operations.
	 *
	 * @param string $table Table name to validate.
	 * @return bool
	 */
	private static function is_safe_table_identifier( $table ) {
		return 1 === preg_match( '/^[A-Za-z0-9_$]+$/', $table );
	}

	/**
	 * Render a minimal admin report and stop the request.
	 *
	 * @param array<int, string> $report Report lines.
	 * @return void
	 */
	private static function render_report( array $report ) {
		$back_url = admin_url();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'ALMGR Manual Prefix Migration', 'asset-lending-manager' ) . '</h1>';
		echo '<p>' . esc_html__( 'The migration ran as a manual one-shot task. Review the report below before continuing.', 'asset-lending-manager' ) . '</p>';
		echo '<ol>';
		foreach ( $report as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ol>';
		echo '<p><a class="button button-primary" href="' . esc_url( $back_url ) . '">' . esc_html__( 'Back to admin', 'asset-lending-manager' ) . '</a></p>';
		echo '</div>';
		exit;
	}
}
