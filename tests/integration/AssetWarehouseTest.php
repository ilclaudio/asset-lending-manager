<?php
/**
 * Integration tests for the almgr_warehouse taxonomy accessors.
 *
 * @package AssetLendingManager
 */

/**
 * Test class for ALMGR_Asset_Manager::get_asset_warehouse() / set_asset_warehouse().
 */
class ALMGR_Asset_Warehouse_Test extends WP_UnitTestCase {

	/**
	 * Set up shared taxonomy/CPT registration once per run.
	 */
	public function setUp(): void {
		parent::setUp();

		$asset_manager = new ALMGR_Asset_Manager();
		$asset_manager->register_post_type();
		$asset_manager->register_taxonomies();
	}

	/**
	 * The default warehouse term is seeded and assignable.
	 */
	public function test_default_warehouse_term_is_seeded() {
		ALMGR_Installer::create_default_terms();

		$term = get_term_by( 'slug', 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( 'Main warehouse', $term->name );
	}

	/**
	 * Create a published asset post for the test.
	 *
	 * @param string $title Optional distinct post title.
	 * @return int
	 */
	private function create_asset( string $title = 'Sample test asset' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
	}

	/**
	 * No warehouse term assigned returns null.
	 */
	public function test_get_asset_warehouse_returns_null_when_unassigned() {
		$asset_id = $this->create_asset();

		$this->assertNull( ALMGR_Asset_Manager::get_asset_warehouse( $asset_id ) );
	}

	/**
	 * A previously assigned warehouse term is returned.
	 */
	public function test_get_asset_warehouse_returns_assigned_term() {
		$asset_id = $this->create_asset();

		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$term = ALMGR_Asset_Manager::get_asset_warehouse( $asset_id );

		$this->assertInstanceOf( WP_Term::class, $term );
		$this->assertSame( 'main-warehouse', $term->slug );
	}

	/**
	 * set_asset_warehouse() assigns the term and returns true.
	 */
	public function test_set_asset_warehouse_assigns_term_and_returns_true() {
		$asset_id = $this->create_asset();

		$result = ALMGR_Asset_Manager::set_asset_warehouse( $asset_id, 'main-warehouse' );

		$this->assertTrue( $result );

		$term = ALMGR_Asset_Manager::get_asset_warehouse( $asset_id );
		$this->assertSame( 'main-warehouse', $term->slug );
	}

	/**
	 * set_asset_warehouse() replaces, never appends, a previous assignment.
	 */
	public function test_set_asset_warehouse_replaces_previous_assignment() {
		$asset_id = $this->create_asset();

		ALMGR_Asset_Manager::set_asset_warehouse( $asset_id, 'main-warehouse' );
		ALMGR_Asset_Manager::set_asset_warehouse( $asset_id, 'secondary-warehouse' );

		$terms = wp_get_object_terms( $asset_id, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, array( 'fields' => 'slugs' ) );

		$this->assertCount( 1, $terms );
		$this->assertSame( 'secondary-warehouse', $terms[0] );
	}

	/**
	 * get_asset_wrapper() (frontend view model) exposes the warehouse name.
	 */
	public function test_wrapper_exposes_warehouse_name() {
		$asset_id = $this->create_asset();
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$wrapper = ALMGR_Asset_Manager::get_asset_wrapper( $asset_id );

		$this->assertSame( array( 'main-warehouse' ), $wrapper->almgr_warehouse );
	}

	/**
	 * get_asset_wrapper() returns an empty array when no warehouse is assigned.
	 */
	public function test_wrapper_warehouse_is_empty_array_when_unassigned() {
		$asset_id = $this->create_asset();

		$wrapper = ALMGR_Asset_Manager::get_asset_wrapper( $asset_id );

		$this->assertSame( array(), $wrapper->almgr_warehouse );
	}

	/**
	 * The shared read service (consumed by REST and Abilities) exposes warehouse_slugs.
	 */
	public function test_shared_read_service_exposes_warehouse_slugs() {
		$asset_id = $this->create_asset();
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$read_service = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset        = $read_service->get_asset( $asset_id );

		$this->assertSame( array( 'main-warehouse' ), $asset->warehouse_slugs );
	}

	/**
	 * Render the asset-view template for a given asset and viewer.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return string
	 */
	private function render_asset_view( int $asset_id ): string {
		$read_service   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$detail_service = new ALMGR_Asset_Detail_Service( $read_service );
		$asset          = $detail_service->get_asset_detail( $asset_id );

		$template_path = ALMGR_PLUGIN_DIR . 'templates/shortcodes/asset-view.php';

		ob_start();
		include $template_path;
		return (string) ob_get_clean();
	}

	/**
	 * An anonymous visitor never sees the warehouse, in any form.
	 */
	public function test_warehouse_hidden_from_anonymous_visitor() {
		$asset_id = $this->create_asset();
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		wp_set_current_user( 0 );
		$html = $this->render_asset_view( $asset_id );

		$this->assertStringNotContainsString( 'main-warehouse', $html );
		$this->assertStringNotContainsString( 'Warehouse', $html );
	}

	/**
	 * A logged-in member sees the assigned warehouse.
	 */
	public function test_warehouse_visible_to_logged_in_member() {
		$asset_id = $this->create_asset();
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		wp_set_current_user( $member_id );
		$html = $this->render_asset_view( $asset_id );

		$this->assertStringContainsString( 'main-warehouse', $html );
		$this->assertStringContainsString( 'Warehouse', $html );

		wp_set_current_user( 0 );
	}

	/**
	 * The shared catalog query service filters assets by warehouse.
	 */
	public function test_query_service_filters_by_warehouse() {
		ALMGR_Installer::create_default_terms();

		$matching_id    = $this->create_asset();
		$non_matching_id = $this->create_asset();
		wp_set_object_terms( $matching_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $non_matching_id, array(), ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$query_service = new ALMGR_Asset_Query_Service();
		$result        = $query_service->get_assets( array( 'warehouse' => 'main-warehouse' ) );

		$this->assertContains( $matching_id, $result->get_items() );
		$this->assertNotContains( $non_matching_id, $result->get_items() );
	}

	/**
	 * Build a frontend manager instance wired with the shared services.
	 *
	 * @return ALMGR_Frontend_Manager
	 */
	private function build_frontend_manager(): ALMGR_Frontend_Manager {
		$settings      = new ALMGR_Settings_Manager();
		$loan_manager  = new ALMGR_Loan_Manager( $settings );
		$asset_reads   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history = new ALMGR_Asset_History_Service( $loan_manager );
		$member_assets = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );

		return new ALMGR_Frontend_Manager( $settings, $asset_reads, $asset_details, $asset_history, $member_assets );
	}

	/**
	 * An anonymous visitor never sees the warehouse filter, and a manually
	 * crafted query string for it has no effect on the result set.
	 */
	public function test_anonymous_visitor_cannot_use_warehouse_filter() {
		ALMGR_Installer::create_default_terms();

		$asset_id = $this->create_asset( 'Anon Warehouse Filter Asset' );
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		$other_id = $this->create_asset( 'Anon Warehouse Other Asset' );
		wp_set_object_terms( $other_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		wp_set_current_user( 0 );
		$_GET['almgr_warehouse'] = 'main-warehouse';

		$html = $this->build_frontend_manager()->shortcode_asset_list( array() );

		unset( $_GET['almgr_warehouse'] );

		$this->assertStringNotContainsString( 'almgr_filter_warehouse', $html );
		$this->assertStringContainsString( 'Anon Warehouse Filter Asset', $html );
		$this->assertStringContainsString( 'Anon Warehouse Other Asset', $html );
	}

	/**
	 * A logged-in user sees the warehouse filter select and it narrows results.
	 */
	public function test_logged_in_user_sees_and_can_use_warehouse_filter() {
		ALMGR_Installer::create_default_terms();

		$matching_id = $this->create_asset( 'Member Warehouse Matching Asset' );
		wp_set_object_terms( $matching_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $matching_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		$other_id = $this->create_asset( 'Member Warehouse Other Asset' );
		wp_set_object_terms( $other_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		wp_set_current_user( $member_id );
		$_GET['almgr_warehouse'] = 'main-warehouse';

		$html = $this->build_frontend_manager()->shortcode_asset_list( array() );

		unset( $_GET['almgr_warehouse'] );
		wp_set_current_user( 0 );

		$this->assertStringContainsString( 'almgr_filter_warehouse', $html );
		$this->assertStringContainsString( 'Member Warehouse Matching Asset', $html );
		$this->assertStringNotContainsString( 'Member Warehouse Other Asset', $html );
	}

	/**
	 * The wp-admin warehouse filter dropdown renders on the asset list screen.
	 */
	public function test_admin_warehouse_filter_dropdown_renders_on_asset_list_screen() {
		global $typenow;

		ALMGR_Installer::create_default_terms();

		$typenow = ALMGR_ASSET_CPT_SLUG;

		ob_start();
		( new ALMGR_Admin_Manager() )->render_warehouse_filter_dropdown();
		$html = ob_get_clean();

		$typenow = null;

		$this->assertStringContainsString( "name='almgr_warehouse'", $html );
		$this->assertStringContainsString( 'Main warehouse', $html );
	}

	/**
	 * The wp-admin warehouse filter dropdown is not rendered on unrelated screens.
	 */
	public function test_admin_warehouse_filter_dropdown_hidden_on_other_screens() {
		global $typenow;

		$typenow = 'post';

		ob_start();
		( new ALMGR_Admin_Manager() )->render_warehouse_filter_dropdown();
		$html = ob_get_clean();

		$typenow = null;

		$this->assertSame( '', $html );
	}

	/**
	 * Create a Kit asset linked to the given components.
	 *
	 * @param int[] $component_ids Component post IDs.
	 * @return int Kit post ID.
	 */
	private function create_kit_with_components( array $component_ids ): int {
		$kit_id = $this->create_asset( 'Warehouse Propagation Kit' );

		wp_set_object_terms( $kit_id, ALMGR_ASSET_KIT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		update_post_meta( $kit_id, 'almgr_components', $component_ids );

		return $kit_id;
	}

	/**
	 * Saving a Kit with a warehouse assigned propagates it to all current components.
	 */
	public function test_saving_kit_with_warehouse_propagates_to_components() {
		$component_1 = $this->create_asset( 'Propagation Component 1' );
		$component_2 = $this->create_asset( 'Propagation Component 2' );
		wp_set_object_terms( $component_1, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $component_2, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );

		$kit_id = $this->create_kit_with_components( array( $component_1, $component_2 ) );
		wp_set_object_terms( $kit_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		wp_update_post( array( 'ID' => $kit_id ) );

		$this->assertSame( 'main-warehouse', ALMGR_Asset_Manager::get_asset_warehouse( $component_1 )->slug );
		$this->assertSame( 'main-warehouse', ALMGR_Asset_Manager::get_asset_warehouse( $component_2 )->slug );
	}

	/**
	 * Saving a Kit with no warehouse assigned does not touch component warehouses.
	 */
	public function test_saving_kit_without_warehouse_does_not_propagate_or_clear() {
		$component_id = $this->create_asset( 'Propagation Component No Warehouse' );
		wp_set_object_terms( $component_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $component_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );

		$kit_id = $this->create_kit_with_components( array( $component_id ) );
		// The Kit itself has no warehouse assigned.

		wp_update_post( array( 'ID' => $kit_id ) );

		// The component's pre-existing warehouse must be left untouched (no reset).
		$this->assertSame( 'main-warehouse', ALMGR_Asset_Manager::get_asset_warehouse( $component_id )->slug );
	}

	/**
	 * Saving a non-Kit asset never triggers warehouse propagation.
	 */
	public function test_saving_component_asset_does_not_trigger_propagation() {
		$asset_id = $this->create_asset( 'Standalone Component' );
		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );

		wp_update_post( array( 'ID' => $asset_id ) );

		$this->assertNull( ALMGR_Asset_Manager::get_asset_warehouse( $asset_id ) );
	}

	/**
	 * The "Return asset" form displays the warehouse assigned to the asset.
	 */
	public function test_return_form_displays_warehouse() {
		ALMGR_Installer::create_default_terms();
		( new ALMGR_Settings_Manager() )->set( 'workflow.member_return_enabled', true );

		$borrower_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id    = $this->create_asset( 'Return Prefill Asset' );
		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'on-loan', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		update_post_meta( $asset_id, '_almgr_current_owner', $borrower_id );

		wp_set_current_user( $borrower_id );
		$html = $this->render_asset_view( $asset_id );
		wp_set_current_user( 0 );
		( new ALMGR_Settings_Manager() )->set( 'workflow.member_return_enabled', false );

		$this->assertStringContainsString( 'Main warehouse', $html );
		$this->assertStringNotContainsString( 'almgr-return-asset-location', $html );
	}

	/**
	 * Without an assigned warehouse, the return form indicates that none is assigned.
	 */
	public function test_return_form_indicates_missing_warehouse() {
		( new ALMGR_Settings_Manager() )->set( 'workflow.member_return_enabled', true );

		$borrower_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id    = $this->create_asset( 'Return No Warehouse Asset' );
		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'on-loan', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		update_post_meta( $asset_id, '_almgr_current_owner', $borrower_id );

		wp_set_current_user( $borrower_id );
		$html = $this->render_asset_view( $asset_id );
		wp_set_current_user( 0 );
		( new ALMGR_Settings_Manager() )->set( 'workflow.member_return_enabled', false );

		$this->assertStringContainsString( 'No warehouse assigned', $html );
		$this->assertStringNotContainsString( 'almgr-return-asset-location', $html );
	}
}
