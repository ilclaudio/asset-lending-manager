<?php

defined( 'ABSPATH' ) || exit;

/**
 * This class manages all the settings of the plugin.
 */
class ALM_Settings_Manager {

	/**
	 * Register the module in WordPress.
	 */
	public function register() {
		// Hook to register settings, options, etc.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		// TODO: register plugin options
	}
}
