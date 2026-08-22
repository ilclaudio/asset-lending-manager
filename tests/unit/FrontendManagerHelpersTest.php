<?php
/**
 * Unit tests for ALMGR_Frontend_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies lightweight frontend query sanitizers without a WordPress bootstrap.
 */
class ALMGR_Frontend_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Frontend manager under test.
	 *
	 * @var ALMGR_Frontend_Manager
	 */
	private $frontend_manager;

	/**
	 * Prepare a frontend manager for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$asset_reads        = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details      = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history      = new ALMGR_Asset_History_Service( new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() ) );
		$member_assets      = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );
		$this->frontend_manager = new ALMGR_Frontend_Manager(
			new ALMGR_Settings_Manager(),
			$asset_reads,
			$asset_details,
			$asset_history,
			$member_assets
		);
		$_GET                   = array();
	}

	/**
	 * Clean superglobals after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET = array();

		parent::tearDown();
	}

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_Frontend_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->frontend_manager, $arguments );
	}

	/**
	 * Verify free-text query values are safely normalized for read-only filters.
	 *
	 * @return void
	 */
	public function test_get_sanitized_query_text_normalizes_missing_and_tagged_values(): void {
		$this->assertSame( '', $this->invoke_private_method( 'get_sanitized_query_text', array( 'search' ) ) );

		$_GET['search'] = "  <b>Star</b>\n Finder  ";

		$this->assertSame( 'Star Finder', $this->invoke_private_method( 'get_sanitized_query_text', array( 'search' ) ) );
	}

	/**
	 * Verify slug query values reuse text sanitization before slug normalization.
	 *
	 * @return void
	 */
	public function test_get_sanitized_query_slug_converts_sanitized_text_to_slug(): void {
		$_GET['structure'] = '  Kit <em>Alpha</em>  ';

		$this->assertSame( 'kit-alpha', $this->invoke_private_method( 'get_sanitized_query_slug', array( 'structure' ) ) );
		$this->assertSame( '', $this->invoke_private_method( 'get_sanitized_query_slug', array( 'missing' ) ) );
	}

	/**
	 * Verify that missing or empty-string keys return 0.
	 *
	 * @return void
	 */
	public function test_get_sanitized_query_absint_returns_zero_for_missing_or_empty_key(): void {
		$this->assertSame( 0, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'missing' ) ) );

		$_GET['empty_val'] = '';
		$this->assertSame( 0, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'empty_val' ) ) );

		$_GET['zero_val'] = '0';
		$this->assertSame( 0, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'zero_val' ) ) );
	}

	/**
	 * Verify that numeric query strings are converted to positive integers via absint().
	 *
	 * @return void
	 */
	public function test_get_sanitized_query_absint_converts_numeric_query_values_to_positive_integers(): void {
		$_GET['page']   = '42';
		$_GET['level']  = '<b>7</b>';
		$_GET['offset'] = '-5';

		$this->assertSame( 42, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'page' ) ) );
		$this->assertSame( 7, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'level' ) ) );
		$this->assertSame( 5, $this->invoke_private_method( 'get_sanitized_query_absint', array( 'offset' ) ) );
	}
}
