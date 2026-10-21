<?php
/**
 * Integration tests for the shared loan workflow domain methods.
 *
 * These tests call the public domain methods directly, without the AJAX
 * adapter, so the rules every channel relies on are verified on their own.
 *
 * @package AssetLendingManager
 */

require_once __DIR__ . '/support/class-almgr-loan-integration-test-case.php';

/**
 * Test class for the shared loan workflow domain methods.
 */
class LoanWorkflowDomainTest extends ALMGR_Loan_Integration_Test_Case {

	/**
	 * Calls recorded by the hook listener: each item is the list of hook arguments.
	 *
	 * @var array
	 */
	private $hook_calls = array();

	/**
	 * Record every call of an action so tests can assert how often and with what it fires.
	 *
	 * @param string $hook Action name.
	 * @param int    $args Number of accepted arguments.
	 * @return void
	 */
	private function listen_to_hook( string $hook, int $args ): void {
		$this->hook_calls = array();
		add_action(
			$hook,
			function () {
				$this->hook_calls[] = func_get_args();
			},
			10,
			$args
		);
	}

	/**
	 * Create a pending request owned by nobody and return its identifiers.
	 *
	 * @return array{request_id:int,asset_id:int,requester_id:int}
	 */
	private function create_pending_request(): array {
		$requester_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id     = $this->create_asset();
		$request_id   = $this->insert_pending_request( $asset_id, $requester_id, 0, 'Please approve me' );

		return array(
			'request_id'   => $request_id,
			'asset_id'     => $asset_id,
			'requester_id' => $requester_id,
		);
	}

	/**
	 * Create an asset on loan to a new member.
	 *
	 * @return array{asset_id:int,owner_id:int}
	 */
	private function create_asset_on_loan(): array {
		$owner_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id = $this->create_asset(
			array(
				'state'    => 'on-loan',
				'owner_id' => $owner_id,
			)
		);

		return array(
			'asset_id' => $asset_id,
			'owner_id' => $owner_id,
		);
	}

	/**
	 * Sending an available asset to maintenance writes a history row and fires no hook.
	 *
	 * @return void
	 */
	public function test_change_state_sends_asset_to_maintenance() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset();
		$this->listen_to_hook( 'almgr_asset_force_returned', 4 );

		$result = $this->get_loan_manager()->change_state( $asset_id, 'maintenance', 'Lens cleaning.', 'Workshop', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result['skipped_components'] );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $asset_id ) );

		$history = $this->get_history_rows_for_asset( $asset_id );
		$this->assertCount( 1, $history );
		$this->assertSame( 'to_maintenance', $history[0]->status );
		$this->assertSame( $operator_id, (int) $history[0]->changed_by );
		$this->assertSame( array(), $this->hook_calls, 'Only a force-return fires the force-return hook.' );
	}

	/**
	 * A force-return fires the force-return hook once with the previous owner.
	 *
	 * @return void
	 */
	public function test_change_state_force_return_fires_hook_once() {
		$loan        = $this->create_asset_on_loan();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$this->listen_to_hook( 'almgr_asset_force_returned', 4 );

		$result = $this->get_loan_manager()->change_state( $loan['asset_id'], 'available', 'Returned late.', 'Shelf 1', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( 'available', $this->get_asset_state_slug( $loan['asset_id'] ) );
		$this->assertSame( 0, (int) get_post_meta( $loan['asset_id'], '_almgr_current_owner', true ) );
		$this->assertCount( 1, $this->hook_calls, 'The force-return hook must fire exactly once.' );
		$this->assertSame( array( $loan['asset_id'], $loan['owner_id'], $operator_id, 'Returned late.' ), $this->hook_calls[0] );
	}

	/**
	 * Each invalid state change returns its own error code and changes nothing.
	 *
	 * @return void
	 */
	public function test_change_state_validation_errors() {
		$operator_id    = $this->create_user_with_role( 'administrator' );
		$asset_id       = $this->create_asset();
		$maintenance_id = $this->create_asset( array( 'state' => 'maintenance' ) );
		$draft_id       = $this->create_asset( array( 'post_status' => 'draft' ) );
		$plain_post_id  = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$manager        = $this->get_loan_manager();
		$this->listen_to_hook( 'almgr_asset_force_returned', 4 );

		$cases = array(
			'invalid asset id'      => array( array( 0, 'maintenance', '', 'Workshop', $operator_id ), 'almgr_invalid_asset_id' ),
			'no location'           => array( array( $asset_id, 'maintenance', '', '', $operator_id ), 'almgr_location_required' ),
			'location too long'     => array( array( $asset_id, 'maintenance', '', str_repeat( 'a', 256 ), $operator_id ), 'almgr_location_too_long' ),
			'invalid target state'  => array( array( $asset_id, 'on-loan', '', 'Workshop', $operator_id ), 'almgr_invalid_target_state' ),
			'notes too long'        => array( array( $asset_id, 'maintenance', str_repeat( 'a', 501 ), 'Workshop', $operator_id ), 'almgr_notes_too_long' ),
			'asset not found'       => array( array( 999999, 'maintenance', '', 'Workshop', $operator_id ), 'almgr_asset_not_found' ),
			'not an asset'          => array( array( $plain_post_id, 'maintenance', '', 'Workshop', $operator_id ), 'almgr_asset_not_found' ),
			'draft asset'           => array( array( $draft_id, 'maintenance', '', 'Workshop', $operator_id ), 'almgr_asset_not_changeable' ),
			'force-return not loan' => array( array( $asset_id, 'available', '', 'Workshop', $operator_id ), 'almgr_asset_not_on_loan' ),
			'from maintenance'      => array( array( $maintenance_id, 'retired', '', 'Workshop', $operator_id ), 'almgr_invalid_state_transition' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->change_state( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $maintenance_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
		$this->assertSame( array(), $this->hook_calls );
	}

	/**
	 * Restoring an asset from maintenance makes it available again and writes history.
	 *
	 * @return void
	 */
	public function test_restore_state_restores_asset_from_maintenance() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$asset_id    = $this->create_asset( array( 'state' => 'maintenance' ) );

		$result = $this->get_loan_manager()->restore_state( $asset_id, 'Repaired.', 'Shelf 3', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result['skipped_components'] );
		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ) );

		$history = $this->get_history_rows_for_asset( $asset_id );
		$this->assertCount( 1, $history );
		$this->assertSame( 'to_available', $history[0]->status );
		$this->assertSame( $operator_id, (int) $history[0]->changed_by );
	}

	/**
	 * Each invalid restore returns its own error code and changes nothing.
	 *
	 * @return void
	 */
	public function test_restore_state_validation_errors() {
		$operator_id    = $this->create_user_with_role( 'administrator' );
		$maintenance_id = $this->create_asset( array( 'state' => 'maintenance' ) );
		$available_id   = $this->create_asset();
		$draft_id       = $this->create_asset(
			array(
				'post_status' => 'draft',
				'state'       => 'maintenance',
			)
		);
		$plain_post_id  = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$manager        = $this->get_loan_manager();

		$cases = array(
			'invalid asset id'  => array( array( 0, '', 'Shelf 3', $operator_id ), 'almgr_invalid_asset_id' ),
			'no location'       => array( array( $maintenance_id, '', '', $operator_id ), 'almgr_location_required' ),
			'location too long' => array( array( $maintenance_id, '', str_repeat( 'a', 256 ), $operator_id ), 'almgr_location_too_long' ),
			'notes too long'    => array( array( $maintenance_id, str_repeat( 'a', 501 ), 'Shelf 3', $operator_id ), 'almgr_notes_too_long' ),
			'asset not found'   => array( array( 999999, '', 'Shelf 3', $operator_id ), 'almgr_asset_not_found' ),
			'not an asset'      => array( array( $plain_post_id, '', 'Shelf 3', $operator_id ), 'almgr_asset_not_found' ),
			'draft asset'       => array( array( $draft_id, '', 'Shelf 3', $operator_id ), 'almgr_asset_not_changeable' ),
			'not restorable'    => array( array( $available_id, '', 'Shelf 3', $operator_id ), 'almgr_not_restorable' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->restore_state( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $maintenance_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $maintenance_id ) );
	}

	/**
	 * A checked return by an operator returns the asset and fires the hook once.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_returns_asset_and_fires_hook_once() {
		$loan        = $this->create_asset_on_loan();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$this->listen_to_hook( 'almgr_asset_returned', 4 );

		$result = $this->get_loan_manager()->return_asset_checked( $loan['asset_id'], 'Back from the workshop.', 'Shelf 2', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result['skipped_components'] );
		$this->assertSame( 'available', $this->get_asset_state_slug( $loan['asset_id'] ) );
		$this->assertSame( 0, (int) get_post_meta( $loan['asset_id'], '_almgr_current_owner', true ) );

		$history = $this->get_history_rows_for_asset( $loan['asset_id'] );
		$this->assertCount( 1, $history );
		$this->assertSame( 'returned', $history[0]->status );

		$this->assertCount( 1, $this->hook_calls, 'The return hook must fire exactly once.' );
		$this->assertSame( array( $loan['asset_id'], $loan['owner_id'], $operator_id, 'Back from the workshop.' ), $this->hook_calls[0] );
	}

	/**
	 * The owning member may return only when member returns are enabled; other members never may.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_permission_for_members() {
		$loan      = $this->create_asset_on_loan();
		$other_id  = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$manager   = $this->get_loan_manager();
		$settings  = new ALMGR_Settings_Manager();

		$settings->set( 'workflow.member_return_enabled', false );
		$result = $manager->return_asset_checked( $loan['asset_id'], '', 'Shelf 2', $loan['owner_id'] );
		$this->assertWPError( $result );
		$this->assertSame( 'almgr_forbidden', $result->get_error_code() );

		$settings->set( 'workflow.member_return_enabled', true );
		$result = $manager->return_asset_checked( $loan['asset_id'], '', 'Shelf 2', $other_id );
		$this->assertWPError( $result );
		$this->assertSame( 'almgr_forbidden', $result->get_error_code() );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $loan['asset_id'] ) );

		$result = $manager->return_asset_checked( $loan['asset_id'], '', 'Shelf 2', $loan['owner_id'] );
		$this->assertIsArray( $result );
		$this->assertSame( 'available', $this->get_asset_state_slug( $loan['asset_id'] ) );
	}

	/**
	 * Each invalid return input returns its own error code and changes nothing.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_validation_errors() {
		$loan         = $this->create_asset_on_loan();
		$operator_id  = $this->create_user_with_role( 'administrator' );
		$available_id = $this->create_asset();
		$draft_id     = $this->create_asset(
			array(
				'post_status' => 'draft',
				'state'       => 'on-loan',
			)
		);
		$plain_post   = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$manager      = $this->get_loan_manager();
		$this->listen_to_hook( 'almgr_asset_returned', 4 );

		$cases = array(
			'invalid asset id' => array( array( 0, '', 'Shelf 2', $operator_id ), 'almgr_invalid_asset_id' ),
			'asset not found'  => array( array( 999999, '', 'Shelf 2', $operator_id ), 'almgr_asset_not_found' ),
			'not an asset'     => array( array( $plain_post, '', 'Shelf 2', $operator_id ), 'almgr_asset_not_found' ),
			'draft asset'      => array( array( $draft_id, '', 'Shelf 2', $operator_id ), 'almgr_asset_not_found' ),
			'not on loan'      => array( array( $available_id, '', 'Shelf 2', $operator_id ), 'almgr_asset_not_on_loan' ),
			'no location'      => array( array( $loan['asset_id'], '', '', $operator_id ), 'almgr_location_required' ),
			'location too long' => array( array( $loan['asset_id'], '', str_repeat( 'a', 256 ), $operator_id ), 'almgr_location_too_long' ),
			'notes too long'   => array( array( $loan['asset_id'], str_repeat( 'a', 501 ), 'Shelf 2', $operator_id ), 'almgr_notes_too_long' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->return_asset_checked( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $loan['asset_id'] ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $loan['asset_id'] ) );
		$this->assertSame( array(), $this->hook_calls, 'The hook must not fire on failure.' );
	}

	/**
	 * The location is required on a cooperative return, with warehouse management on or off.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_requires_location_with_and_without_warehouse() {
		$operator_id = $this->create_user_with_role( 'administrator' );

		foreach ( array( false, true ) as $warehouse_enabled ) {
			( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', $warehouse_enabled );
			$loan = $this->create_asset_on_loan();

			$result = $this->get_loan_manager()->return_asset_checked( $loan['asset_id'], '', '', $operator_id );

			$this->assertWPError( $result, 'warehouse ' . ( $warehouse_enabled ? 'on' : 'off' ) );
			$this->assertSame( 'almgr_location_required', $result->get_error_code() );
			$this->assertSame( 'on-loan', $this->get_asset_state_slug( $loan['asset_id'] ) );
		}
	}

	/**
	 * A cooperative return writes the location and never changes the warehouse.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_writes_location_and_keeps_warehouse() {
		$operator_id = $this->create_user_with_role( 'administrator' );

		foreach ( array( false, true ) as $warehouse_enabled ) {
			( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', $warehouse_enabled );
			$loan = $this->create_asset_on_loan();
			wp_set_object_terms( $loan['asset_id'], 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

			$result = $this->get_loan_manager()->return_asset_checked( $loan['asset_id'], '', 'Optics lab, shelf 2', $operator_id );

			$label = 'warehouse ' . ( $warehouse_enabled ? 'on' : 'off' );
			$this->assertIsArray( $result, $label );
			$this->assertSame( 'available', $this->get_asset_state_slug( $loan['asset_id'] ), $label );
			$this->assertSame( 'Optics lab, shelf 2', get_post_meta( $loan['asset_id'], 'almgr_location', true ), $label );
			$this->assertSame(
				array( 'main-warehouse' ),
				wp_get_object_terms( $loan['asset_id'], ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, array( 'fields' => 'slugs' ) ),
				$label
			);
		}
	}

	/**
	 * Returning a kit writes the location on the kit and its returned components, and leaves their warehouse alone.
	 *
	 * @return void
	 */
	public function test_return_asset_checked_writes_location_on_kit_and_components() {
		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', true );
		$operator_id  = $this->create_user_with_role( 'administrator' );
		$owner_id     = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$kit_id       = $this->create_asset(
			array(
				'structure' => ALMGR_ASSET_KIT_SLUG,
				'state'     => 'on-loan',
				'owner_id'  => $owner_id,
			)
		);
		$component_id = $this->create_asset(
			array(
				'state'    => 'on-loan',
				'owner_id' => $owner_id,
			)
		);
		update_post_meta( $kit_id, 'almgr_components', array( $component_id ) );
		wp_set_object_terms( $kit_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $component_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$result = $this->get_loan_manager()->return_asset_checked( $kit_id, '', 'Optics lab, shelf 2', $operator_id );

		$this->assertIsArray( $result );
		foreach ( array( $kit_id, $component_id ) as $id ) {
			$this->assertSame( 'available', $this->get_asset_state_slug( $id ) );
			$this->assertSame( 'Optics lab, shelf 2', get_post_meta( $id, 'almgr_location', true ) );
			$this->assertSame(
				array( 'main-warehouse' ),
				wp_get_object_terms( $id, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, array( 'fields' => 'slugs' ) )
			);
		}
	}

	/**
	 * Maintenance, retirement, forced return and restore write the location and never touch the warehouse.
	 *
	 * @return void
	 */
	public function test_state_changes_write_location_and_keep_warehouse() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$manager     = $this->get_loan_manager();

		foreach ( array( false, true ) as $warehouse_enabled ) {
			( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', $warehouse_enabled );

			foreach ( array( 'maintenance', 'retired' ) as $target ) {
				$label    = 'warehouse ' . ( $warehouse_enabled ? 'on' : 'off' ) . ', ' . $target;
				$asset_id = $this->create_asset();
				wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

				$result = $manager->change_state( $asset_id, $target, '', 'Repair shop', $operator_id );
				$this->assertIsArray( $result, $label );
				$this->assertSame( $target, $this->get_asset_state_slug( $asset_id ), $label );
				$this->assertSame( 'Repair shop', get_post_meta( $asset_id, 'almgr_location', true ), $label );
				$this->assertSame( array( 'main-warehouse' ), $this->get_warehouse_slugs( $asset_id ), $label );

				$result = $manager->restore_state( $asset_id, '', 'Optics lab', $operator_id );
				$this->assertIsArray( $result, $label );
				$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ), $label );
				$this->assertSame( 'Optics lab', get_post_meta( $asset_id, 'almgr_location', true ), $label );
				$this->assertSame( array( 'main-warehouse' ), $this->get_warehouse_slugs( $asset_id ), $label );
			}

			$loan   = $this->create_asset_on_loan();
			$label  = 'forced return, warehouse ' . ( $warehouse_enabled ? 'on' : 'off' );
			$result = $manager->change_state( $loan['asset_id'], 'available', '', 'Front desk', $operator_id );
			$this->assertIsArray( $result, $label );
			$this->assertSame( 'available', $this->get_asset_state_slug( $loan['asset_id'] ), $label );
			$this->assertSame( 'Front desk', get_post_meta( $loan['asset_id'], 'almgr_location', true ), $label );
		}
	}

	/**
	 * Sending a kit to maintenance and restoring it writes the location on the kit and its components only.
	 *
	 * @return void
	 */
	public function test_kit_state_changes_write_location_on_kit_and_components_and_keep_warehouse() {
		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', true );
		$operator_id  = $this->create_user_with_role( 'administrator' );
		$kit_id       = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$component_id = $this->create_asset();
		$other_id     = $this->create_asset();
		update_post_meta( $kit_id, 'almgr_components', array( $component_id ) );
		foreach ( array( $kit_id, $component_id, $other_id ) as $id ) {
			wp_set_object_terms( $id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		}

		$result = $this->get_loan_manager()->change_state( $kit_id, 'maintenance', '', 'Repair shop', $operator_id );
		$this->assertIsArray( $result );
		foreach ( array( $kit_id, $component_id ) as $id ) {
			$this->assertSame( 'maintenance', $this->get_asset_state_slug( $id ) );
			$this->assertSame( 'Repair shop', get_post_meta( $id, 'almgr_location', true ) );
		}

		$result = $this->get_loan_manager()->restore_state( $kit_id, '', 'Optics lab', $operator_id );
		$this->assertIsArray( $result );
		foreach ( array( $kit_id, $component_id ) as $id ) {
			$this->assertSame( 'available', $this->get_asset_state_slug( $id ) );
			$this->assertSame( 'Optics lab', get_post_meta( $id, 'almgr_location', true ) );
		}

		$this->assertSame( '', (string) get_post_meta( $other_id, 'almgr_location', true ), 'An unrelated asset must not receive the location.' );
		foreach ( array( $kit_id, $component_id, $other_id ) as $id ) {
			$this->assertSame( array( 'main-warehouse' ), $this->get_warehouse_slugs( $id ) );
		}
	}

	/**
	 * Return the warehouse term slugs assigned to an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return string[]
	 */
	private function get_warehouse_slugs( int $asset_id ): array {
		return wp_get_object_terms( $asset_id, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, array( 'fields' => 'slugs' ) );
	}

	/**
	 * Direct assignment through the domain method transfers the asset and fires the hook once.
	 *
	 * @return void
	 */
	public function test_assign_asset_assigns_and_fires_hook_once() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$assignee_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id    = $this->create_asset();
		$this->listen_to_hook( 'almgr_direct_assign', 5 );

		$result = $this->get_loan_manager()->assign_asset( $asset_id, $assignee_id, 'Lent for the workshop.', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['previous_owner_id'] );
		$this->assertSame( array(), $result['excluded_components'] );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( $assignee_id, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ) );

		$history = $this->get_history_rows_for_asset( $asset_id );
		$this->assertCount( 1, $history );
		$this->assertSame( 'direct_assign', $history[0]->status );
		$this->assertSame( $operator_id, (int) $history[0]->changed_by );

		$this->assertCount( 1, $this->hook_calls, 'The direct assignment hook must fire exactly once.' );
		$this->assertSame( array( $asset_id, $assignee_id, $operator_id, 'Lent for the workshop.', 0 ), $this->hook_calls[0] );
	}

	/**
	 * Reassigning an on-loan asset reports the previous owner in the result and in the hook.
	 *
	 * @return void
	 */
	public function test_assign_asset_reports_previous_owner() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$previous_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$assignee_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id    = $this->create_asset(
			array(
				'state'    => 'on-loan',
				'owner_id' => $previous_id,
			)
		);
		$this->listen_to_hook( 'almgr_direct_assign', 5 );

		$result = $this->get_loan_manager()->assign_asset( $asset_id, $assignee_id, 'Moved to another member.', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( $previous_id, $result['previous_owner_id'] );
		$this->assertCount( 1, $this->hook_calls );
		$this->assertSame( $previous_id, $this->hook_calls[0][4] );
	}

	/**
	 * Each invalid assignment input returns its own error code and changes nothing.
	 *
	 * @return void
	 */
	public function test_assign_asset_validation_errors() {
		$operator_id   = $this->create_user_with_role( 'administrator' );
		$assignee_id   = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$ineligible_id = $this->create_user_with_role( 'subscriber' );
		$asset_id      = $this->create_asset();
		$draft_id      = $this->create_asset( array( 'post_status' => 'draft' ) );
		$plain_post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$manager       = $this->get_loan_manager();
		$this->listen_to_hook( 'almgr_direct_assign', 5 );

		$cases = array(
			'invalid asset id'    => array( array( 0, $assignee_id, 'Reason', $operator_id ), 'almgr_invalid_asset_id' ),
			'invalid assignee id' => array( array( $asset_id, 0, 'Reason', $operator_id ), 'almgr_invalid_assignee_id' ),
			'empty reason'        => array( array( $asset_id, $assignee_id, '', $operator_id ), 'almgr_assign_reason_required' ),
			'zero as reason'      => array( array( $asset_id, $assignee_id, '0', $operator_id ), 'almgr_assign_reason_required' ),
			'reason too long'     => array( array( $asset_id, $assignee_id, str_repeat( 'a', 5000 ), $operator_id ), 'almgr_assign_reason_too_long' ),
			'asset not found'     => array( array( 999999, $assignee_id, 'Reason', $operator_id ), 'almgr_asset_not_found' ),
			'not an asset'        => array( array( $plain_post_id, $assignee_id, 'Reason', $operator_id ), 'almgr_asset_not_found' ),
			'draft asset'         => array( array( $draft_id, $assignee_id, 'Reason', $operator_id ), 'almgr_asset_not_assignable' ),
			'assignee not found'  => array( array( $asset_id, 999999, 'Reason', $operator_id ), 'almgr_assignee_not_found' ),
			'assignee ineligible' => array( array( $asset_id, $ineligible_id, 'Reason', $operator_id ), 'almgr_assignee_not_eligible' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->assign_asset( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertSame( 'available', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $asset_id ) );
		$this->assertSame( array(), $this->hook_calls, 'The hook must not fire on failure.' );
	}

	/**
	 * An asset in maintenance fails inside the workflow with its own message.
	 *
	 * @return void
	 */
	public function test_assign_asset_reports_workflow_failure_for_asset_in_maintenance() {
		$operator_id = $this->create_user_with_role( 'administrator' );
		$assignee_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id    = $this->create_asset( array( 'state' => 'maintenance' ) );
		$this->listen_to_hook( 'almgr_direct_assign', 5 );

		$result = $this->get_loan_manager()->assign_asset( $asset_id, $assignee_id, 'Reason', $operator_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_assign_failed', $result->get_error_code() );
		$this->assertSame( 'Cannot assign an asset that is in maintenance or retired state.', $result->get_error_message() );
		$this->assertSame( 'maintenance', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( array(), $this->hook_calls );
	}

	/**
	 * The disabled direct assignment setting is checked before any other input.
	 *
	 * @return void
	 */
	public function test_assign_asset_respects_disabled_setting_first() {
		( new ALMGR_Settings_Manager() )->set( 'direct_assign.enabled', false );
		$operator_id = $this->create_user_with_role( 'administrator' );

		$result = $this->get_loan_manager()->assign_asset( 0, 0, '', $operator_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_direct_assign_disabled', $result->get_error_code() );
	}

	/**
	 * Approving through the domain method transfers the asset, cancels competitors and fires the hook once.
	 *
	 * @return void
	 */
	public function test_approve_request_transfers_asset_and_fires_hook_once() {
		$ids           = $this->create_pending_request();
		$operator_id   = $this->create_user_with_role( 'administrator' );
		$requester_two = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$this->insert_pending_request( $ids['asset_id'], $requester_two, 0, 'Second request' );
		$this->listen_to_hook( 'almgr_loan_request_approved', 1 );

		$result = $this->get_loan_manager()->approve_request( $ids['request_id'], $ids['asset_id'], $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( $ids['request_id'], $result['request_id'] );
		$this->assertSame( array(), $result['excluded_components'] );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $ids['asset_id'] ) );
		$this->assertSame( $ids['requester_id'], (int) get_post_meta( $ids['asset_id'], '_almgr_current_owner', true ) );
		$this->assertSame( 0, $this->count_pending_requests_for_asset( $ids['asset_id'] ) );

		$statuses = wp_list_pluck( $this->get_history_rows_for_asset( $ids['asset_id'] ), 'status' );
		$this->assertContains( 'approved', $statuses );
		$this->assertContains( 'canceled', $statuses );

		$this->assertCount( 1, $this->hook_calls, 'The approval hook must fire exactly once.' );
		$this->assertSame( $ids['request_id'], (int) $this->hook_calls[0][0]->id );
	}

	/**
	 * Each invalid approval input returns its own error code and changes nothing.
	 *
	 * @return void
	 */
	public function test_approve_request_validation_errors() {
		$ids         = $this->create_pending_request();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$member_id   = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$manager     = $this->get_loan_manager();
		$this->listen_to_hook( 'almgr_loan_request_approved', 1 );

		$cases = array(
			'invalid request id' => array( array( 0, $ids['asset_id'], $operator_id ), 'almgr_invalid_request_id' ),
			'invalid asset id'   => array( array( $ids['request_id'], 0, $operator_id ), 'almgr_invalid_asset_id' ),
			'request not found'  => array( array( 999999, $ids['asset_id'], $operator_id ), 'almgr_loan_request_not_found' ),
			'asset id mismatch'  => array( array( $ids['request_id'], $ids['asset_id'] + 1000, $operator_id ), 'almgr_asset_mismatch' ),
			'unrelated member'   => array( array( $ids['request_id'], $ids['asset_id'], $member_id ), 'almgr_forbidden' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->approve_request( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertNotNull( $this->get_request_row( $ids['request_id'] ) );
		$this->assertSame( 'available', $this->get_asset_state_slug( $ids['asset_id'] ) );
		$this->assertSame( array(), $this->hook_calls, 'The hook must not fire on failure.' );
	}

	/**
	 * A request that is no longer pending fails inside the workflow with its own message.
	 *
	 * @return void
	 */
	public function test_approve_request_reports_workflow_failure_for_non_pending_request() {
		global $wpdb;
		$ids         = $this->create_pending_request();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$wpdb->update( $this->get_requests_table_name(), array( 'status' => 'canceled' ), array( 'id' => $ids['request_id'] ), array( '%s' ), array( '%d' ) );
		$this->listen_to_hook( 'almgr_loan_request_approved', 1 );

		$result = $this->get_loan_manager()->approve_request( $ids['request_id'], $ids['asset_id'], $operator_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_approve_failed', $result->get_error_code() );
		$this->assertSame( 'Request is not pending.', $result->get_error_message() );
		$this->assertSame( array(), $this->hook_calls );
	}

	/**
	 * The disabled-requests setting is checked first for approval too.
	 *
	 * @return void
	 */
	public function test_approve_request_respects_disabled_loan_requests_setting_first() {
		( new ALMGR_Settings_Manager() )->set( 'loans.loan_requests_enabled', false );
		$operator_id = $this->create_user_with_role( 'administrator' );

		$result = $this->get_loan_manager()->approve_request( 0, 0, $operator_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_loan_requests_disabled', $result->get_error_code() );
	}

	/**
	 * Rejecting through the domain method writes history, removes the request and fires the hook once.
	 *
	 * @return void
	 */
	public function test_reject_request_rejects_and_fires_hook_once() {
		$ids         = $this->create_pending_request();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$this->listen_to_hook( 'almgr_loan_request_rejected', 2 );

		$result = $this->get_loan_manager()->reject_request( $ids['request_id'], $ids['asset_id'], 'Not available this week.', $operator_id );

		$this->assertIsArray( $result );
		$this->assertSame( $ids['request_id'], $result['request_id'] );
		$this->assertNull( $this->get_request_row( $ids['request_id'] ) );

		$history = $this->get_history_rows_for_asset( $ids['asset_id'] );
		$this->assertCount( 1, $history );
		$this->assertSame( 'rejected', $history[0]->status );
		$this->assertSame( 'Not available this week.', $history[0]->message );
		$this->assertSame( $operator_id, (int) $history[0]->changed_by );

		$this->assertCount( 1, $this->hook_calls, 'The rejection hook must fire exactly once.' );
		$this->assertSame( $ids['request_id'], (int) $this->hook_calls[0][0]->id );
		$this->assertSame( 'Not available this week.', $this->hook_calls[0][1] );
	}

	/**
	 * Each invalid input returns its own error code and leaves the request untouched.
	 *
	 * @return void
	 */
	public function test_reject_request_validation_errors() {
		$ids         = $this->create_pending_request();
		$operator_id = $this->create_user_with_role( 'administrator' );
		$manager     = $this->get_loan_manager();
		$this->listen_to_hook( 'almgr_loan_request_rejected', 2 );

		$cases = array(
			'invalid request id'   => array( array( 0, $ids['asset_id'], 'Reason', $operator_id ), 'almgr_invalid_request_id' ),
			'invalid asset id'     => array( array( $ids['request_id'], 0, 'Reason', $operator_id ), 'almgr_invalid_asset_id' ),
			'empty message'        => array( array( $ids['request_id'], $ids['asset_id'], '', $operator_id ), 'almgr_rejection_message_required' ),
			'zero as message'      => array( array( $ids['request_id'], $ids['asset_id'], '0', $operator_id ), 'almgr_rejection_message_required' ),
			'message too long'     => array( array( $ids['request_id'], $ids['asset_id'], str_repeat( 'a', 5000 ), $operator_id ), 'almgr_rejection_message_too_long' ),
			'request not found'    => array( array( 999999, $ids['asset_id'], 'Reason', $operator_id ), 'almgr_loan_request_not_found' ),
			'asset id mismatch'    => array( array( $ids['request_id'], $ids['asset_id'] + 1000, 'Reason', $operator_id ), 'almgr_asset_mismatch' ),
		);

		foreach ( $cases as $label => $case ) {
			$result = $manager->reject_request( ...$case[0] );
			$this->assertWPError( $result, $label );
			$this->assertSame( $case[1], $result->get_error_code(), $label );
		}

		$this->assertNotNull( $this->get_request_row( $ids['request_id'] ), 'No failed attempt may remove the request.' );
		$this->assertSame( array(), $this->get_history_rows_for_asset( $ids['asset_id'] ) );
		$this->assertSame( array(), $this->hook_calls, 'The hook must not fire on failure.' );
	}

	/**
	 * A user who is neither an operator nor the owner cannot reject.
	 *
	 * @return void
	 */
	public function test_reject_request_is_forbidden_for_unrelated_member() {
		$ids       = $this->create_pending_request();
		$member_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );

		$result = $this->get_loan_manager()->reject_request( $ids['request_id'], $ids['asset_id'], 'Reason', $member_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_forbidden', $result->get_error_code() );
		$this->assertNotNull( $this->get_request_row( $ids['request_id'] ) );
	}

	/**
	 * The disabled-requests setting is checked before any other input.
	 *
	 * @return void
	 */
	public function test_reject_request_respects_disabled_loan_requests_setting_first() {
		( new ALMGR_Settings_Manager() )->set( 'loans.loan_requests_enabled', false );
		$operator_id = $this->create_user_with_role( 'administrator' );

		$result = $this->get_loan_manager()->reject_request( 0, 0, '', $operator_id );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_loan_requests_disabled', $result->get_error_code() );
	}
}
