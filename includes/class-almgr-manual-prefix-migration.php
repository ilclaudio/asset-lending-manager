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
