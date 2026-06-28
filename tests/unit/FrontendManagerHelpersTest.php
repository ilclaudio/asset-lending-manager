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

		$this->frontend_manager = new ALMGR_Frontend_Manager( new ALMGR_Settings_Manager() );
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
}
