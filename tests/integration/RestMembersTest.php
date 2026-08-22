<?php
/**
 * Integration tests for the REST members endpoint loan counts.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for ALMGR REST members responses.
 */
class ALMGR_REST_Members_Integration_Test extends WP_UnitTestCase {

	/**
	 * REST manager instance shared across tests in this class.
	 *
	 * @var ALMGR_REST_Manager
	 */
	private ALMGR_REST_Manager $rest_manager;

	/**
	 * Guard: plugin state is booted only once per process.
	 *
	 * @var bool
	 */
	private static bool $plugin_booted = false;

	/**
	 * Set up before the first test in this class, outside any DB transaction.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( self::$plugin_booted ) {
			return;
		}

		$settings = new ALMGR_Settings_Manager();
		( new ALMGR_Role_Manager() )->activate();

		$asset_manager = new ALMGR_Asset_Manager();
		$asset_manager->register_post_type();
		$asset_manager->register_taxonomies();

		ALMGR_Installer::create_default_terms();

		( new ALMGR_Loan_Manager( $settings ) )->activate();

		self::$plugin_booted = true;
	}

	/**
	 * Set up a fresh REST manager before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$settings           = new ALMGR_Settings_Manager();
		$loan_manager       = new ALMGR_Loan_Manager( $settings );
		$asset_queries      = new ALMGR_Asset_Query_Service();
		$asset_reads       = new ALMGR_Asset_Read_Service( $asset_queries );
		$asset_details     = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history     = new ALMGR_Asset_History_Service( $loan_manager );
		$member_assets     = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );
		$this->rest_manager = new ALMGR_REST_Manager( $settings, $asset_reads, $asset_details, $asset_history, $member_assets );
	}

	/**
	 * Create a published asset assigned to a specific user.
	 *
	 * @param int    $owner_id    Current owner user ID.
	 * @param string $post_status Post status.
	 * @return int
	 */
	private function create_assigned_asset( int $owner_id, string $post_status = 'publish' ): int {
		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => $post_status,
				'post_title'  => 'REST Count Asset ' . wp_generate_password( 6, false ),
			)
		);

		wp_set_object_terms( $asset_id, ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'generic', ALMGR_ASSET_TYPE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'on-loan', ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, 'basic', ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, false );
		update_post_meta( $asset_id, '_almgr_current_owner', $owner_id );

		return $asset_id;
	}

	/**
	 * Index REST member rows by user ID for stable assertions.
	 *
	 * @param array $rows REST member rows.
	 * @return array<int,array>
	 */
	private function index_members_by_id( array $rows ): array {
		$indexed = array();

		foreach ( $rows as $row ) {
			$indexed[ (int) $row['id'] ] = $row;
		}

		return $indexed;
	}

	/**
	 * The /members endpoint returns active_loans_count values that match the
	 * number of published assets currently assigned to each returned user.
	 *
	 * @return void
	 */
	public function test_get_members_returns_correct_active_loan_counts(): void {
		$member_one_id = self::factory()->user->create(
			array(
				'role'         => ALMGR_MEMBER_ROLE,
				'user_login'   => 'member_one',
				'display_name' => 'Member One',
			)
		);
		$member_two_id = self::factory()->user->create(
			array(
				'role'         => ALMGR_MEMBER_ROLE,
				'user_login'   => 'member_two',
				'display_name' => 'Member Two',
			)
		);
		$operator_id   = self::factory()->user->create(
			array(
				'role'         => ALMGR_OPERATOR_ROLE,
				'user_login'   => 'operator_zero',
				'display_name' => 'Operator Zero',
			)
		);

		$this->create_assigned_asset( $member_one_id );
		$this->create_assigned_asset( $member_one_id );
		$this->create_assigned_asset( $member_two_id );
		$this->create_assigned_asset( $member_one_id, 'draft' );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/members' );
		$request->set_param( 'per_page', 10 );

		$response = $this->rest_manager->get_members( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );

		$members = $this->index_members_by_id( $data['data'] );

		$this->assertArrayHasKey( $member_one_id, $members, 'First member should be present in /members response.' );
		$this->assertArrayHasKey( $member_two_id, $members, 'Second member should be present in /members response.' );
		$this->assertArrayHasKey( $operator_id, $members, 'Operator should be present in /members response.' );

		$this->assertSame( 2, (int) $members[ $member_one_id ]['active_loans_count'], 'Published assigned assets should be counted for member one.' );
		$this->assertSame( 1, (int) $members[ $member_two_id ]['active_loans_count'], 'Published assigned assets should be counted for member two.' );
		$this->assertSame( 0, (int) $members[ $operator_id ]['active_loans_count'], 'Users without assigned published assets should return zero active loans.' );
	}

	/**
	 * The current-member endpoint returns only assets held by the authenticated member.
	 *
	 * @return void
	 */
	public function test_get_my_assets_returns_only_assets_held_by_current_member(): void {
		$member_id       = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$other_member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$held_asset_id   = $this->create_assigned_asset( $member_id );
		$this->create_assigned_asset( $other_member_id );

		wp_set_current_user( $member_id );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/me/assets' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 20 );

		$response = $this->rest_manager->get_my_assets( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();

		$this->assertSame( $member_id, (int) $data['member_id'] );
		$this->assertSame( 1, (int) $data['total'] );
		$this->assertCount( 1, $data['data'] );
		$this->assertSame( $held_asset_id, (int) $data['data'][0]['id'] );
	}

	/**
	 * A member may not use the member endpoint to inspect another member's assets.
	 *
	 * @return void
	 */
	public function test_get_member_assets_denies_member_access_to_another_member(): void {
		$member_id       = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$other_member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$this->create_assigned_asset( $other_member_id );

		wp_set_current_user( $member_id );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/members/' . $other_member_id . '/assets' );
		$request->set_param( 'member_id', $other_member_id );

		$response = $this->rest_manager->get_member_assets( $request );

		$this->assertWPError( $response );
		$this->assertSame( 'almgr_member_assets_forbidden', $response->get_error_code() );
	}

	/**
	 * An operator may inspect assets held by another member through the shared service.
	 *
	 * @return void
	 */
	public function test_get_member_assets_allows_operator_access(): void {
		$operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$member_id   = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id    = $this->create_assigned_asset( $member_id );

		wp_set_current_user( $operator_id );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/members/' . $member_id . '/assets' );
		$request->set_param( 'member_id', $member_id );

		$response = $this->rest_manager->get_member_assets( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();

		$this->assertSame( $member_id, (int) $data['member_id'] );
		$this->assertSame( 1, (int) $data['total'] );
		$this->assertSame( $asset_id, (int) $data['data'][0]['id'] );
	}

	/**
	 * The REST catalog adapter must consume the same filtered result as the
	 * shared asset query service, including pagination metadata.
	 *
	 * @return void
	 */
	public function test_rest_catalog_matches_shared_asset_query_result(): void {
		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );

		$this->create_assigned_asset( $member_id );
		$this->create_assigned_asset( $member_id );
		$this->create_assigned_asset( $member_id );
		$this->create_assigned_asset( $member_id, 'draft' );

		$asset_queries = new ALMGR_Asset_Query_Service();
		$result        = $asset_queries->get_assets(
			array(
				'owner'    => $member_id,
				'state'    => 'on-loan',
				'page'     => 1,
				'per_page' => 2,
			)
		);

		$request = new WP_REST_Request( 'GET', '/almgr/v1/assets' );
		$request->set_param( 'owner', $member_id );
		$request->set_param( 'state', 'on-loan' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );

		$response = $this->rest_manager->get_assets( $request );
		$data     = $response->get_data();
		$rest_ids = array_map(
			static function ( $asset ) {
				return (int) $asset['id'];
			},
			$data['data']
		);

		$this->assertInstanceOf( ALMGR_Asset_Query_Result::class, $result );
		$this->assertSame( $result->get_items(), $rest_ids );
		$this->assertSame( 3, $result->get_total() );
		$this->assertSame( 2, $result->get_pages() );
		$this->assertSame( $result->get_total(), (int) $data['total'] );
		$this->assertSame( $result->get_pages(), (int) $data['pages'] );
	}

	/**
	 * The REST detail adapter must project the same canonical detail record used
	 * by the frontend detail template.
	 *
	 * @return void
	 */
	public function test_rest_asset_detail_matches_shared_detail_record(): void {
		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$asset_id  = $this->create_assigned_asset( $member_id );

		$asset_reads   = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details = new ALMGR_Asset_Detail_Service( $asset_reads );
		$shared        = $asset_details->get_asset_detail( $asset_id );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/assets/' . $asset_id );
		$request->set_param( 'id', $asset_id );
		$response = $this->rest_manager->get_asset( $request );
		$data     = $response->get_data();

		$this->assertSame( (int) $shared->id, (int) $data['id'] );
		$this->assertSame( (string) $shared->code, $data['code'] );
		$this->assertSame( (string) $shared->title, $data['title'] );
		$this->assertSame( $shared->structure_slugs, $data['structure'] );
		$this->assertSame( $shared->type_slugs, $data['type'] );
		$this->assertSame( $shared->almgr_state_slugs, $data['state'] );
		$this->assertSame( $shared->level_slugs, $data['level'] );
		$this->assertSame( (int) $shared->owner_id, (int) $data['owner_id'] );
		$this->assertSame( $shared->components, $data['components'] );
	}

	/**
	 * The operator REST detail history uses the same paginated service result
	 * as the frontend history consumers.
	 *
	 * @return void
	 */
	public function test_rest_asset_detail_history_matches_shared_history_result(): void {
		global $wpdb;

		$operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$asset_id    = $this->create_assigned_asset( $operator_id );
		$wpdb->insert(
			$wpdb->prefix . 'almgr_loan_requests_history',
			array(
				'loan_request_id' => 0,
				'asset_id'        => $asset_id,
				'requester_id'    => $operator_id,
				'owner_id'        => 0,
				'status'          => 'direct_assign',
				'message'         => 'History parity test',
				'changed_at'      => current_time( 'mysql' ),
				'changed_by'      => $operator_id,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d' )
		);

		wp_set_current_user( $operator_id );
		$history_service = new ALMGR_Asset_History_Service( new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() ) );
		$shared          = $history_service->get_history( $asset_id, 0, 10, 1 );

		$request = new WP_REST_Request( 'GET', '/almgr/v1/assets/' . $asset_id );
		$request->set_param( 'id', $asset_id );
		$response = $this->rest_manager->get_asset( $request );
		$data     = $response->get_data();

		$this->assertSame( $shared->get_total(), count( $data['history'] ) );
		$this->assertSame( $shared->get_items()[0]->status, $data['history'][0]['status'] );
		$this->assertSame( $shared->get_items()[0]->message, $data['history'][0]['message'] );
	}

	/**
	 * The current-member REST adapter must consume the same result as the
	 * shared member-assets service.
	 *
	 * @return void
	 */
	public function test_my_assets_rest_matches_shared_member_assets_result(): void {
		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );

		$this->create_assigned_asset( $member_id );
		$this->create_assigned_asset( $member_id );

		wp_set_current_user( $member_id );
		$member_assets = new ALMGR_Member_Assets_Service(
			new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() ),
			new ALMGR_Access_Policy()
		);
		$result = $member_assets->get_assets_for_member(
			$member_id,
			$member_id,
			array(
				'page'     => 1,
				'per_page' => 1,
			)
		);

		$request = new WP_REST_Request( 'GET', '/almgr/v1/me/assets' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 1 );

		$response = $this->rest_manager->get_my_assets( $request );
		$data     = $response->get_data();
		$rest_ids = array_map(
			static function ( $asset ) {
				return (int) $asset['id'];
			},
			$data['data']
		);
		$shared_ids = array_map(
			static function ( $asset ) {
				return (int) $asset->id;
			},
			$result->get_items()
		);

		$this->assertInstanceOf( ALMGR_Asset_Read_Result::class, $result );
		$this->assertSame( $shared_ids, $rest_ids );
		$this->assertSame( 2, $result->get_total() );
		$this->assertSame( 2, $result->get_pages() );
		$this->assertSame( $result->get_total(), (int) $data['total'] );
		$this->assertSame( $result->get_pages(), (int) $data['pages'] );
	}
}
