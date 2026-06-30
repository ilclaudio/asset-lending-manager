<?php
/**
 * Integration tests for CSV assets import.
 *
 * Tests invoke process_assets_csv_import() directly via reflection so that the
 * file-upload layer (nonces, $_FILES) is bypassed while the full DB path
 * (wp_insert_post, wp_set_object_terms, ACF kit-component linking) runs
 * against the real test database.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the CSV assets import pipeline.
 */
class ALMGR_Assets_Import_Integration_Test extends WP_UnitTestCase {

	/**
	 * Tools manager under test.
	 *
	 * @var ALMGR_Tools_Manager
	 */
	private ALMGR_Tools_Manager $tools;

	/**
	 * Temp CSV files created during the test; cleaned up in tearDown.
	 *
	 * @var string[]
	 */
	private array $temp_files = array();

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
	 * Delete any temp CSV files written during the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
		$this->temp_files = array();
		parent::tearDown();
	}

	/**
	 * Write CSV content to a temp file and register it for cleanup.
	 *
	 * @param string $content CSV content.
	 * @return string Absolute path to the temp file.
	 */
	private function write_csv( string $content ): string {
		$path               = sys_get_temp_dir() . '/almgr_assets_import_test_' . uniqid( '', true ) . '.csv';
		file_put_contents( $path, $content );
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Standard 11-column CSV header for assets import.
	 *
	 * @return string
	 */
	private function header(): string {
		return "Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles\n";
	}

	/**
	 * Invoke process_assets_csv_import() via reflection.
	 *
	 * @param string $csv         CSV string (with header).
	 * @param string $import_mode 'create_only' or 'update'.
	 * @param string $run_mode    'live' or 'dry_run'.
	 * @return array Import report.
	 */
	private function run_import( string $csv, string $import_mode = 'create_only', string $run_mode = 'live' ): array {
		$path   = $this->write_csv( $csv );
		$method = new ReflectionMethod( ALMGR_Tools_Manager::class, 'process_assets_csv_import' );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->tools, array( $path, basename( $path ), $import_mode, $run_mode ) );
	}

	/**
	 * Find all published ALM asset posts in the DB.
	 *
	 * @return WP_Post[]
	 */
	private function get_all_asset_posts(): array {
		return get_posts(
			array(
				'post_type'      => ALMGR_ASSET_CPT_SLUG,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Two valid component rows → two posts created, each with the correct
	 * structure, type, state, and level taxonomy terms.
	 *
	 * @return void
	 */
	public function test_import_creates_components_with_correct_taxonomy_terms(): void {
		$csv = $this->header() .
			"Dobson 200 TI;component;telescope;available;basic;;Test telescope;Orion;D200;publish;\n" .
			"EQ6 Mount TI;component;mount;maintenance;intermediate;;Test mount;Sky-Watcher;EQ6;publish;\n";

		$report = $this->run_import( $csv );

		$this->assertSame( 2, $report['counts']['created'], 'Expected 2 assets created.' );
		$this->assertEmpty( $report['errors'], 'Expected no import errors.' );

		$posts = $this->get_all_asset_posts();
		$this->assertCount( 2, $posts, 'Expected exactly 2 posts in the DB.' );

		$dobson = null;
		foreach ( $posts as $post ) {
			if ( 'Dobson 200 TI' === $post->post_title ) {
				$dobson = $post;
				break;
			}
		}
		$this->assertNotNull( $dobson, 'Dobson 200 TI post was not found.' );
		$this->assertTrue( has_term( ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, $dobson->ID ), 'Dobson should have component structure term.' );
		$this->assertTrue( has_term( 'telescope', ALMGR_ASSET_TYPE_TAXONOMY_SLUG, $dobson->ID ), 'Dobson should have telescope type term.' );
		$this->assertTrue( has_term( 'available', ALMGR_ASSET_STATE_TAXONOMY_SLUG, $dobson->ID ), 'Dobson should have available state term.' );
		$this->assertTrue( has_term( 'basic', ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, $dobson->ID ), 'Dobson should have basic level term.' );
	}

	/**
	 * Two components + one kit referencing them by CSV title → kit post is
	 * created with the kit structure term and its almgr_components meta
	 * contains exactly the two component post IDs.
	 *
	 * The ACF get_field/update_field shims in bootstrap-integration.php map
	 * to get_post_meta/update_post_meta, so reading almgr_components via
	 * get_post_meta() is correct in the test environment.
	 *
	 * @return void
	 */
	public function test_import_creates_kit_and_links_components_by_csv_title(): void {
		$csv = $this->header() .
			"Refractor 80 KL;component;refractor;available;basic;;A refractor;Vixen;R80;publish;\n" .
			"Tripod Pro KL;component;tripod;available;basic;;A tripod;Berlebach;T100;publish;\n" .
			"Observation Kit KL;kit;telescope;available;basic;;A complete kit;Various;KIT001;publish;Refractor 80 KL|Tripod Pro KL\n";

		$report = $this->run_import( $csv );

		$this->assertSame( 3, $report['counts']['created'], 'Expected 3 assets created (2 components + 1 kit).' );
		$this->assertEmpty( $report['errors'], 'Expected no import errors.' );

		$posts = $this->get_all_asset_posts();
		$this->assertCount( 3, $posts, 'Expected exactly 3 posts in the DB.' );

		$kit_post    = null;
		$comp_ids    = array();
		foreach ( $posts as $post ) {
			if ( 'Observation Kit KL' === $post->post_title ) {
				$kit_post = $post;
			} elseif ( in_array( $post->post_title, array( 'Refractor 80 KL', 'Tripod Pro KL' ), true ) ) {
				$comp_ids[] = $post->ID;
			}
		}

		$this->assertNotNull( $kit_post, 'Kit post was not found.' );
		$this->assertCount( 2, $comp_ids, 'Both component posts should be in the DB.' );
		$this->assertTrue( has_term( ALMGR_ASSET_KIT_SLUG, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, $kit_post->ID ), 'Kit should have the kit structure term.' );

		$linked_ids = get_post_meta( $kit_post->ID, 'almgr_components', true );
		$this->assertIsArray( $linked_ids, 'almgr_components meta should be an array.' );
		$this->assertCount( 2, $linked_ids, 'Kit should link exactly 2 component IDs.' );

		foreach ( $comp_ids as $expected_id ) {
			$this->assertContains( $expected_id, $linked_ids, "Component ID {$expected_id} should be in kit component list." );
		}
	}

	/**
	 * Dry-run mode reports each row as "would create" but no posts are
	 * written to the database.
	 *
	 * @return void
	 */
	public function test_import_dry_run_creates_no_posts(): void {
		$csv = $this->header() .
			"Dry Scope TI;component;telescope;available;basic;;A scope;Orion;DS001;publish;\n" .
			"Dry Mount TI;component;mount;available;basic;;A mount;Vixen;DM001;publish;\n";

		$report = $this->run_import( $csv, 'create_only', 'dry_run' );

		$this->assertSame( 2, $report['counts']['created'], 'Dry-run should count 2 "would create" entries.' );
		$this->assertCount( 0, $this->get_all_asset_posts(), 'No posts should be in the DB after dry-run.' );
	}
}
