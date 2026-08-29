<?php
/**
 * Integration tests for the optional WordPress Abilities API adapter.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verifies registration, permissions and delegation for read-only abilities.
 */
class ALMGR_Abilities_Integration_Test extends WP_UnitTestCase {

	/**
	 * Abilities adapter under test.
	 *
	 * @var ALMGR_Abilities_Manager
	 */
	private $abilities;

	/**
	 * Set up the plugin services and optional API registrations.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available in this test environment.' );
		}

		$settings = new ALMGR_Settings_Manager();
		( new ALMGR_Role_Manager() )->activate();
		$asset_manager = new ALMGR_Asset_Manager();
		$asset_manager->register_post_type();
		$asset_manager->register_taxonomies();
		ALMGR_Installer::create_default_terms();
		$loan_manager = new ALMGR_Loan_Manager( $settings );
		$loan_manager->activate();

		$asset_reads   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history = new ALMGR_Asset_History_Service( $loan_manager );
		$member_assets = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );
		$this->abilities = new ALMGR_Abilities_Manager( $asset_reads, $asset_details, $asset_history, $member_assets, null, null, $loan_manager );

		if ( ! wp_get_ability( 'almgr/list-assets' ) ) {
			$this->abilities->register();
			do_action( 'wp_abilities_api_categories_init' );
			do_action( 'wp_abilities_api_init' );
		}
	}

	/**
	 * Create a published asset with the standard catalog terms.
	 *
	 * @return int
	 */
	private function create_asset(): int {
		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
				'post_title'  => 'Ability Asset ' . wp_generate_password( 6, false ),
			)
		);

		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'generic', ALMGR_ASSET_TYPE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'basic', ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, false );

		return $asset_id;
	}

	/**
	 * Verify that the read-only abilities are registered and marked
	 * as REST-visible and readonly.
	 *
	 * @return void
	 */
	public function test_read_only_abilities_are_registered_with_safe_metadata(): void {
		foreach ( array( 'almgr/list-assets', 'almgr/get-asset', 'almgr/get-asset-loan-history', 'almgr/list-my-loan-requests', 'almgr/list-asset-loan-requests', 'almgr/get-loan-request' ) as $ability_id ) {
			$ability = wp_get_ability( $ability_id );
			$this->assertNotNull( $ability );
			$this->assertSame( true, $ability->get_meta()['show_in_rest'] );
			$this->assertSame( true, $ability->get_meta()['annotations']['readonly'] );
		}
	}

	/**
	 * Verify list and detail abilities delegate to the shared asset services.
	 *
	 * @return void
	 */
	public function test_asset_abilities_return_shared_asset_data(): void {
		$operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$asset_id    = $this->create_asset();
		wp_set_object_terms( $asset_id, 'main-warehouse', ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG, false );
		wp_set_current_user( $operator_id );

		$list = wp_get_ability( 'almgr/list-assets' )->execute(
			array(
				'page'     => 1,
				'per_page' => 20,
			)
		);
		$detail = wp_get_ability( 'almgr/get-asset' )->execute( array( 'asset_id' => $asset_id ) );

		$this->assertIsArray( $list );
		$this->assertIsArray( $detail );
		$this->assertContains( $asset_id, wp_list_pluck( $list['data'], 'id' ) );
		$this->assertSame( $asset_id, $detail['id'] );
		$this->assertSame( array( 'main-warehouse' ), $detail['warehouse'] );
	}

	/**
	 * Verify history ability applies the shared history visibility/query path.
	 *
	 * @return void
	 */
	public function test_asset_history_ability_returns_shared_history_data(): void {
		global $wpdb;

		$operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$asset_id    = $this->create_asset();
		$wpdb->insert(
			$wpdb->prefix . 'almgr_loan_requests_history',
			array(
				'loan_request_id' => 0,
				'asset_id'        => $asset_id,
				'requester_id'    => $operator_id,
				'owner_id'        => 0,
				'status'          => 'direct_assign',
				'message'         => 'Ability history test',
				'changed_at'      => current_time( 'mysql' ),
				'changed_by'      => $operator_id,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d' )
		);
		wp_set_current_user( $operator_id );

		$result = wp_get_ability( 'almgr/get-asset-loan-history' )->execute( array( 'asset_id' => $asset_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'direct_assign', $result['data'][0]['status'] );
		$this->assertSame( 'Ability history test', $result['data'][0]['message'] );
	}

	/**
	 * Verify current-member ability uses the authenticated actor and shared result.
	 *
	 * @return void
	 */
	public function test_list_my_assets_ability_matches_shared_member_result(): void {
		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id  = $this->create_asset();
		wp_set_current_user( $member_id );
		update_post_meta( $asset_id, '_almgr_current_owner', $member_id );
		wp_set_object_terms( $asset_id, 'on-loan', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );

		$result = wp_get_ability( 'almgr/list-my-assets' )->execute( array( 'page' => 1, 'per_page' => 20 ) );

		$this->assertIsArray( $result );
		$this->assertSame( $member_id, $result['member_id'] );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $asset_id, $result['data'][0]['id'] );
	}

	/**
	 * Verify loan request abilities project the shared request query result.
	 *
	 * @return void
	 */
	public function test_loan_request_abilities_match_shared_query_result(): void {
		global $wpdb;

		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id  = $this->create_asset();
		update_post_meta( $asset_id, '_almgr_current_owner', $member_id );
		$wpdb->insert(
			$wpdb->prefix . 'almgr_loan_requests',
			array(
				'asset_id'        => $asset_id,
				'requester_id'    => $member_id,
				'owner_id'        => 0,
				'request_date'    => current_time( 'mysql' ),
				'request_message' => 'Ability request test',
				'status'          => 'pending',
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		wp_set_current_user( $member_id );
		$query  = new ALMGR_Loan_Request_Query_Service();
		$shared = $query->get_for_user( $member_id );
		$mine   = wp_get_ability( 'almgr/list-my-loan-requests' )->execute();
		$asset  = wp_get_ability( 'almgr/list-asset-loan-requests' )->execute( array( 'asset_id' => $asset_id ) );

		$this->assertSame( count( $shared->get_items() ), count( $mine['data'] ) );
		$this->assertSame( 1, $mine['total'] );
		$this->assertSame( 1, $mine['page'] );
		$this->assertSame( 1, $mine['pages'] );
		$this->assertSame( $asset_id, $asset['data'][0]['asset_id'] );
		$this->assertSame( 1, $asset['total'] );
		$this->assertSame( 'Ability request test', $asset['data'][0]['request_message'] );
	}

	/**
	 * Verify loan request creation uses the authenticated actor and shared workflow.
	 *
	 * @return void
	 */
	public function test_create_loan_request_ability_uses_shared_workflow(): void {
		global $wpdb;

		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id  = $this->create_asset();
		wp_set_current_user( $member_id );

		$ability = wp_get_ability( 'almgr/create-loan-request' );
		$this->assertNotNull( $ability );
		$this->assertSame( false, $ability->get_meta()['annotations']['readonly'] );

		$result = $ability->execute(
			array(
				'asset_id'        => $asset_id,
				'request_message' => 'Ability create request test',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $asset_id, $result['asset_id'] );
		$this->assertSame( 0, $result['owner_id'] );

		$request_row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$wpdb->prefix . 'almgr_loan_requests',
				(int) $result['request_id']
			)
		);

		$this->assertNotNull( $request_row );
		$this->assertSame( 'pending', $request_row->status );
	}

	/**
	 * Verify a requester can read the loan request through the shared policy.
	 *
	 * @return void
	 */
	public function test_get_loan_request_ability_respects_request_visibility(): void {
		global $wpdb;

		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id  = $this->create_asset();
		$wpdb->insert(
			$wpdb->prefix . 'almgr_loan_requests',
			array(
				'asset_id'        => $asset_id,
				'requester_id'    => $member_id,
				'owner_id'        => 0,
				'request_date'    => current_time( 'mysql' ),
				'request_message' => 'Ability detail request test',
				'status'          => 'pending',
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		$request_id = (int) $wpdb->insert_id;
		wp_set_current_user( $member_id );

		$result = wp_get_ability( 'almgr/get-loan-request' )->execute( array( 'loan_request_id' => $request_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( $request_id, $result['id'] );
		$this->assertSame( 'Ability detail request test', $result['request_message'] );
	}
}
