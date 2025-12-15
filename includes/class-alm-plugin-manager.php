<?php

defined( 'ABSPATH' ) || exit;

/**
 * This is the main class of the plugin that registers all the needed components.
 */
class ALM_Plugin_Manager {

	/**
	 * Singleton instance
	 *
	 * @var ALM_Plugin_Manager
	 */
	private static $instance = null;

	// Modules.
	private $settings_manager;
	private $role_manager;
	private $device_manager;
	private $loan_manager;
	private $notification_manager;
	private $frontend_manager;

	/**
	 * Get singleton instance
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
	 * Constructor
	 */
	private function __construct() {
		// private for singleton.
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain(
			ALM_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( ALM_PLUGIN_FILE ) ) . '/languages/'
		);

		// Initialize modules.
		$this->settings_manager     = new ALM_Settings_Manager();
		$this->role_manager         = new ALM_Role_Manager();
		$this->device_manager       = new ALM_Device_Manager();
		$this->loan_manager         = new ALM_Loan_Manager();
		$this->notification_manager = new ALM_Notification_Manager();
		$this->frontend_manager     = new ALM_Frontend_Manager();

		// Register modules.
		$this->settings_manager->register();
		$this->role_manager->register();
		$this->device_manager->register();
		$this->loan_manager->register();
		$this->notification_manager->register();
		$this->frontend_manager->register();

		// @TODO: Remove this ( Test i18n - debug only).
		add_action(
			'init',
			function() {
				// Translation example.
				$english = __( 'Hello world', 'asset-lending-manager' );
				error_log( 'Translation: ' . $english );
			}
		);
	}

	/**
	 * To prevent cloning.
	 */
	private function __clone() {}

	/**
	 * To prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
