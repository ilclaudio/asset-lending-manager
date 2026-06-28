<?php
/**
 * Shared integration helpers for loan workflow tests.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exception used to intercept wp_die() during AJAX tests.
 */
class ALMGR_Test_WP_Die_Exception extends Exception {
}

/**
 * Base test case for loan workflow integration tests.
 */
abstract class ALMGR_Loan_Integration_Test_Case extends WP_UnitTestCase {

	/**
	 * Shared settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	protected static $settings_manager;

	/**
	 * Shared asset manager instance.
	 *
	 * @var ALMGR_Asset_Manager
	 */
	protected static $asset_manager;

	/**
	 * Shared loan manager instance.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	protected static $loan_manager;

	/**
	 * Shared role manager instance.
	 *
	 * @var ALMGR_Role_Manager
	 */
	protected static $role_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->boot_minimal_plugin_state();
		$this->reset_runtime_state();

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_wp_die_handler' ), 1, 1 );

		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'ajax' );
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_wp_die_handler' ), 1 );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'front' );
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Boot the minimum runtime pieces required by integration tests.
	 *
	 * @return void
	 */
	protected function boot_minimal_plugin_state(): void {
		if ( self::$settings_manager instanceof ALMGR_Settings_Manager ) {
			return;
		}

		self::$settings_manager = new ALMGR_Settings_Manager();
		self::$role_manager     = new ALMGR_Role_Manager();
		self::$asset_manager    = new ALMGR_Asset_Manager();
		self::$loan_manager     = new ALMGR_Loan_Manager( self::$settings_manager );

		self::$role_manager->activate();
		self::$asset_manager->register_post_type();
		self::$asset_manager->register_taxonomies();
		ALMGR_Installer::create_default_terms();
		self::$loan_manager->activate();
	}

	/**
	 * Reset plugin state between tests.
	 *
	 * @return void
	 */
	protected function reset_runtime_state(): void {
		self::$settings_manager->reset();

		$this->truncate_table( $this->get_requests_table_name() );
		$this->truncate_table( $this->get_history_table_name() );

		wp_set_current_user( 0 );
	}

	/**
	 * Return the loan manager under test.
	 *
	 * @return ALMGR_Loan_Manager
	 */
	protected function get_loan_manager(): ALMGR_Loan_Manager {
		return self::$loan_manager;
	}

	/**
	 * Create a user for a specific plugin role.
	 *
	 * @param string $role User role.
	 * @return int
	 */
	protected function create_user_with_role( string $role ): int {
		return self::factory()->user->create(
			array(
				'role'       => $role,
				'user_login' => $role . '_' . wp_generate_password( 8, false ),
			)
		);
	}

	/**
	 * Create a published component asset in a specific state.
	 *
	 * @param array $args Asset overrides.
	 * @return int
	 */
	protected function create_asset( array $args = array() ): int {
		$defaults = array(
			'post_type'   => ALMGR_ASSET_CPT_SLUG,
			'post_status' => 'publish',
			'post_title'  => 'Test Asset ' . wp_generate_password( 6, false ),
			'state'       => 'available',
			'structure'   => ALMGR_ASSET_COMPONENT_SLUG,
			'type'        => 'generic',
			'level'       => 'basic',
			'owner_id'    => 0,
		);

		$args     = wp_parse_args( $args, $defaults );
		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => $args['post_type'],
				'post_status' => $args['post_status'],
				'post_title'  => $args['post_title'],
			)
		);

		wp_set_object_terms( $asset_id, $args['structure'], ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['type'], ALMGR_ASSET_TYPE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['state'], ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['level'], ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, false );
		update_post_meta( $asset_id, '_almgr_current_owner', (int) $args['owner_id'] );

		return $asset_id;
	}

	/**
	 * Insert a pending request directly in the plugin table.
	 *
	 * @param int    $asset_id     Asset ID.
	 * @param int    $requester_id Requester user ID.
	 * @param int    $owner_id     Current owner user ID.
	 * @param string $message      Request message.
	 * @return int
	 */
	protected function insert_pending_request( int $asset_id, int $requester_id, int $owner_id, string $message ): int {
		global $wpdb;

		$wpdb->insert(
			$this->get_requests_table_name(),
			array(
				'asset_id'         => $asset_id,
				'requester_id'     => $requester_id,
				'owner_id'         => $owner_id,
				'request_date'     => current_time( 'mysql' ),
				'request_message'  => $message,
				'status'           => 'pending',
				'response_date'    => null,
				'response_message' => null,
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Call an AJAX handler and return its decoded JSON payload.
	 *
	 * @param string $method AJAX handler method.
	 * @param array  $post   POST payload.
	 * @return array
	 */
	protected function call_ajax_handler( string $method, array $post ): array {
		$_POST    = $post;
		$_REQUEST = $post;

		ob_start();

		try {
			$this->get_loan_manager()->{$method}();
			$output = ob_get_clean();
		} catch ( ALMGR_Test_WP_Die_Exception $exception ) {
			$output = ob_get_clean();
		}

		$decoded = json_decode( $output, true );

		$this->assertIsArray( $decoded, 'AJAX response must be valid JSON.' );
		$this->assertArrayHasKey( 'success', $decoded );
		$this->assertArrayHasKey( 'data', $decoded );

		return $decoded;
	}

	/**
	 * Filter wp_die handler for AJAX tests.
	 *
	 * @return callable
	 */
	public function filter_wp_die_handler(): callable {
		return array( $this, 'handle_wp_die' );
	}

	/**
	 * Throw an exception instead of terminating the test process.
	 *
	 * @param string $message Die message.
	 * @return void
	 * @throws ALMGR_Test_WP_Die_Exception Always thrown to stop execution.
	 */
	public function handle_wp_die( $message = '', $title = '', $args = array() ): void {
		throw new ALMGR_Test_WP_Die_Exception( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * Fetch a request row by ID.
	 *
	 * @param int $request_id Request ID.
	 * @return object|null
	 */
	protected function get_request_row( int $request_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$this->get_requests_table_name(),
				$request_id
			)
		);
	}

	/**
	 * Fetch history rows for an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return array
	 */
	protected function get_history_rows_for_asset( int $asset_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE asset_id = %d ORDER BY id ASC',
				$this->get_history_table_name(),
				$asset_id
			)
		);
	}

	/**
	 * Count pending requests for an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return int
	 */
	protected function count_pending_requests_for_asset( int $asset_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE asset_id = %d AND status = 'pending'",
				$this->get_requests_table_name(),
				$asset_id
			)
		);
	}

	/**
	 * Return the current asset state slug.
	 *
	 * @param int $asset_id Asset ID.
	 * @return string
	 */
	protected function get_asset_state_slug( int $asset_id ): string {
		$terms = wp_get_post_terms(
			$asset_id,
			ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			array(
				'fields' => 'slugs',
			)
		);

		return empty( $terms ) ? '' : (string) $terms[0];
	}

	/**
	 * Return the loan requests table name.
	 *
	 * @return string
	 */
	protected function get_requests_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'almgr_loan_requests';
	}

	/**
	 * Return the loan history table name.
	 *
	 * @return string
	 */
	protected function get_history_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'almgr_loan_requests_history';
	}

	/**
	 * Truncate a plugin table.
	 *
	 * @param string $table_name Table name.
	 * @return void
	 */
	private function truncate_table( string $table_name ): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table_name ) );
	}
}
