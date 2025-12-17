<?php
/**
 * Integration tests for ALM_Settings_Manager.
 *
 * @package AssetLendingManager
 */

class ALM_Settings_Manager_Test extends WP_UnitTestCase {

	/**
	 * Settings manager instance.
	 *
	 * @var ALM_Settings_Manager
	 */
	protected $settings;

	/**
	 * Setup before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settings = new ALM_Settings_Manager();

		// Reset settings before each test to start clean
		$this->settings->reset();
	}

	/**
	 * Teardown after each test.
	 */
	public function tearDown(): void {
		// Clean up options from database
		delete_option( 'alm_settings' );
		
		parent::tearDown();
	}

	/**
	 * Test that get_all() returns defaults initially.
	 */
	public function test_get_all_returns_defaults() {
		$all = $this->settings->get_all();

		$this->assertIsArray( $all );
		$this->assertArrayHasKey( 'email', $all );
		$this->assertArrayHasKey( 'notifications', $all );
		$this->assertArrayHasKey( 'loans', $all );
		$this->assertArrayHasKey( 'frontend', $all );
		$this->assertArrayHasKey( 'logging', $all );
	}

	/**
	 * Test get() returns a default value if key does not exist.
	 */
	public function test_get_returns_default_for_non_existing_key() {
		$result = $this->settings->get( 'non.existing.key', 'fallback' );
		$this->assertSame( 'fallback', $result );
	}

	/**
	 * Test get() returns saved value after set().
	 */
	public function test_set_and_get_value() {
		$this->settings->set( 'logging.enabled', true );
		$result = $this->settings->get( 'logging.enabled' );
		$this->assertTrue( $result );
	}

	/**
	 * Test partial updates do not remove default values.
	 */
	public function test_partial_update_preserves_defaults() {
		$this->settings->set( 'logging.level', 'debug' );
		$all = $this->settings->get_all();

		// logging.level changed.
		$this->assertSame( 'debug', $all['logging']['level'] );

		// other defaults preserved.
		$this->assertArrayHasKey( 'from_name', $all['email'] );
		$this->assertArrayHasKey( 'loan_request', $all['notifications'] );
	}

	/**
	 * Test reset restores defaults.
	 */
	public function test_reset_restores_defaults() {
		$this->settings->set( 'logging.enabled', true );
		$this->settings->reset();

		$result = $this->settings->get( 'logging.enabled' );
		$this->assertFalse( $result );
	}

	/**
	 * Test that settings are persisted to database.
	 */
	public function test_settings_persist_to_database() {
		$this->settings->set( 'email.from_name', 'Test Name' );

		// Create new instance to verify persistence
		$new_settings = new ALM_Settings_Manager();
		$result = $new_settings->get( 'email.from_name' );

		$this->assertSame( 'Test Name', $result );
	}
}
