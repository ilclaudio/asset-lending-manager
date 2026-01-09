<?php
/**
 * Tests for Plugin Manager modules initialization.
 */

use PHPUnit\Framework\TestCase;

/**
 * Test that PluginManager loads all expected modules.
 */
class PluginManagerModulesTest extends TestCase {

	/**
	 * Plugin manager instance.
	 *
	 * @var ALM_Plugin_Manager
	 */
	private $plugin_manager;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_manager = ALM_Plugin_Manager::get_instance();
		$this->plugin_manager->init();
	}

	/**
	 * Test that modules array is initialized.
	 */
	public function test_modules_array_is_initialized() {
		$modules = $this->plugin_manager->get_modules();

		$this->assertIsArray( $modules, 'Modules should be an array.' );
		$this->assertNotEmpty( $modules, 'Modules array should not be empty.' );
	}

	/**
	 * Test that all expected module classes are loaded.
	 */
	public function test_expected_module_classes_are_loaded() {
		$modules = $this->plugin_manager->get_modules();

		$expected_classes = array(
			ALM_Settings_Manager::class,
			ALM_Role_Manager::class,
			ALM_Asset_Manager::class,
			ALM_Loan_Manager::class,
			ALM_Notification_Manager::class,
			ALM_Frontend_Manager::class,
		);

		foreach ( $expected_classes as $expected_class ) {
			$this->assertTrue(
				$this->modules_contain_instance_of( $modules, $expected_class ),
				sprintf( 'Module of class %s should be loaded.', $expected_class )
			);
		}
	}

	/**
	 * Test that every module exposes a register() method.
	 */
	public function test_modules_expose_register_method() {
		$modules = $this->plugin_manager->get_modules();

		foreach ( $modules as $module ) {
			$this->assertTrue(
				method_exists( $module, 'register' ),
				'Every module must implement a register() method.'
			);
		}
	}

	/**
	 * Check if modules array contains an instance of a given class.
	 *
	 * @param array  $modules Modules array.
	 * @param string $class   Class name.
	 *
	 * @return bool
	 */
	private function modules_contain_instance_of( array $modules, string $class ): bool {
		foreach ( $modules as $module ) {
			if ( $module instanceof $class ) {
				return true;
			}
		}

		return false;
	}
}
