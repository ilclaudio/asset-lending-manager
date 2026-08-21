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
		$this->rest_manager = new ALMGR_REST_Manager( $settings, $loan_manager, new ALMGR_Asset_Query_Service() );
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
}
