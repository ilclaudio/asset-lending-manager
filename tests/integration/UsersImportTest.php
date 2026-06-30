<?php
/**
 * Integration tests for CSV users import.
 *
 * Tests invoke process_users_csv_import() directly via reflection so that the
 * file-upload layer (nonces, $_FILES) is bypassed while the full DB path
 * (wp_create_user, role assignment) runs against the real test database.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the CSV users import pipeline.
 */
class ALMGR_Users_Import_Integration_Test extends WP_UnitTestCase {

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
		$path               = sys_get_temp_dir() . '/almgr_users_import_test_' . uniqid( '', true ) . '.csv';
		file_put_contents( $path, $content );
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Invoke process_users_csv_import() via reflection.
	 *
	 * @param string $csv         CSV string (with header).
	 * @param string $import_mode 'create_only' or 'update'.
	 * @param string $run_mode    'live' or 'dry_run'.
	 * @return array Import report.
	 */
	private function run_import( string $csv, string $import_mode = 'create_only', string $run_mode = 'live' ): array {
		$path   = $this->write_csv( $csv );
		$method = new ReflectionMethod( ALMGR_Tools_Manager::class, 'process_users_csv_import' );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->tools, array( $path, basename( $path ), $import_mode, $run_mode ) );
	}

	/**
	 * Two valid rows (member + operator) → two WP users created with correct plugin roles.
	 *
	 * @return void
	 */
	public function test_import_creates_users_with_correct_plugin_roles(): void {
		$csv = "Username;Email;First_Name;Last_Name;Role\n" .
			"mario_t1;mario.t1.import@example.test;Mario;Rossi;member\n" .
			"luigi_t1;luigi.t1.import@example.test;Luigi;Verdi;operator\n";

		$report = $this->run_import( $csv );

		$this->assertSame( 2, $report['counts']['created'], 'Expected 2 users created.' );
		$this->assertEmpty( $report['errors'], 'Expected no import errors.' );

		$mario = get_user_by( 'email', 'mario.t1.import@example.test' );
		$this->assertInstanceOf( WP_User::class, $mario, 'Mario was not created.' );
		$this->assertContains( ALMGR_MEMBER_ROLE, (array) $mario->roles, 'Mario should have member role.' );

		$luigi = get_user_by( 'email', 'luigi.t1.import@example.test' );
		$this->assertInstanceOf( WP_User::class, $luigi, 'Luigi was not created.' );
		$this->assertContains( ALMGR_OPERATOR_ROLE, (array) $luigi->roles, 'Luigi should have operator role.' );
	}

	/**
	 * Dry-run mode reports one "created" entry but writes nothing to the DB.
	 *
	 * @return void
	 */
	public function test_import_dry_run_does_not_persist_users(): void {
		$csv = "Username;Email;First_Name;Last_Name;Role\n" .
			"dry_t1;dry.t1.import@example.test;Dry;User;member\n";

		$report = $this->run_import( $csv, 'create_only', 'dry_run' );

		$this->assertSame( 1, $report['counts']['created'], 'Dry-run should count one "would create".' );
		$this->assertFalse( get_user_by( 'email', 'dry.t1.import@example.test' ), 'No user should exist in DB after dry-run.' );
	}

	/**
	 * Two rows sharing the same email → first created, second skipped with a skipped log entry.
	 *
	 * @return void
	 */
	public function test_import_skips_duplicate_email_in_csv(): void {
		$csv = "Username;Email;First_Name;Last_Name;Role\n" .
			"user_a_t1;dup.t1.import@example.test;User;A;member\n" .
			"user_b_t1;dup.t1.import@example.test;User;B;member\n";

		$report = $this->run_import( $csv );

		$this->assertSame( 1, $report['counts']['created'], 'Only the first user should be created.' );
		$this->assertSame( 1, $report['counts']['skipped'], 'The duplicate-email row should be skipped.' );
		$this->assertEmpty( $report['errors'], 'Skipped duplicates should not produce error entries.' );
	}
}
