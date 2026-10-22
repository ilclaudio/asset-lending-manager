<?php
/**
 * Integration tests for the warehouse catalog filter across frontend, REST and Abilities.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verifies that all three channels apply the same warehouse filter rules.
 */
class ALMGR_Asset_Warehouse_Filter_Parity_Test extends WP_UnitTestCase {

	/**
	 * Operator user used to call the channels.
	 *
	 * @var int
	 */
	private $operator_id;

	/**
	 * Asset in the main warehouse.
	 *
	 * @var int
	 */
	private $main_asset_id;

	/**
	 * Asset in the secondary warehouse.
	 *
	 * @var int
	 */
	private $secondary_asset_id;

	/**
	 * Asset without a warehouse.
	 *
	 * @var int
	 */
	private $unassigned_asset_id;

	/**
	 * Set up services, terms and one asset per warehouse state.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$settings = new ALMGR_Settings_Manager();
		$settings->set( 'warehouse.enabled', true );
		( new ALMGR_Role_Manager() )->activate();

		$asset_manager = new ALMGR_Asset_Manager();
		$asset_manager->register_post_type();
		$asset_manager->register_taxonomies();
		ALMGR_Installer::create_default_terms();
		wp_insert_term( 'Secondary warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, array( 'slug' => 'secondary-warehouse' ) );

		$loan_manager  = new ALMGR_Loan_Manager( $settings );
		$asset_reads   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history = new ALMGR_Asset_History_Service( $loan_manager );
		$member_assets = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );

		$rest_manager = new ALMGR_REST_Manager( $settings, $asset_reads, $asset_details, $asset_history, $member_assets );
		$rest_manager->register();
		$GLOBALS['wp_rest_server'] = null;

		if ( function_exists( 'wp_get_ability' ) && ! wp_get_ability( 'almgr/list-assets' ) ) {
			$abilities = new ALMGR_Abilities_Manager( $asset_reads, $asset_details, $asset_history, $member_assets, null, null, $loan_manager );
			$abilities->register();
			do_action( 'wp_abilities_api_categories_init' );
			do_action( 'wp_abilities_api_init' );
		}

		$this->operator_id         = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$this->main_asset_id       = $this->create_asset( 'WH Alpha', 'main-warehouse' );
		$this->secondary_asset_id  = $this->create_asset( 'WH Beta', 'secondary-warehouse' );
		$this->unassigned_asset_id = $this->create_asset( 'WH Gamma', '' );

		wp_set_current_user( $this->operator_id );
	}

	/**
	 * Reset the shared REST server and current user.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$GLOBALS['wp_rest_server'] = null;
		unset( $_GET['almgr_warehouse'] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Create a published available asset.
	 *
	 * @param string $title     Post title.
	 * @param string $warehouse Warehouse term slug, empty for none.
	 * @return int
	 */
	private function create_asset( string $title, string $warehouse ): int {
		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);

		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		if ( '' !== $warehouse ) {
			wp_set_object_terms( $asset_id, $warehouse, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		}

		return $asset_id;
	}

	/**
	 * Call GET /almgr/v1/assets through the REST server.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Response
	 */
	private function rest_assets( array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', '/almgr/v1/assets' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * Asset IDs returned by the REST catalog for the fixture assets only.
	 *
	 * @param array $params Request parameters.
	 * @return int[]
	 */
	private function rest_ids( array $params ): array {
		$response = $this->rest_assets( $params );
		$this->assertFalse( $response->is_error(), 'REST returned an error.' );

		return $this->only_fixture_ids( wp_list_pluck( $response->get_data()['data'], 'id' ) );
	}

	/**
	 * Asset IDs returned by the list-assets Ability for the fixture assets only.
	 *
	 * @param array $input Ability input.
	 * @return int[]
	 */
	private function ability_ids( array $input ): array {
		$result = $this->call_ability( $input );
		$this->assertIsArray( $result );

		return $this->only_fixture_ids( wp_list_pluck( $result['data'], 'id' ) );
	}

	/**
	 * Execute the list-assets Ability, skipping when the API is unavailable.
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	private function call_ability( array $input ) {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available in this test environment.' );
		}

		return wp_get_ability( 'almgr/list-assets' )->execute( $input );
	}

	/**
	 * Asset titles shown by the frontend catalog for the current query string.
	 *
	 * @return string
	 */
	private function frontend_html(): string {
		$settings      = new ALMGR_Settings_Manager();
		$loan_manager  = new ALMGR_Loan_Manager( $settings );
		$asset_reads   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history = new ALMGR_Asset_History_Service( $loan_manager );
		$member_assets = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );
		$frontend      = new ALMGR_Frontend_Manager( $settings, $asset_reads, $asset_details, $asset_history, $member_assets );

		return $frontend->shortcode_asset_list( array() );
	}

	/**
	 * Map fixture titles shown in the frontend HTML to their asset IDs.
	 *
	 * @param string $html Frontend catalog HTML.
	 * @return int[]
	 */
	private function frontend_ids( string $html ): array {
		$ids   = array();
		$names = array(
			'WH Alpha' => $this->main_asset_id,
			'WH Beta'  => $this->secondary_asset_id,
			'WH Gamma' => $this->unassigned_asset_id,
		);
		foreach ( $names as $title => $asset_id ) {
			if ( false !== strpos( $html, $title ) ) {
				$ids[] = $asset_id;
			}
		}
		sort( $ids );

		return $ids;
	}

	/**
	 * Keep only the fixture asset IDs, sorted.
	 *
	 * @param array $ids Asset IDs.
	 * @return int[]
	 */
	private function only_fixture_ids( array $ids ): array {
		$fixture = array( $this->main_asset_id, $this->secondary_asset_id, $this->unassigned_asset_id );
		$ids     = array_values( array_intersect( array_map( 'intval', $ids ), $fixture ) );
		sort( $ids );

		return $ids;
	}

	/**
	 * The slug filter returns the same assets in all three channels.
	 *
	 * @return void
	 */
	public function test_slug_filter_has_parity_across_channels(): void {
		$expected = array( $this->main_asset_id );

		$_GET['almgr_warehouse'] = 'main-warehouse';
		$frontend                = $this->frontend_ids( $this->frontend_html() );

		$this->assertSame( $expected, $this->rest_ids( array( 'warehouse' => 'main-warehouse' ) ) );
		$this->assertSame( $expected, $this->ability_ids( array( 'warehouse' => 'main-warehouse' ) ) );
		$this->assertSame( $expected, $frontend );
	}

	/**
	 * The "Not assigned" filter returns the same assets in all three channels.
	 *
	 * @return void
	 */
	public function test_unassigned_filter_has_parity_across_channels(): void {
		$expected = array( $this->unassigned_asset_id );

		$_GET['almgr_warehouse'] = ALMGR_Asset_Manager::WAREHOUSE_UNASSIGNED_OPTION;
		$frontend                = $this->frontend_ids( $this->frontend_html() );

		$this->assertSame( $expected, $this->rest_ids( array( 'warehouse_unassigned' => true ) ) );
		$this->assertSame( $expected, $this->rest_ids( array( 'warehouse_unassigned' => 'true' ) ) );
		$this->assertSame( $expected, $this->ability_ids( array( 'warehouse_unassigned' => true ) ) );
		$this->assertSame( $expected, $frontend );
	}

	/**
	 * A false `warehouse_unassigned` is the same as not filtering.
	 *
	 * @return void
	 */
	public function test_unassigned_false_does_not_filter(): void {
		$all = array( $this->main_asset_id, $this->secondary_asset_id, $this->unassigned_asset_id );
		sort( $all );

		$this->assertSame( $all, $this->rest_ids( array( 'warehouse_unassigned' => false ) ) );
		$this->assertSame( $all, $this->ability_ids( array( 'warehouse_unassigned' => false ) ) );
	}

	/**
	 * With warehouse management off, both filters fail with the same error everywhere.
	 *
	 * @return void
	 */
	public function test_filters_are_rejected_when_warehouse_is_disabled(): void {
		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', false );

		foreach ( array( array( 'warehouse' => 'main-warehouse' ), array( 'warehouse_unassigned' => true ) ) as $params ) {
			$response = $this->rest_assets( $params );
			$this->assertTrue( $response->is_error() );
			$this->assertSame( 'almgr_warehouse_disabled', $response->as_error()->get_error_code() );
			$this->assertSame( 400, $response->get_status() );

			$ability = $this->call_ability( $params );
			$this->assertWPError( $ability );
			$this->assertSame( 'almgr_warehouse_disabled', $ability->get_error_code() );
		}

		$service = ( new ALMGR_Asset_Query_Service() )->get_assets( array( 'warehouse' => 'main-warehouse' ) );
		$this->assertWPError( $service );
		$this->assertSame( 'almgr_warehouse_disabled', $service->get_error_code() );
	}

	/**
	 * With warehouse management off, a catalog request without warehouse filters still works.
	 *
	 * @return void
	 */
	public function test_catalog_without_warehouse_filter_works_when_warehouse_is_disabled(): void {
		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', false );

		$this->assertContains( $this->main_asset_id, $this->rest_ids( array() ) );
	}

	/**
	 * An unknown warehouse slug is an error, not an empty or unfiltered list.
	 *
	 * @return void
	 */
	public function test_unknown_warehouse_slug_is_rejected(): void {
		$response = $this->rest_assets( array( 'warehouse' => 'no-such-warehouse' ) );
		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'almgr_warehouse_not_found', $response->as_error()->get_error_code() );
		$this->assertSame( 400, $response->get_status() );

		$ability = $this->call_ability( array( 'warehouse' => 'no-such-warehouse' ) );
		$this->assertWPError( $ability );
		$this->assertSame( 'almgr_warehouse_not_found', $ability->get_error_code() );
	}

	/**
	 * A slug that sanitizes to nothing is also rejected.
	 *
	 * @return void
	 */
	public function test_warehouse_slug_that_sanitizes_to_empty_is_rejected(): void {
		$result = ( new ALMGR_Asset_Query_Service() )->get_assets( array( 'warehouse' => '!!!' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'almgr_warehouse_not_found', $result->get_error_code() );
	}

	/**
	 * Combining the two warehouse filters is a contradictory request.
	 *
	 * @return void
	 */
	public function test_combined_warehouse_filters_are_rejected(): void {
		$params = array(
			'warehouse'            => 'main-warehouse',
			'warehouse_unassigned' => true,
		);

		$response = $this->rest_assets( $params );
		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'almgr_invalid_warehouse_filter', $response->as_error()->get_error_code() );
		$this->assertSame( 400, $response->get_status() );

		$ability = $this->call_ability( $params );
		$this->assertWPError( $ability );
		$this->assertSame( 'almgr_invalid_warehouse_filter', $ability->get_error_code() );
	}

	/**
	 * Anonymous REST callers cannot reach the catalog, so they cannot probe warehouses.
	 *
	 * @return void
	 */
	public function test_anonymous_rest_caller_cannot_filter_by_warehouse(): void {
		wp_set_current_user( 0 );

		$response = $this->rest_assets( array( 'warehouse' => 'main-warehouse' ) );

		$this->assertTrue( $response->is_error() );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Both API adapters derive the new parameters from the shared definition.
	 *
	 * @return void
	 */
	public function test_api_adapters_expose_the_shared_filter_definition(): void {
		$routes = rest_get_server()->get_routes();
		$args   = $routes['/almgr/v1/assets'][0]['args'];

		foreach ( array_keys( ALMGR_Asset_Query_Service::get_api_filter_definitions() ) as $key ) {
			$this->assertArrayHasKey( $key, $args, "REST is missing the {$key} argument." );
		}
		$this->assertSame( 'boolean', $args['warehouse_unassigned']['type'] );

		if ( function_exists( 'wp_get_ability' ) ) {
			$properties = wp_get_ability( 'almgr/list-assets' )->get_input_schema()['properties'];
			foreach ( array_keys( ALMGR_Asset_Query_Service::get_api_filter_definitions() ) as $key ) {
				$this->assertArrayHasKey( $key, $properties, "The Ability is missing the {$key} input." );
			}
			$this->assertSame( 'boolean', $properties['warehouse_unassigned']['type'] );
		}
	}

	/**
	 * The frontend offers "Not assigned" to members and hides the filter when warehouse is off.
	 *
	 * @return void
	 */
	public function test_frontend_filter_row_follows_warehouse_visibility(): void {
		$html = $this->frontend_html();
		$this->assertStringContainsString( 'almgr_filter_warehouse', $html );
		$this->assertStringContainsString( ALMGR_Asset_Manager::WAREHOUSE_UNASSIGNED_OPTION, $html );

		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', false );
		$html = $this->frontend_html();
		$this->assertStringNotContainsString( 'almgr_filter_warehouse', $html );

		( new ALMGR_Settings_Manager() )->set( 'warehouse.enabled', true );
		wp_set_current_user( 0 );
		$html = $this->frontend_html();
		$this->assertStringNotContainsString( 'almgr_filter_warehouse', $html );
	}
}
