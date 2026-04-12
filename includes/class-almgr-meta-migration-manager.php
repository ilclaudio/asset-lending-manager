<?php
/**
 * ALMGR Meta Migration Manager
 *
 * Provides an admin-post action (almgr_migrate_acf_field_names) that renames
 * legacy ACF post-meta keys (unprefixed) to the new prefixed keys (almgr_*)
 * for all almgr_asset posts.
 *
 * The operation is idempotent: running it multiple times produces the same
 * result without creating duplicates or losing data.
 *
 * Access is restricted to authenticated users with the manage_options capability.
 * The action is triggered by a form in the Tools > Utilities admin page.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALMGR_Meta_Migration_Manager
 */
class ALMGR_Meta_Migration_Manager {

	/**
	 * Nonce action name.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'almgr_migrate_acf_field_names_action';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_FIELD = 'almgr_migrate_acf_field_names_nonce';

	/**
	 * Map of old meta keys to new meta keys.
	 * Each entry also drives the ACF shadow key migration (_old -> _new).
	 *
	 * @var array<string,string>
	 */
	const FIELD_MAP = array(
		'manufacturer'         => 'almgr_manufacturer',
		'model'                => 'almgr_model',
		'data_acquisto'        => 'almgr_data_acquisto',
		'cost'                 => 'almgr_cost',
		'dimensions'           => 'almgr_dimensions',
		'weight'               => 'almgr_weight',
		'location'             => 'almgr_location',
		'components'           => 'almgr_components',
		'user_manual'          => 'almgr_user_manual',
		'technical_data_sheet' => 'almgr_technical_data_sheet',
		'serial_number'        => 'almgr_serial_number',
		'external_code'        => 'almgr_external_code',
		'notes'                => 'almgr_notes',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_post_almgr_migrate_acf_field_names', array( $this, 'handle_migration' ) );
	}

	/**
	 * Handle the admin-post action: verify nonce and capability, run migration,
	 * output a JSON report, and exit.
	 *
	 * @return void
	 */
	public function handle_migration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to run this migration.', 'asset-lending-manager' ),
				403
			);
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$report = $this->run_migration();

		header( 'Content-Type: application/json; charset=utf-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded output is safe.
		echo wp_json_encode( $report, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Execute the idempotent meta key migration for all almgr_asset posts.
	 *
	 * @return array{
	 *   assets_processed: int,
	 *   fields_migrated: int,
	 *   fields_already_migrated: int,
	 *   fields_skipped_empty: int,
	 *   errors: array<int, array{asset_id: int, old_key: string, error: string}>
	 * }
	 */
	public function run_migration(): array {
		$report = array(
			'assets_processed'        => 0,
			'fields_migrated'         => 0,
			'fields_already_migrated' => 0,
			'fields_skipped_empty'    => 0,
			'errors'                  => array(),
		);

		$asset_ids = $this->get_all_asset_ids();

		foreach ( $asset_ids as $asset_id ) {
			++$report['assets_processed'];
			foreach ( self::FIELD_MAP as $old_key => $new_key ) {
				$result = $this->migrate_field( (int) $asset_id, $old_key, $new_key );
				switch ( $result ) {
					case 'migrated':
						++$report['fields_migrated'];
						break;
					case 'already_migrated':
						++$report['fields_already_migrated'];
						break;
					case 'skipped_empty':
						++$report['fields_skipped_empty'];
						break;
					default:
						// $result is an error message string.
						$report['errors'][] = array(
							'asset_id' => (int) $asset_id,
							'old_key'  => $old_key,
							'error'    => (string) $result,
						);
						break;
				}
			}
		}

		return $report;
	}

	/**
	 * Migrate a single meta field for a single post.
	 *
	 * Returns a status string:
	 * - 'migrated'          Old key found and moved to new key.
	 * - 'already_migrated'  New key already exists; nothing to do.
	 * - 'skipped_empty'     Neither key exists; nothing to do.
	 * - <error message>     Something went wrong.
	 *
	 * ACF stores field values with a companion shadow key:
	 *   _<field_name> => <acf_field_key>  (e.g. _manufacturer => field_694a...)
	 * The shadow key is also renamed so that ACF still links the value to its
	 * field definition after the migration.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $old_key Old meta key.
	 * @param string $new_key New meta key.
	 * @return string Status code or error message.
	 */
	private function migrate_field( int $post_id, string $old_key, string $new_key ): string {
		$old_shadow = '_' . $old_key;
		$new_shadow = '_' . $new_key;

		// Check whether the new key already exists (any value, including empty string).
		$new_exists = metadata_exists( 'post', $post_id, $new_key );
		if ( $new_exists ) {
			return 'already_migrated';
		}

		// Check whether the old key exists.
		$old_exists = metadata_exists( 'post', $post_id, $old_key );
		if ( ! $old_exists ) {
			return 'skipped_empty';
		}

		// Read the old value.
		$value      = get_post_meta( $post_id, $old_key, true );
		$shadow_val = get_post_meta( $post_id, $old_shadow, true );

		// Write to new key.
		$updated = update_post_meta( $post_id, $new_key, $value );
		if ( false === $updated ) {
			return sprintf(
				/* translators: %1$s: old meta key, %2$s: new meta key. */
				__( 'update_post_meta failed moving "%1$s" to "%2$s".', 'asset-lending-manager' ),
				$old_key,
				$new_key
			);
		}

		// Migrate the ACF shadow key if present.
		if ( '' !== $shadow_val ) {
			update_post_meta( $post_id, $new_shadow, $shadow_val );
			delete_post_meta( $post_id, $old_shadow );
		}

		// Remove old key.
		delete_post_meta( $post_id, $old_key );

		return 'migrated';
	}

	/**
	 * Return all post IDs for the almgr_asset CPT (any status).
	 *
	 * @return int[]
	 */
	private function get_all_asset_ids(): array {
		$query = new WP_Query(
			array(
				'post_type'      => ALMGR_ASSET_CPT_SLUG,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return array_map( 'intval', (array) $query->posts );
	}
}
