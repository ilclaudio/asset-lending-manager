<?php
/**
 * Integration tests for build_kit_state_change_plan() in ALMGR_Loan_Manager.
 *
 * The method decides which kit components are affected when the kit's state
 * changes (→ maintenance, → retired, → available via forced-return or restore).
 * It reads asset state and owner from the real DB and is invoked via
 * ReflectionMethod because it is private.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the kit state change plan builder.
 *
 * Extends WP_UnitTestCase directly (not ALMGR_Loan_Integration_Test_Case) to avoid
 * the static boot-flag collision that would cause LoanWorkflowAjaxTest to skip
 * re-running create_default_terms() and fail.
 */
class ALMGR_Kit_State_Change_Plan_Integration_Test extends WP_UnitTestCase {

	/**
	 * Loan manager instance under test.
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

	/**
	 * Set up before the first test in this class, outside any DB transaction.
	 * Registers CPT, taxonomies, roles, default terms, and plugin DB tables once.
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
	 * Create a fresh loan manager for each test.
	 * The constructor only stores the settings reference — no hooks are registered.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->loan_manager = new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Invoke the private build_kit_state_change_plan() via reflection.
	 *
	 * @param int    $kit_id       Kit asset ID.
	 * @param string $target_state Target state slug (available, maintenance, retired).
	 * @return array Plan array.
	 */
	private function call_state_change_plan( int $kit_id, string $target_state ): array {
		$method = new ReflectionMethod( ALMGR_Loan_Manager::class, 'build_kit_state_change_plan' );
		$method->setAccessible( true );
		return $method->invoke( $this->loan_manager, $kit_id, $target_state );
	}

	/**
	 * Create a published ALM asset post and assign the four taxonomy terms.
	 *
	 * @param array $args Overrides: post_title, structure, state, type, level, owner_id.
	 * @return int Post ID.
	 */
	private function create_asset( array $args = array() ): int {
		$defaults = array(
			'post_title' => 'Test Asset ' . uniqid( '', true ),
			'structure'  => ALMGR_ASSET_COMPONENT_SLUG,
			'state'      => 'available',
			'type'       => 'generic',
			'level'      => 'basic',
			'owner_id'   => 0,
		);
		$args = array_merge( $defaults, $args );

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
	 * Store component IDs in the kit's almgr_components meta.
	 * The get_field() ACF shim in bootstrap-integration.php maps to get_post_meta() single=true.
	 *
	 * @param int   $kit_id        Kit asset ID.
	 * @param int[] $component_ids Component post IDs.
	 * @return void
	 */
	private function link_components( int $kit_id, array $component_ids ): void {
		update_post_meta( $kit_id, 'almgr_components', $component_ids );
	}

	// -------------------------------------------------------------------------
	// Tests
	// -------------------------------------------------------------------------

	/**
	 * Kit transitioning to maintenance: components already in state=available with
	 * no owner are included; components already in maintenance or retired are excluded
	 * with their own reason_code.
	 *
	 * @return void
	 */
	public function test_target_maintenance_includes_available_with_no_owner_excludes_maintenance_and_retired(): void {
		$kit_id     = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'state' => 'available', 'owner_id' => 0 ) );
		$avail_id   = $this->create_asset( array( 'state' => 'available', 'owner_id' => 0 ) );
		$maint_id   = $this->create_asset( array( 'state' => 'maintenance' ) );
		$retired_id = $this->create_asset( array( 'state' => 'retired' ) );
		$this->link_components( $kit_id, array( $avail_id, $maint_id, $retired_id ) );

		$plan = $this->call_state_change_plan( $kit_id, 'maintenance' );

		$this->assertTrue( $plan['is_kit'] );
		$this->assertCount( 1, $plan['included_components'], 'Only available/owner=0 component should be included.' );
		$this->assertSame( $avail_id, $plan['included_components'][0]['id'] );

		$this->assertCount( 2, $plan['excluded_components'] );
		$excluded_by_id = array_column( $plan['excluded_components'], 'reason_code', 'id' );
		$this->assertSame( 'maintenance', $excluded_by_id[ $maint_id ] );
		$this->assertSame( 'retired',     $excluded_by_id[ $retired_id ] );

		$this->assertContains( $avail_id,   $plan['affected_asset_ids'] );
		$this->assertNotContains( $maint_id,   $plan['affected_asset_ids'] );
		$this->assertNotContains( $retired_id, $plan['affected_asset_ids'] );
	}

	/**
	 * Kit transitioning to retired follows the same inclusion rules as transitioning to
	 * maintenance: available/owner=0 included; maintenance excluded with reason=maintenance;
	 * on-loan to the same owner as the kit is also included (kit_owner != 0 case).
	 *
	 * @return void
	 */
	public function test_target_retired_uses_same_inclusion_rules_as_maintenance(): void {
		$kit_id         = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'state' => 'available', 'owner_id' => 0 ) );
		$avail_id       = $this->create_asset( array( 'state' => 'available', 'owner_id' => 0 ) );
		$maint_id       = $this->create_asset( array( 'state' => 'maintenance' ) );
		$this->link_components( $kit_id, array( $avail_id, $maint_id ) );

		$plan = $this->call_state_change_plan( $kit_id, 'retired' );

		$this->assertCount( 1, $plan['included_components'], 'Only available/owner=0 component should be included.' );
		$this->assertSame( $avail_id, $plan['included_components'][0]['id'] );

		$this->assertCount( 1, $plan['excluded_components'] );
		$this->assertSame( 'maintenance', $plan['excluded_components'][0]['reason_code'] );
		$this->assertSame( $maint_id,     $plan['excluded_components'][0]['id'] );
	}

	/**
	 * Forced return (target=available, kit in state=on-loan): only components that are
	 * on-loan to the same owner as the kit are included; components with any other state
	 * or owner are excluded with reason_code not_controlled_by_kit.
	 *
	 * @return void
	 */
	public function test_target_available_force_return_includes_only_on_loan_to_kit_owner(): void {
		$kit_id           = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'state' => 'on-loan', 'owner_id' => 42 ) );
		$controlled_id    = $this->create_asset( array( 'state' => 'on-loan', 'owner_id' => 42 ) ); // same owner → INCLUDE
		$other_user_id    = $this->create_asset( array( 'state' => 'on-loan', 'owner_id' => 43 ) ); // different owner → EXCLUDE
		$avail_id         = $this->create_asset( array( 'state' => 'available', 'owner_id' => 0 ) ); // not on-loan → EXCLUDE
		$this->link_components( $kit_id, array( $controlled_id, $other_user_id, $avail_id ) );

		$plan = $this->call_state_change_plan( $kit_id, 'available' );

		$this->assertCount( 1, $plan['included_components'], 'Only component on-loan to the kit owner should be included.' );
		$this->assertSame( $controlled_id, $plan['included_components'][0]['id'] );

		$this->assertCount( 2, $plan['excluded_components'] );
		$excluded_ids = array_column( $plan['excluded_components'], 'id' );
		$this->assertContains( $other_user_id, $excluded_ids );
		$this->assertContains( $avail_id,      $excluded_ids );

		foreach ( $plan['excluded_components'] as $exc ) {
			$this->assertSame( 'not_controlled_by_kit', $exc['reason_code'] );
		}

		$this->assertNotContains( $other_user_id, $plan['affected_asset_ids'] );
		$this->assertNotContains( $avail_id,      $plan['affected_asset_ids'] );
	}

	/**
	 * Restore from maintenance (target=available, kit in state=maintenance):
	 * only components that are in the same state as the kit AND have no owner are included.
	 * Components in the same state but with an owner, or in a different state, are excluded.
	 *
	 * @return void
	 */
	public function test_target_available_restore_includes_only_components_in_same_state_as_kit(): void {
		$kit_id             = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'state' => 'maintenance', 'owner_id' => 0 ) );
		$controlled_id      = $this->create_asset( array( 'state' => 'maintenance', 'owner_id' => 0 ) );  // same state, no owner → INCLUDE
		$maint_with_owner   = $this->create_asset( array( 'state' => 'maintenance', 'owner_id' => 42 ) ); // same state, has owner → EXCLUDE
		$avail_id           = $this->create_asset( array( 'state' => 'available',   'owner_id' => 0 ) );  // different state → EXCLUDE
		$this->link_components( $kit_id, array( $controlled_id, $maint_with_owner, $avail_id ) );

		$plan = $this->call_state_change_plan( $kit_id, 'available' );

		$this->assertCount( 1, $plan['included_components'], 'Only component in same state as kit with no owner should be included.' );
		$this->assertSame( $controlled_id, $plan['included_components'][0]['id'] );

		$this->assertCount( 2, $plan['excluded_components'] );
		$excluded_ids = array_column( $plan['excluded_components'], 'id' );
		$this->assertContains( $maint_with_owner, $excluded_ids );
		$this->assertContains( $avail_id,         $excluded_ids );

		foreach ( $plan['excluded_components'] as $exc ) {
			$this->assertSame( 'not_controlled_by_kit', $exc['reason_code'] );
		}

		$this->assertContains( $controlled_id,   $plan['affected_asset_ids'] );
		$this->assertNotContains( $maint_with_owner, $plan['affected_asset_ids'] );
		$this->assertNotContains( $avail_id,         $plan['affected_asset_ids'] );
	}

	/**
	 * Passing a component (non-kit) asset returns is_kit=false and empty component
	 * lists; affected_asset_ids and location_target_ids contain only the asset itself.
	 *
	 * @return void
	 */
	public function test_non_kit_asset_returns_empty_plan(): void {
		$comp_id = $this->create_asset( array( 'structure' => ALMGR_ASSET_COMPONENT_SLUG ) );

		$plan = $this->call_state_change_plan( $comp_id, 'maintenance' );

		$this->assertFalse( $plan['is_kit'], 'Component should not be identified as a kit.' );
		$this->assertEmpty( $plan['included_components'] );
		$this->assertEmpty( $plan['excluded_components'] );
		$this->assertSame( array( $comp_id ), $plan['affected_asset_ids'] );
		$this->assertSame( array( $comp_id ), $plan['location_target_ids'] );
	}
}
