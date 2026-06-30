<?php
/**
 * Integration tests for the users CSV export helper.
 *
 * stream_users_csv_export() cannot be invoked directly in tests because it
 * writes to php://output and calls exit(). The private helper get_users_for_export()
 * contains all DB-dependent logic and is tested via ReflectionMethod.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration tests for the DB-dependent users export helper method.
 *
 * Extends WP_UnitTestCase directly (not ALMGR_Loan_Integration_Test_Case) to avoid
 * the static boot-flag collision that would cause LoanWorkflowAjaxTest to skip
 * re-running create_default_terms() and fail.
 */
class ALMGR_Users_Export_Integration_Test extends WP_UnitTestCase {

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
	 * Invoke the private get_users_for_export() via reflection.
	 *
	 * @return array<int, array<string>>
	 */
	private function run_export(): array {
		$method = new ReflectionMethod( ALMGR_Tools_Manager::class, 'get_users_for_export' );
		$method->setAccessible( true );
		return $method->invoke( $this->tools );
	}

	/**
	 * Users with almgr_member or almgr_operator roles appear in the export;
	 * users with other roles (subscriber, administrator) are excluded.
	 *
	 * @return void
	 */
	public function test_export_returns_rows_for_alm_roles_only(): void {
		$member_id   = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
		$subscriber  = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$rows   = $this->run_export();
		$emails = array_column( $rows, 1 );

		$member_email   = get_userdata( $member_id )->user_email;
		$operator_email = get_userdata( $operator_id )->user_email;
		$sub_email      = get_userdata( $subscriber )->user_email;

		$this->assertContains( $member_email, $emails, 'Member should appear in the export.' );
		$this->assertContains( $operator_email, $emails, 'Operator should appear in the export.' );
		$this->assertNotContains( $sub_email, $emails, 'Subscriber should not appear in the export.' );
		$this->assertCount( 2, $rows, 'Export should contain exactly the two ALM-role users.' );
	}

	/**
	 * All five CSV columns are populated correctly from the WP user record
	 * and the first_name / last_name user meta fields.
	 *
	 * @return void
	 */
	public function test_export_row_contains_correct_user_data(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'       => ALMGR_MEMBER_ROLE,
				'user_login' => 'luigi.bianchi',
				'user_email' => 'luigi.bianchi@example.test',
				'first_name' => 'Luigi',
				'last_name'  => 'Bianchi',
			)
		);

		$rows = $this->run_export();

		$found = null;
		foreach ( $rows as $row ) {
			if ( 'luigi.bianchi@example.test' === $row[1] ) {
				$found = $row;
				break;
			}
		}

		$this->assertNotNull( $found, 'A row for the created user should be present in the export.' );
		$this->assertSame( 'luigi.bianchi', $found[0], 'Column 0 (login) should match.' );
		$this->assertSame( 'luigi.bianchi@example.test', $found[1], 'Column 1 (email) should match.' );
		$this->assertSame( 'Luigi', $found[2], 'Column 2 (first_name) should match.' );
		$this->assertSame( 'Bianchi', $found[3], 'Column 3 (last_name) should match.' );
		$this->assertSame( 'member', $found[4], 'Column 4 (role) should be "member".' );
	}

	/**
	 * User meta values that start with spreadsheet formula characters (= + - @)
	 * are prefixed with a leading apostrophe to prevent formula injection.
	 *
	 * @return void
	 */
	public function test_export_sanitizes_formula_injection_in_user_meta(): void {
		$user_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		update_user_meta( $user_id, 'first_name', '=SUM(A1:A100)' );
		update_user_meta( $user_id, 'last_name', '+Rossi' );

		$rows = $this->run_export();

		$email = get_userdata( $user_id )->user_email;
		$found = null;
		foreach ( $rows as $row ) {
			if ( $email === $row[1] ) {
				$found = $row;
				break;
			}
		}

		$this->assertNotNull( $found, 'Row for the user with injected meta should exist.' );
		$this->assertSame( "'=SUM(A1:A100)", $found[2], 'first_name starting with = must be prefixed with apostrophe.' );
		$this->assertSame( "'+Rossi", $found[3], 'last_name starting with + must be prefixed with apostrophe.' );
	}
}
