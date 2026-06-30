<?php
/**
 * Integration tests for the assets CSV export helpers.
 *
 * stream_assets_csv_export() cannot be invoked directly in tests because it
 * writes to php://output and calls exit(). Instead, the two private helpers
 * that contain the DB-dependent logic are tested via reflection:
 *
 *   - get_assets_export_term_slug()            reads wp_get_post_terms() from the real DB
 *   - get_assets_export_kit_component_titles() reads almgr_components via the ACF shim
 *                                              and fetches post titles for each component
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the DB-dependent assets export helper methods.
 *
 * Extends WP_UnitTestCase (not ALMGR_Loan_Integration_Test_Case) to avoid
 * sharing the static boot flag with LoanWorkflowAjaxTest, which would prevent
 * that class from re-running create_default_terms() and cause its tests to fail.
 */
class ALMGR_Assets_Export_Integration_Test extends WP_UnitTestCase {

	/**
	 * Tools manager instance shared across tests in this class.
	 *
	 * @var ALMGR_Tools_Manager
	 */
	private ALMGR_Tools_Manager $tools;

	/**
	 * Guard: plugin state is booted only once per process.
	 *
	 * @var bool
	 */
	private static bool $plugin_booted = false;

	/**
	 * Set up before the first test in this class, outside any DB transaction.
	 * Registers CPT, taxonomies, roles, and default terms once.
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
	 * Set up a fresh tools manager before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tools = new ALMGR_Tools_Manager();
	}

	/**
	 * Create a published ALM asset post and assign the given taxonomy terms.
	 *
	 * @param array $args Overrides: post_title, structure, type, state, level.
	 * @return int Post ID.
	 */
	private function create_test_asset( array $args = array() ): int {
		$defaults = array(
			'post_title' => 'Test Asset Export ' . uniqid( '', true ),
			'structure'  => ALMGR_ASSET_COMPONENT_SLUG,
			'type'       => 'generic',
			'state'      => 'available',
			'level'      => 'basic',
		);
		$args     = array_merge( $defaults, $args );

		$asset_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
				'post_title'  => $args['post_title'],
			)
		);

		wp_set_object_terms( $asset_id, $args['structure'], ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['type'], ALMGR_ASSET_TYPE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['state'], ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
		wp_set_object_terms( $asset_id, $args['level'], ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, false );
		update_post_meta( $asset_id, '_almgr_current_owner', 0 );

		return $asset_id;
	}

	/**
	 * Invoke a private export helper via reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $args        Arguments to pass.
	 * @return mixed
	 */
	private function call_export_helper( string $method_name, array $args ) {
		$method = new ReflectionMethod( ALMGR_Tools_Manager::class, $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->tools, $args );
	}

	/**
	 * Taxonomy slugs assigned at post creation are returned verbatim by
	 * get_assets_export_term_slug() for structure, state, type, and level.
	 *
	 * @return void
	 */
	public function test_export_term_slug_returns_assigned_taxonomy_slug(): void {
		$asset_id = $this->create_test_asset(
			array(
				'structure' => ALMGR_ASSET_KIT_SLUG,
				'state'     => 'maintenance',
				'type'      => 'telescope',
				'level'     => 'advanced',
			)
		);

		$this->assertSame(
			ALMGR_ASSET_KIT_SLUG,
			$this->call_export_helper( 'get_assets_export_term_slug', array( $asset_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG ) ),
			'Structure slug should match the assigned term.'
		);
		$this->assertSame(
			'maintenance',
			$this->call_export_helper( 'get_assets_export_term_slug', array( $asset_id, ALMGR_ASSET_STATE_TAXONOMY_SLUG ) ),
			'State slug should match the assigned term.'
		);
		$this->assertSame(
			'telescope',
			$this->call_export_helper( 'get_assets_export_term_slug', array( $asset_id, ALMGR_ASSET_TYPE_TAXONOMY_SLUG ) ),
			'Type slug should match the assigned term.'
		);
		$this->assertSame(
			'advanced',
			$this->call_export_helper( 'get_assets_export_term_slug', array( $asset_id, ALMGR_ASSET_LEVEL_TAXONOMY_SLUG ) ),
			'Level slug should match the assigned term.'
		);
	}

	/**
	 * A post with no taxonomy term assigned returns an empty string.
	 *
	 * @return void
	 */
	public function test_export_term_slug_returns_empty_string_when_no_term_assigned(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => 'publish',
			)
		);

		$result = $this->call_export_helper( 'get_assets_export_term_slug', array( $post_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG ) );

		$this->assertSame( '', $result, 'No assigned term should produce an empty string.' );
	}

	/**
	 * A kit with two linked components returns their titles pipe-separated in
	 * the same order they were stored in the almgr_components meta field.
	 *
	 * almgr_components is stored via update_post_meta() because the update_field()
	 * shim in bootstrap-integration.php maps directly to update_post_meta().
	 *
	 * @return void
	 */
	public function test_export_kit_component_titles_returns_pipe_separated_titles(): void {
		$comp1_id = $this->create_test_asset( array( 'post_title' => 'Refractor 80 EX' ) );
		$comp2_id = $this->create_test_asset( array( 'post_title' => 'EQ6 Mount EX' ) );
		$kit_id   = $this->create_test_asset(
			array(
				'post_title' => 'Observation Kit EX',
				'structure'  => ALMGR_ASSET_KIT_SLUG,
			)
		);

		update_post_meta( $kit_id, 'almgr_components', array( $comp1_id, $comp2_id ) );

		$result = $this->call_export_helper( 'get_assets_export_kit_component_titles', array( $kit_id ) );

		$this->assertSame( 'Refractor 80 EX|EQ6 Mount EX', $result, 'Component titles should be pipe-separated in storage order.' );
	}

	/**
	 * A hard-deleted component post (wp_delete_post with force=true) is silently
	 * skipped because get_post() returns null; only titles of existing posts appear.
	 *
	 * Note: wp_trash_post() does NOT cause a skip because get_post() returns the
	 * post regardless of status. Only a force delete makes get_post() return null.
	 *
	 * @return void
	 */
	public function test_export_kit_component_titles_skips_hard_deleted_component_posts(): void {
		$live_id    = $this->create_test_asset( array( 'post_title' => 'Live Scope EX' ) );
		$deleted_id = $this->create_test_asset( array( 'post_title' => 'Deleted Mount EX' ) );
		$kit_id     = $this->create_test_asset(
			array(
				'post_title' => 'Kit With Deleted EX',
				'structure'  => ALMGR_ASSET_KIT_SLUG,
			)
		);

		update_post_meta( $kit_id, 'almgr_components', array( $live_id, $deleted_id ) );
		wp_delete_post( $deleted_id, true );

		$result = $this->call_export_helper( 'get_assets_export_kit_component_titles', array( $kit_id ) );

		$this->assertSame( 'Live Scope EX', $result, 'Hard-deleted post title should not appear in export output.' );
	}
}
