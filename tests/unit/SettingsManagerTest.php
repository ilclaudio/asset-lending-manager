<?php
/**
 * Unit tests for ALMGR_Settings_Manager.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies the settings service behavior without bootstrapping WordPress.
 *
 * These tests intentionally use an in-memory option store from tests/bootstrap.php
 * so the unit lane can validate ALMGR_Settings_Manager without requiring a real
 * WordPress database or the full plugin module graph.
 */
class ALMGR_Settings_Manager_Unit_Test extends TestCase {

	/**
	 * Reset the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'almgr_settings' );
	}

	/**
	 * Verifies that a fresh settings instance exposes all top-level default groups.
	 *
	 * This protects the public shape of the settings array: other plugin modules
	 * expect these groups to exist even before the option is saved in WordPress.
	 *
	 * @return void
	 */
	public function test_get_all_returns_expected_default_groups(): void {
		$settings = new ALMGR_Settings_Manager();
		$all      = $settings->get_all();

		$this->assertArrayHasKey( 'email', $all );
		$this->assertArrayHasKey( 'loans', $all );
		$this->assertArrayHasKey( 'frontend', $all );
		$this->assertArrayHasKey( 'autocomplete', $all );
		$this->assertArrayHasKey( 'rest_api', $all );
	}

	/**
	 * Verifies dot-notation reads for nested defaults and missing keys.
	 *
	 * This protects the read API used throughout the plugin, including the fallback
	 * behavior callers rely on when a setting path does not exist.
	 *
	 * @return void
	 */
	public function test_get_uses_dot_notation_and_fallbacks(): void {
		$settings = new ALMGR_Settings_Manager();

		$this->assertSame( 500, $settings->get( 'loans.request_message_max_length' ) );
		$this->assertSame( ALMGR_ASSET_LIST_PER_PAGE, $settings->get( 'frontend.asset_list_per_page' ) );
		$this->assertSame( 'fallback', $settings->get( 'missing.path', 'fallback' ) );
	}

	/**
	 * Verifies that set() updates one nested setting and persists it.
	 *
	 * This confirms that a single dot-notation write updates both the manager's
	 * readable output and the underlying option payload.
	 *
	 * @return void
	 */
	public function test_set_persists_value(): void {
		$settings = new ALMGR_Settings_Manager();

		$settings->set( 'logging.enabled', true );

		$this->assertTrue( $settings->get( 'logging.enabled' ) );
		$this->assertTrue( get_option( 'almgr_settings' )['logging']['enabled'] );
	}

	/**
	 * Verifies that set_batch() updates multiple nested settings in one write.
	 *
	 * This also protects the deep-merge behavior: unrelated default values must
	 * remain available after saving only a partial settings payload.
	 *
	 * @return void
	 */
	public function test_set_batch_preserves_unrelated_defaults(): void {
		$settings = new ALMGR_Settings_Manager();

		$settings->set_batch(
			array(
				'logging.level'         => 'debug',
				'asset.code_prefix'     => 'LAB',
				'autocomplete.min_chars' => 4,
			)
		);

		$all = $settings->get_all();

		$this->assertSame( 'debug', $all['logging']['level'] );
		$this->assertSame( 'LAB', $all['asset']['code_prefix'] );
		$this->assertSame( 4, $all['autocomplete']['min_chars'] );
		$this->assertArrayHasKey( 'from_name', $all['email'] );
		$this->assertTrue( $all['notifications']['loan_request'] );
	}

	/**
	 * Verifies that reset() discards saved changes and restores defaults.
	 *
	 * This protects the expected admin behavior for "restore defaults" style flows
	 * and ensures stale option values do not survive a reset.
	 *
	 * @return void
	 */
	public function test_reset_restores_defaults(): void {
		$settings = new ALMGR_Settings_Manager();

		$settings->set( 'logging.enabled', true );
		$settings->reset();

		$this->assertFalse( $settings->get( 'logging.enabled' ) );
	}
}
