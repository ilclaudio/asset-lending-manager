<?php
// includes/class-settings-manager.php

defined( 'ABSPATH' ) || exit;

class Settings_Manager {

	public function register() {
		// Hook to register settings, options, etc.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		// TODO: register plugin options
	}
}
