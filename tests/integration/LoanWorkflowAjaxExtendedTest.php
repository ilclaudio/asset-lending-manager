<?php
/**
 * Integration tests for additional loan workflow AJAX handlers.
 *
 * Covers the three AJAX endpoints not exercised by LoanWorkflowAjaxTest:
 *   - ajax_direct_assign_asset
 *   - ajax_change_asset_state  (→ maintenance and forced return → available)
 *
 * ajax_restore_asset_state is not covered here; see KitStateChangePlanTest for
 * the underlying plan-builder logic.
 *
 * Note: there is no ajax_cancel_loan_request handler in the plugin. Member-initiated
 * cancellation of a pending request is a planned feature that has not yet been
 * implemented; the corresponding test cannot be written until the handler exists.
 *
 * Extends WP_UnitTestCase directly (not ALMGR_Loan_Integration_Test_Case) to avoid
 * the static boot-flag collision that would cause LoanWorkflowAjaxTest — which runs
 * after this class alphabetically — to skip re-running create_default_terms() and fail.
 *
 * @package AssetLendingManager
 */

// Load the WP-die interceptor class without inheriting from the base test case.
require_once __DIR__ . '/support/class-almgr-loan-integration-test-case.php';

/**
 * Test class for additional loan workflow AJAX handlers.
 */
class LoanWorkflowAjaxExtendedTest extends WP_UnitTestCase {

	/**
	 * Loan manager instance used to invoke AJAX handlers.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	private ALMGR_Loan_Manager $loan_manager;

	/**
	 * Guard: plugin state is booted only once per process.
	 *
	 * @var bool
	 */
	private static bool $plugin_booted = false;

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	/**
	 * Boot CPT, taxonomies, roles, default terms, and plugin DB tables once,
	 * outside any DB transaction so the committed data persists across all tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( self::$plugin_booted ) {
			return;
		}
		$settings      = new ALMGR_Settings_Manager();
		( new ALMGR_Role_Manager() )->activate();
		$asset_manager = new ALMGR_Asset_Manager();
		$asset_manager->register_post_type();
		$asset_manager->register_taxonomies();
		ALMGR_Installer::create_default_terms();
		( new ALMGR_Loan_Manager( $settings ) )->activate();
		self::$plugin_booted = true;
	}

	/**
	 * Create a fresh loan manager, reset plugin tables, and set up AJAX environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loan_manager = new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() );

		// Truncate plugin tables so each test starts clean.
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $wpdb->prefix . 'almgr_loan_requests' ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $wpdb->prefix . 'almgr_loan_requests_history' ) );
		wp_set_current_user( 0 );

		// Simulate an AJAX request context.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_wp_die_handler' ), 1, 1 );

		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'ajax' );
		}
	}

	/**
	 * Restore environment and clear superglobals after each test.
	 *
	 * @return void
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

	// -------------------------------------------------------------------------
	// AJAX helpers (mirror ALMGR_Loan_Integration_Test_Case)
	// -------------------------------------------------------------------------

	/**
	 * Return the wp_die handler that intercepts wp_die() during AJAX tests.
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
	 * @throws ALMGR_Test_WP_Die_Exception Always thrown.
	 */
	public function handle_wp_die( $message = '', $title = '', $args = array() ): void {
		throw new ALMGR_Test_WP_Die_Exception( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * Call an AJAX handler method and return its decoded JSON response.
	 *
	 * @param string $method AJAX handler method name on ALMGR_Loan_Manager.
	 * @param array  $post   POST payload.
	 * @return array Decoded response with 'success' and 'data' keys.
	 */
	private function call_ajax_handler( string $method, array $post ): array {
		$_POST    = $post;
		$_REQUEST = $post;

		ob_start();

		try {
			$this->loan_manager->{$method}();
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

	// -------------------------------------------------------------------------
	// Fixture helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a user with the given plugin role.
	 *
	 * @param string $role WP role slug.
	 * @return int User ID.
	 */
	private function create_user_with_role( string $role ): int {
		return self::factory()->user->create(
			array(
				'role'       => $role,
				'user_login' => $role . '_' . wp_generate_password( 8, false ),
			)
		);
	}

	/**
	 * Create a published ALM asset post and assign taxonomy terms.
	 *
	 * @param array $args Overrides: post_title, structure, state, type, level, owner_id.
	 * @return int Post ID.
	 */
	private function create_asset( array $args = array() ): int {
		$defaults = array(
			'post_title' => 'Test Asset ' . wp_generate_password( 6, false ),
			'structure'  => ALMGR_ASSET_COMPONENT_SLUG,
			'state'      => 'available',
			'type'       => 'generic',
			'level'      => 'basic',
			'owner_id'   => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
				'post_title'  => $args['post_title'],
			)
		);

		wp_set_object_terms( $asset_id, $args['structure'], ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['type'],      ALMGR_ASSET_TYPE_TAXONOMY_SLUG,      false );
		wp_set_object_terms( $asset_id, $args['state'],     ALMGR_ASSET_STATE_TAXONOMY_SLUG,     false );
		wp_set_object_terms( $asset_id, $args['level'],     ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,     false );
		update_post_meta( $asset_id, '_almgr_current_owner', (int) $args['owner_id'] );

		return $asset_id;
	}

	/**
	 * Insert a pending loan request directly into the plugin table.
	 *
	 * @param int    $asset_id     Asset ID.
	 * @param int    $requester_id Requester user ID.
	 * @param int    $owner_id     Current owner user ID.
	 * @param string $message      Request message.
	 * @return int Inserted row ID.
	 */
	private function insert_pending_request( int $asset_id, int $requester_id, int $owner_id, string $message ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'almgr_loan_requests',
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
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Return the current state slug for an asset from the taxonomy.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return string State slug or empty string.
	 */
	private function get_asset_state_slug( int $asset_id ): string {
		$terms = wp_get_post_terms( $asset_id, ALMGR_ASSET_STATE_TAXONOMY_SLUG, array( 'fields' => 'slugs' ) );
		return empty( $terms ) ? '' : (string) $terms[0];
	}

	/**
	 * Count pending requests for an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return int
	 */
	private function count_pending_requests_for_asset( int $asset_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE asset_id = %d AND status = 'pending'",
				$wpdb->prefix . 'almgr_loan_requests',
				$asset_id
			)
		);
	}

	/**
	 * Return all history rows for an asset ordered by id ASC.
	 *
	 * @param int $asset_id Asset ID.
	 * @return array
	 */
	private function get_history_rows_for_asset( int $asset_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE asset_id = %d ORDER BY id ASC',
				$wpdb->prefix . 'almgr_loan_requests_history',
				$asset_id
			)
		);
	}

	// -------------------------------------------------------------------------
	// Tests
	// -------------------------------------------------------------------------

	/**
	 * Direct assignment transfers ownership, sets state to on-loan, and cancels
	 * all pending requests for the asset.
	 *
	 * @return void
	 */
	public function test_direct_assign_asset_transfers_ownership_and_cancels_pending_requests(): void {
		$operator_id  = $this->create_user_with_role( 'administrator' );
		$assignee_id  = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$requester_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id     = $this->create_asset();

		$this->insert_pending_request( $asset_id, $requester_id, 0, 'Please assign me.' );

		wp_set_current_user( $operator_id );

		$response = $this->call_ajax_handler(
			'ajax_direct_assign_asset',
			array(
				'nonce'       => wp_create_nonce( 'almgr_direct_assign_nonce' ),
				'asset_id'    => $asset_id,
				'assignee_id' => $assignee_id,
				'reason'      => 'Assigned for the telescope session.',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $asset_id ), 'Asset state should be on-loan after direct assignment.' );
		$this->assertSame( $assignee_id, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ), 'Asset owner should be the assignee.' );
		$this->assertSame( 0, $this->count_pending_requests_for_asset( $asset_id ), 'All pending requests should be canceled after direct assignment.' );

		$history_statuses = wp_list_pluck( $this->get_history_rows_for_asset( $asset_id ), 'status' );
		$this->assertContains( 'direct_assign', $history_statuses, 'History should contain a direct_assign entry.' );
		$this->assertContains( 'canceled', $history_statuses, 'The pending request should be recorded as canceled in history.' );
	}

	/**
	 * Changing a kit's state to maintenance propagates to eligible components.
	 * Components already in state=available with no owner are included; the kit
	 * and all included components reach state=maintenance after the handler runs.
	 *
	 * @return void
	 */
	public function test_change_asset_state_applies_state_to_kit_and_available_components(): void {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$kit_id      = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$comp_id     = $this->create_asset(); // state=available, owner=0 → will be included

		update_post_meta( $kit_id, 'almgr_components', array( $comp_id ) );

		wp_set_current_user( $operator_id );

		$response = $this->call_ajax_handler(
			'ajax_change_asset_state',
			array(
				'nonce'        => wp_create_nonce( 'almgr_change_state_nonce' ),
				'asset_id'     => $kit_id,
				'target_state' => 'maintenance',
				'location'     => 'Storage room A',
				'notes'        => 'Scheduled maintenance.',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $kit_id ), 'Kit should reach state=maintenance.' );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $comp_id ), 'Included component should reach state=maintenance.' );
	}

	/**
	 * Force-returning an on-loan asset (target=available via ajax_change_asset_state)
	 * clears the owner, sets state=available, and writes a to_available history entry.
	 *
	 * @return void
	 */
	public function test_force_return_loan_clears_owner_and_restores_asset_to_available(): void {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$borrower_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id    = $this->create_asset( array( 'state' => 'on-loan', 'owner_id' => $borrower_id ) );

		wp_set_current_user( $operator_id );

		$response = $this->call_ajax_handler(
			'ajax_change_asset_state',
			array(
				'nonce'        => wp_create_nonce( 'almgr_change_state_nonce' ),
				'asset_id'     => $asset_id,
				'target_state' => 'available',
				'location'     => 'Clubhouse shelf',
				'notes'        => 'Force returned by operator.',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ), 'Asset state should be available after force return.' );
		$this->assertSame( 0, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ), 'Asset owner should be cleared after force return.' );

		$history_statuses = wp_list_pluck( $this->get_history_rows_for_asset( $asset_id ), 'status' );
		$this->assertContains( 'to_available', $history_statuses, 'History should contain a to_available entry.' );
	}
}
