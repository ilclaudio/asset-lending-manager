<?php
/**
 * Unit tests for ALMGR_REST_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pagination clamping logic without registering REST routes or hitting the database.
 */
class ALMGR_REST_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * REST manager under test.
	 *
	 * @var ALMGR_REST_Manager
	 */
	private $rest_manager;

	/**
	 * Create a REST manager instance without registering any WordPress hooks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$asset_reads      = new ALMGR_Asset_Read_Service( new ALMGR_Asset_Query_Service() );
		$asset_details    = new ALMGR_Asset_Detail_Service( $asset_reads );
		$asset_history    = new ALMGR_Asset_History_Service( new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() ) );
		$member_assets    = new ALMGR_Member_Assets_Service( $asset_reads, new ALMGR_Access_Policy() );
		$this->rest_manager = new ALMGR_REST_Manager(
			new ALMGR_Settings_Manager(),
			$asset_reads,
			$asset_details,
			$asset_history,
			$member_assets
		);
	}

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_REST_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->rest_manager, $arguments );
	}

	/**
	 * Verify that in-range values are returned unchanged.
	 *
	 * @return void
	 */
	public function test_clamp_per_page_returns_value_in_valid_range(): void {
		$this->assertSame( 1, $this->invoke_private_method( 'clamp_per_page', array( 1 ) ) );
		$this->assertSame( 50, $this->invoke_private_method( 'clamp_per_page', array( 50 ) ) );
		$this->assertSame( 100, $this->invoke_private_method( 'clamp_per_page', array( 100 ) ) );
	}

	/**
	 * Verify that values below 1 are clamped to 1 and values above MAX_PER_PAGE are clamped to 100.
	 *
	 * @return void
	 */
	public function test_clamp_per_page_enforces_lower_and_upper_bounds(): void {
		$this->assertSame( 1, $this->invoke_private_method( 'clamp_per_page', array( 0 ) ) );
		$this->assertSame( 1, $this->invoke_private_method( 'clamp_per_page', array( -5 ) ) );
		$this->assertSame( 100, $this->invoke_private_method( 'clamp_per_page', array( 101 ) ) );
		$this->assertSame( 100, $this->invoke_private_method( 'clamp_per_page', array( 999 ) ) );
	}

	/**
	 * Verify that non-integer inputs are coerced via (int) before clamping.
	 *
	 * @return void
	 */
	public function test_clamp_per_page_coerces_non_integer_input(): void {
		$this->assertSame( 25, $this->invoke_private_method( 'clamp_per_page', array( '25' ) ) );
		$this->assertSame( 1, $this->invoke_private_method( 'clamp_per_page', array( '0' ) ) );
		$this->assertSame( 1, $this->invoke_private_method( 'clamp_per_page', array( 1.9 ) ) );
	}
}
