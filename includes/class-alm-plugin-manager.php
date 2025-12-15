<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main module of the plugin to bootstrap and to coordinate all the other modules.
 */
class ALM_Plugin_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var ALM_Plugin_Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered plugin modules.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Get singleton instance.
	 *
	 * @return ALM_Plugin_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce singleton.
	 */
	private function __construct() {}

	/**
	 * Plugin bootstrap.
	 *
	 * Called once from the main plugin file.
	 */
	public function init() {
		$this->load_textdomain();
		$this->init_modules();
		$this->register_modules();
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			ALM_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( ALM_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Instantiate plugin modules.
	 *
	 * @return void
	 */
	private function init_modules() {
		$this->modules = array(
			new ALM_Settings_Manager(),
			new ALM_Role_Manager(),
			new ALM_Device_Manager(),
			new ALM_Loan_Manager(),
			new ALM_Notification_Manager(),
			new ALM_Frontend_Manager(),
		);
	}

	/**
	 * Register all modules with WordPress.
	 *
	 * @return void
	 */
	private function register_modules() {
		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'register' ) ) {
				$module->register();
			}
		}
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws Exception If unserialization is attempted.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton.' );
	}
}
