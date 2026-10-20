<?php
/**
 * Integration tests for the row locks taken by asset state transitions.
 *
 * Two parts:
 * - the transition rules are re-checked under the lock, so an operation that
 *   validated its input against an outdated state fails cleanly instead of
 *   writing a second, contradictory history row (the private cores are called
 *   directly to simulate that outdated pre-check);
 * - the locks are really held: a second database connection that holds a row
 *   lock makes the operation fail with a clear message, and the operation goes
 *   through once the lock is released.
 *
 * @package AssetLendingManager
 */

require_once __DIR__ . '/support/class-almgr-loan-integration-test-case.php';

/**
 * Test class for the asset row locks.
 */
class AssetLockTest extends ALMGR_Loan_Integration_Test_Case {

	/**
	 * Second database connection used to hold a competing row lock.
	 *
	 * @var mysqli|null
	 */
	private $other_connection = null;

	/**
	 * Close the second connection and restore the lock wait timeout.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;

		if ( $this->other_connection instanceof mysqli ) {
			$this->other_connection->query( 'ROLLBACK' );
			$this->other_connection->close();
			$this->other_connection = null;
		}

		$wpdb->query( 'SET SESSION innodb_lock_wait_timeout = 50' );

		parent::tearDown();
	}

	/**
	 * Call a private method of the loan manager.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	private function call_private( string $method, array $args ) {
		$reflection = new ReflectionMethod( ALMGR_Loan_Manager::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $this->get_loan_manager(), $args );
	}

	/**
	 * Open a second connection to the test database, with a transaction holding a row lock on a post.
	 *
	 * @param int $post_id Post ID whose row is locked.
	 * @return void
	 */
	private function hold_lock_from_another_connection( int $post_id ): void {
		global $wpdb;

		// The fixtures live in the test's open transaction: commit them so the
		// second connection can see (and lock) the row.
		$wpdb->query( 'COMMIT' );

		$host   = DB_HOST;
		$port   = null;
		$socket = null;
		if ( preg_match( '/^(.+):(\d+)$/', DB_HOST, $matches ) ) {
			$host = $matches[1];
			$port = (int) $matches[2];
		} elseif ( false !== strpos( DB_HOST, ':/' ) ) {
			list( $host, $socket ) = explode( ':', DB_HOST, 2 );
		}

		mysqli_report( MYSQLI_REPORT_OFF );
		$connection = new mysqli( $host, DB_USER, DB_PASSWORD, DB_NAME, $port, $socket );
		$this->assertSame( 0, $connection->connect_errno, 'The second database connection must open.' );

		$connection->query( 'START TRANSACTION' );
		$result = $connection->query( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE ID = ' . (int) $post_id . ' FOR UPDATE' );
		$this->assertNotFalse( $result, 'The competing lock must be taken.' );
		$this->assertSame( 1, $result->num_rows );

		$this->other_connection = $connection;

		// Fail fast instead of waiting for the default 50 seconds.
		$wpdb->query( 'SET SESSION innodb_lock_wait_timeout = 1' );
	}

	/**
	 * Release the competing lock.
	 *
	 * @return void
	 */
	private function release_lock_of_another_connection(): void {
		$this->other_connection->query( 'ROLLBACK' );
	}

	/**
	 * A force-return validated against an outdated state fails under the lock and writes nothing.
	 *
	 * @return void
	 */
	public function test_state_change_core_rejects_a_stale_force_return() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset();

		$result = $this->call_private( 'change_asset_state', array( $asset_id, 'available', 'Late.', $operator_id, 'Shelf 1' ) );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Asset can only be force-returned when it is on loan.', $result['message'] );
		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * A transition that is not allowed from the current state fails under the lock.
	 *
	 * @return void
	 */
	public function test_state_change_core_rejects_a_transition_from_a_retired_asset() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset( array( 'state' => 'retired' ) );

		$result = $this->call_private( 'change_asset_state', array( $asset_id, 'maintenance', 'Late.', $operator_id, 'Shelf 1' ) );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Asset state cannot be changed from its current state.', $result['message'] );
		$this->assertSame( 'retired', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * Restoring an asset that is no longer in maintenance or retired fails under the lock.
	 *
	 * @return void
	 */
	public function test_restore_core_rejects_an_asset_that_is_not_restorable() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset();

		$result = $this->call_private( 'restore_asset_state', array( $asset_id, 'Late.', $operator_id, 'Shelf 1' ) );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Asset is not in a restorable state.', $result['message'] );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * Returning an asset that is no longer on loan fails under the lock.
	 *
	 * @return void
	 */
	public function test_return_core_rejects_an_asset_that_is_not_on_loan() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset();

		$result = $this->get_loan_manager()->return_asset( $asset_id, 'Late.', $operator_id, 'Shelf 1' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Asset can only be returned when it is on loan.', $result['message'] );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * A member who stopped being the owner cannot return the asset, even when the earlier check passed.
	 *
	 * @return void
	 */
	public function test_return_core_rechecks_the_owner_under_the_lock() {
		( new ALMGR_Settings_Manager() )->set( 'workflow.member_return_enabled', true );
		$former_owner = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$new_owner    = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id     = $this->create_asset(
			array(
				'state'    => 'on-loan',
				'owner_id' => $new_owner,
			)
		);

		$result = $this->get_loan_manager()->return_asset( $asset_id, 'Not mine anymore.', $former_owner, 'Shelf 1' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'You do not have permission to return this asset.', $result['message'] );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( $new_owner, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * Two force-returns of the same asset produce one effect: the second one is refused.
	 *
	 * @return void
	 */
	public function test_a_repeated_force_return_writes_a_single_history_row() {
		$loan_owner  = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset(
			array(
				'state'    => 'on-loan',
				'owner_id' => $loan_owner,
			)
		);

		$first = $this->get_loan_manager()->change_state( $asset_id, 'available', 'First.', 'Shelf 1', $operator_id );
		$this->assertIsArray( $first );

		// The second caller validated its input before the first one finished.
		$second = $this->call_private( 'change_asset_state', array( $asset_id, 'available', 'Second.', $operator_id, 'Shelf 1' ) );

		$this->assertFalse( $second['success'] );
		$this->assertCount( 1, $this->get_history_rows_for_asset( $asset_id ), 'Only the first force-return may be recorded.' );
		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ) );
	}

	/**
	 * A row lock held by another connection stops a state change, which succeeds once the lock is released.
	 *
	 * @return void
	 */
	public function test_state_change_fails_cleanly_while_another_connection_holds_the_asset_lock() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset();
		$manager     = $this->get_loan_manager();

		$this->hold_lock_from_another_connection( $asset_id );

		$blocked = $manager->change_state( $asset_id, 'maintenance', 'Cleaning.', 'Workshop', $operator_id );

		$this->assertWPError( $blocked );
		$this->assertSame( 'almgr_change_state_failed', $blocked->get_error_code() );
		$this->assertStringContainsString( 'another operation is in progress', $blocked->get_error_message() );
		clean_post_cache( $asset_id );
		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ), 'A blocked operation must change nothing.' );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );

		$this->release_lock_of_another_connection();

		$result = $manager->change_state( $asset_id, 'maintenance', 'Cleaning.', 'Workshop', $operator_id );
		$this->assertIsArray( $result, 'The operation must go through once the lock is released.' );
		clean_post_cache( $asset_id );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $asset_id ) );
	}

	/**
	 * Direct assignment is stopped by the same lock.
	 *
	 * @return void
	 */
	public function test_direct_assignment_fails_cleanly_while_another_connection_holds_the_asset_lock() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$assignee_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id    = $this->create_asset();

		$this->hold_lock_from_another_connection( $asset_id );

		$blocked = $this->get_loan_manager()->assign_asset( $asset_id, $assignee_id, 'Workshop.', $operator_id );

		$this->assertWPError( $blocked );
		$this->assertSame( 'almgr_assign_failed', $blocked->get_error_code() );
		$this->assertStringContainsString( 'another operation is in progress', $blocked->get_error_message() );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
	}

	/**
	 * An operation on a kit also locks its components.
	 *
	 * @return void
	 */
	public function test_kit_operation_is_blocked_by_a_lock_on_one_of_its_components() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$component_1 = $this->create_asset();
		$component_2 = $this->create_asset();
		$kit_id      = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		update_field( 'almgr_components', array( $component_1, $component_2 ), $kit_id );

		$this->hold_lock_from_another_connection( $component_2 );

		$blocked = $this->get_loan_manager()->change_state( $kit_id, 'maintenance', 'Cleaning.', 'Workshop', $operator_id );

		$this->assertWPError( $blocked );
		$this->assertStringContainsString( 'another operation is in progress', $blocked->get_error_message() );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $kit_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $component_1 ) );

		$this->release_lock_of_another_connection();

		$this->assertIsArray( $this->get_loan_manager()->change_state( $kit_id, 'maintenance', 'Cleaning.', 'Workshop', $operator_id ) );
	}

	/**
	 * Approving a request also takes the asset lock, so it cannot run next to a direct assignment.
	 *
	 * @return void
	 */
	public function test_approval_is_blocked_by_an_asset_lock() {
		$operator_id  = $this->create_user_with_role( 'administrator' );
		$requester_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id     = $this->create_asset();
		$request_id   = $this->insert_pending_request( $asset_id, $requester_id, 0, 'Please.' );

		$this->hold_lock_from_another_connection( $asset_id );

		$blocked = $this->get_loan_manager()->approve_request( $request_id, $asset_id, $operator_id );

		$this->assertWPError( $blocked );
		$this->assertSame( 'almgr_approve_failed', $blocked->get_error_code() );
		$this->assertStringContainsString( 'another operation is in progress', $blocked->get_error_message() );
		$this->assertNotNull( $this->get_request_row( $request_id ), 'The request must stay pending.' );

		$this->release_lock_of_another_connection();

		$this->assertIsArray( $this->get_loan_manager()->approve_request( $request_id, $asset_id, $operator_id ) );
	}
}
