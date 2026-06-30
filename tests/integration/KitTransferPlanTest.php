<?php
/**
 * Integration tests for build_kit_transfer_plan() in ALMGR_Loan_Manager.
 *
 * The method decides which kit components travel with the kit when ownership
 * changes: it reads asset state and owner from the real DB and is invoked via
 * ReflectionMethod because it is private.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the kit transfer plan builder.
 *
 * Extends WP_UnitTestCase directly (not ALMGR_Loan_Integration_Test_Case) to avoid
 * the static boot-flag collision that would cause LoanWorkflowAjaxTest to skip
 * re-running create_default_terms() and fail subsequent tests.
 */
class ALMGR_Kit_Transfer_Plan_Integration_Test extends WP_UnitTestCase {

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
	 * Invoke the private build_kit_transfer_plan() via reflection.
	 *
	 * @param int $kit_id       Kit asset ID.
	 * @param int $new_owner_id Intended new owner (not used by the plan logic, only logged).
	 * @return array Plan array.
	 */
	private function call_transfer_plan( int $kit_id, int $new_owner_id ): array {
		$method = new ReflectionMethod( ALMGR_Loan_Manager::class, 'build_kit_transfer_plan' );
		$method->setAccessible( true );
		return $method->invoke( $this->loan_manager, $kit_id, $new_owner_id );
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
	 * The get_field() ACF shim in bootstrap-integration.php maps to get_post_meta() single=true,
	 * so a serialized array stored here is read back as an array by get_kit_components().
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
	 * All components in state=available with owner=0 are included in the plan.
	 * Kit + all component IDs appear in both transfer_asset_ids and location_clear_ids.
	 *
	 * @return void
	 */
	public function test_all_available_components_are_included(): void {
		$comp1_id = $this->create_asset( array( 'state' => 'available', 'owner_id' => 0 ) );
		$comp2_id = $this->create_asset( array( 'state' => 'available', 'owner_id' => 0 ) );
		$kit_id   = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'owner_id' => 0 ) );
		$this->link_components( $kit_id, array( $comp1_id, $comp2_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertTrue( $plan['is_kit'], 'Asset created as kit should be identified as a kit.' );
		$this->assertCount( 2, $plan['included_components'], 'Both available components should be included.' );
		$this->assertEmpty( $plan['excluded_components'], 'No component should be excluded.' );

		$included_ids = array_column( $plan['included_components'], 'id' );
		$this->assertContains( $comp1_id, $included_ids );
		$this->assertContains( $comp2_id, $included_ids );
		$this->assertContains( $kit_id,   $plan['transfer_asset_ids'] );
		$this->assertContains( $comp1_id, $plan['transfer_asset_ids'] );
		$this->assertContains( $comp2_id, $plan['transfer_asset_ids'] );
	}

	/**
	 * A component on-loan to the same user as the kit's current owner is included.
	 * The plan compares component_owner with kit_previous_owner (the kit's current owner
	 * before the transfer), not with the intended new owner.
	 *
	 * @return void
	 */
	public function test_on_loan_to_same_owner_as_kit_is_included(): void {
		$shared_owner = 42; // Arbitrary non-zero ID; the method reads it from meta, does not validate the user.
		$kit_id  = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'owner_id' => $shared_owner ) );
		$comp_id = $this->create_asset( array( 'state' => 'on-loan', 'owner_id' => $shared_owner ) );
		$this->link_components( $kit_id, array( $comp_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertCount( 1, $plan['included_components'], 'Component on-loan to the same owner as the kit should be included.' );
		$this->assertEmpty( $plan['excluded_components'] );
		$this->assertSame( $comp_id, $plan['included_components'][0]['id'] );
	}

	/**
	 * A component on-loan to a different user than the kit's current owner is excluded
	 * with reason_code on_loan_to_other_user.
	 *
	 * @return void
	 */
	public function test_on_loan_to_different_user_is_excluded(): void {
		$kit_id  = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG, 'owner_id' => 42 ) );
		$comp_id = $this->create_asset( array( 'state' => 'on-loan', 'owner_id' => 43 ) );
		$this->link_components( $kit_id, array( $comp_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertEmpty( $plan['included_components'] );
		$this->assertCount( 1, $plan['excluded_components'] );
		$this->assertSame( 'on_loan_to_other_user', $plan['excluded_components'][0]['reason_code'] );
		$this->assertSame( $comp_id, $plan['excluded_components'][0]['id'] );
		$this->assertNotContains( $comp_id, $plan['transfer_asset_ids'] );
	}

	/**
	 * A component in state=maintenance is excluded with reason_code maintenance.
	 *
	 * @return void
	 */
	public function test_maintenance_component_is_excluded(): void {
		$kit_id  = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$comp_id = $this->create_asset( array( 'state' => 'maintenance' ) );
		$this->link_components( $kit_id, array( $comp_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertEmpty( $plan['included_components'] );
		$this->assertCount( 1, $plan['excluded_components'] );
		$this->assertSame( 'maintenance', $plan['excluded_components'][0]['reason_code'] );
		$this->assertNotContains( $comp_id, $plan['transfer_asset_ids'] );
	}

	/**
	 * A component in state=retired is excluded with reason_code retired.
	 *
	 * @return void
	 */
	public function test_retired_component_is_excluded(): void {
		$kit_id  = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$comp_id = $this->create_asset( array( 'state' => 'retired' ) );
		$this->link_components( $kit_id, array( $comp_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertEmpty( $plan['included_components'] );
		$this->assertCount( 1, $plan['excluded_components'] );
		$this->assertSame( 'retired', $plan['excluded_components'][0]['reason_code'] );
		$this->assertNotContains( $comp_id, $plan['transfer_asset_ids'] );
	}

	/**
	 * A kit with two available and two ineligible components: counts are correct,
	 * only included IDs appear in transfer_asset_ids.
	 *
	 * @return void
	 */
	public function test_mixed_kit_partial_inclusion_counts_are_correct(): void {
		$kit_id    = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$avail1_id = $this->create_asset( array( 'state' => 'available' ) );
		$avail2_id = $this->create_asset( array( 'state' => 'available' ) );
		$maint_id  = $this->create_asset( array( 'state' => 'maintenance' ) );
		$retir_id  = $this->create_asset( array( 'state' => 'retired' ) );
		$this->link_components( $kit_id, array( $avail1_id, $avail2_id, $maint_id, $retir_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$this->assertCount( 2, $plan['included_components'], '2 available components should be included.' );
		$this->assertCount( 2, $plan['excluded_components'], 'Maintenance and retired components should be excluded.' );

		$included_ids = array_column( $plan['included_components'], 'id' );
		$this->assertContains( $avail1_id, $included_ids );
		$this->assertContains( $avail2_id, $included_ids );

		$excluded_ids = array_column( $plan['excluded_components'], 'id' );
		$this->assertContains( $maint_id, $excluded_ids );
		$this->assertContains( $retir_id, $excluded_ids );

		$this->assertContains( $avail1_id, $plan['transfer_asset_ids'] );
		$this->assertContains( $avail2_id, $plan['transfer_asset_ids'] );
		$this->assertNotContains( $maint_id, $plan['transfer_asset_ids'] );
		$this->assertNotContains( $retir_id, $plan['transfer_asset_ids'] );
	}

	/**
	 * transfer_asset_ids and location_clear_ids are always identical, regardless
	 * of which components are included or excluded from the plan.
	 *
	 * @return void
	 */
	public function test_location_clear_ids_always_match_transfer_asset_ids(): void {
		$kit_id   = $this->create_asset( array( 'structure' => ALMGR_ASSET_KIT_SLUG ) );
		$avail_id = $this->create_asset( array( 'state' => 'available' ) );
		$maint_id = $this->create_asset( array( 'state' => 'maintenance' ) );
		$this->link_components( $kit_id, array( $avail_id, $maint_id ) );

		$plan = $this->call_transfer_plan( $kit_id, 99 );

		$transfer = $plan['transfer_asset_ids'];
		$location = $plan['location_clear_ids'];
		sort( $transfer );
		sort( $location );

		$this->assertSame( $transfer, $location, 'transfer_asset_ids and location_clear_ids must always be identical.' );
	}

	/**
	 * Passing a component (non-kit) asset as kit_id returns is_kit=false, empty
	 * component lists, and transfer_asset_ids / location_clear_ids containing only
	 * the asset itself.
	 *
	 * @return void
	 */
	public function test_non_kit_asset_returns_empty_component_lists(): void {
		$comp_id = $this->create_asset( array( 'structure' => ALMGR_ASSET_COMPONENT_SLUG ) );

		$plan = $this->call_transfer_plan( $comp_id, 99 );

		$this->assertFalse( $plan['is_kit'], 'Component should not be identified as a kit.' );
		$this->assertEmpty( $plan['included_components'] );
		$this->assertEmpty( $plan['excluded_components'] );
		$this->assertSame( array( $comp_id ), $plan['transfer_asset_ids'] );
		$this->assertSame( array( $comp_id ), $plan['location_clear_ids'] );
	}
}
