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
 * These tests intentionally use an in-memory option store from tests/bootstrap-unit.php
 * so the unit lane can validate ALMGR_Settings_Manager without requiring a real
 * WordPress database or the full plugin module graph.
 */
class ALMGR_Settings_Manager_Unit_Test extends TestCase {

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_Settings_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( new ALMGR_Settings_Manager(), $arguments );
	}

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

	/**
	 * Verifies deep_merge preserves defaults, merges nested arrays, and allows scalar overrides.
	 *
	 * @return void
	 */
	public function test_deep_merge_handles_schema_evolution_and_scalar_overrides(): void {
		$merged = $this->invoke_private_method(
			'deep_merge',
			array(
				array(
					'notifications' => array(
						'enabled' => false,
						'modes'   => array(
							'owner'    => true,
							'operator' => false,
						),
					),
					'frontend'      => array(
						'asset_list_per_page' => 12,
						'default_filters'     => array(
							'state' => 'available',
							'type'  => '',
						),
					),
					'logging'       => false,
				),
				array(
					'notifications' => array(
						'modes' => array(
							'operator' => true,
						),
					),
					'frontend'      => array(
						'default_filters' => 'legacy-flat-value',
					),
					'logging'       => array(
						'enabled' => true,
					),
					'new_group'     => array(
						'enabled' => true,
					),
				),
			)
		);

		$this->assertSame( false, $merged['notifications']['enabled'] );
		$this->assertSame( true, $merged['notifications']['modes']['owner'] );
		$this->assertSame( true, $merged['notifications']['modes']['operator'] );
		$this->assertSame( 'legacy-flat-value', $merged['frontend']['default_filters'] );
		$this->assertSame( 12, $merged['frontend']['asset_list_per_page'] );
		$this->assertSame( array( 'enabled' => true ), $merged['logging'] );
		$this->assertSame( array( 'enabled' => true ), $merged['new_group'] );
	}

	/**
	 * Verifies that every default group is present and that key scalar defaults match
	 * the values other modules depend on.
	 *
	 * Adding or removing a group, or silently changing a scalar default, will break
	 * the module that reads it — this test catches those regressions early.
	 *
	 * @return void
	 */
	public function test_all_default_groups_and_key_scalars_are_present(): void {
		$settings = new ALMGR_Settings_Manager();
		$all      = $settings->get_all();

		$expected_groups = array(
			'email',
			'notifications',
			'template',
			'loans',
			'direct_assign',
			'workflow',
			'frontend',
			'autocomplete',
			'logging',
			'asset',
			'rest_api',
			'contact_form',
		);
		foreach ( $expected_groups as $group ) {
			$this->assertArrayHasKey( $group, $all, "Missing default group: {$group}" );
		}

		// Notifications — controls whether emails are sent at all.
		$this->assertFalse( $all['notifications']['enabled'] );
		$this->assertTrue( $all['notifications']['loan_request'] );
		$this->assertSame( 'no_owner', $all['notifications']['loan_request_operator_mode'] );
		$this->assertTrue( $all['notifications']['loan_decision'] );
		$this->assertTrue( $all['notifications']['loan_confirmation'] );

		// Loans — loan-request business rules.
		$this->assertTrue( $all['loans']['loan_requests_enabled'] );
		$this->assertSame( 0, $all['loans']['max_active_per_user'] );
		$this->assertSame( 500, $all['loans']['request_message_max_length'] );

		// Direct assign — operator direct-assignment toggle.
		$this->assertTrue( $all['direct_assign']['enabled'] );

		// Workflow — cancel-concurrent-requests behavior.
		$this->assertTrue( $all['workflow']['cancel_concurrent_requests_on_assign'] );

		// Frontend — filter and pagination defaults.
		$this->assertFalse( $all['frontend']['default_filters_open'] );
		$this->assertFalse( $all['frontend']['default_kit_filter'] );
		$this->assertSame( ALMGR_ASSET_LIST_PER_PAGE, $all['frontend']['asset_list_per_page'] );

		// Autocomplete — search thresholds.
		$this->assertSame( 3, $all['autocomplete']['min_chars'] );

		// Logging — debug output is off by default.
		$this->assertFalse( $all['logging']['enabled'] );
		$this->assertSame( 'error', $all['logging']['level'] );

		// REST API — enabled by default.
		$this->assertTrue( $all['rest_api']['enabled'] );

		// Contact form — enabled by default.
		$this->assertTrue( $all['contact_form']['enabled'] );
		$this->assertSame( 500, $all['contact_form']['max_message_length'] );
	}
}
