<?php
/**
 * Settings Manager for Asset Lending Manager plugin.
 *
 * This file defines the ALM_Settings_Manager class, responsible for
 * handling all plugin configuration options.
 *
 * The settings are stored as a single serialized array in the WordPress
 * options table (wp_options) under the "alm_settings" option name.
 *
 * The manager provides:
 * - Centralized default values for all plugin settings.
 * - Safe read access with automatic fallback to defaults.
 * - Write access for updating individual settings.
 *
 * Settings are accessed using dot notation keys, for example:
 *
 *   $settings->get( 'email.from_address' );
 *   $settings->set( 'logging.enabled', true );
 *
 * This class does not contain any business logic and does not render
 * any user interface. It acts as a configuration service used by
 * other plugin modules.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class manages all the settings of the plugin.
 */
class ALM_Settings_Manager {

	/**
	 * Option name used in wp_options.
	 *
	 * @var string
	 */
	private $option_name = 'alm_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		'email' => array(
			'from_name'    => '',
			'from_address' => '',
			'system_email' => '',
		),
		'notifications' => array(
			'enabled'           => true,
			'loan_request'      => true,
			'loan_decision'     => true,
			'loan_confirmation' => true,
		),
		'loans' => array(
			'max_active_per_user'     => 0,
			'allow_multiple_requests' => true,
		),
		'frontend' => array(
			'assets_page_id' => 0,
		),
		'logging' => array(
			'enabled' => false,
			'level'   => 'error',
		),
	);

	/**
	 * Register hooks.
	 */
	public function register() {
		// Register actions and filters here.
	}

	/**
	 * Plugin activation handler.
	 *
	 * Called once when the plugin is activated.
	 *
	 * @return void
	 */
	public function activate() {
		// Do nothing.
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * Called once when the plugin is deactivated.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Do nothing.
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_all() {
		$saved = get_option( $this->option_name, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return $this->deep_merge( $this->defaults, $saved );
	}

	/**
	 * Get a specific setting using dot notation.
	 *
	 * Example: email.from_address
	 *
	 * @param string $key     Setting key using dot notation.
	 * @param mixed  $default Default value returned if the setting is not found.
	 * @return mixed Setting value or default.
	 */
	public function get( $key, $default = null ) {
		$settings = $this->get_all();
		$keys = explode( '.', $key );

		foreach ( $keys as $segment ) {
			if ( ! is_array( $settings ) || ! array_key_exists( $segment, $settings ) ) {
				return $default;
			}
			$settings = $settings[ $segment ];
		}

		return $settings;
	}

	/**
	 * Set a specific setting using dot notation.
	 *
	 * Example: email.from_address
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 */
	public function set( $key, $value ) {
		$settings = $this->get_all();
		$keys     = explode( '.', $key );
		$ref      = &$settings;
		foreach ( $keys as $segment ) {
			if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
				$ref[ $segment ] = array();
			}
			$ref = &$ref[ $segment ];
		}
		$ref = $value;
		update_option( $this->option_name, $settings );
	}

	/**
	 * Reset settings to defaults.
	 */
	public function reset() {
		update_option( $this->option_name, $this->defaults );
	}

	/**
	 * Deep merge two arrays preserving defaults.
	 *
	 * @param array $defaults
	 * @param array $saved
	 * @return array
	 */
	private function deep_merge( array $defaults, array $saved ) {
		foreach ( $saved as $key => $value ) {
			if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = $this->deep_merge( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}

		return $defaults;
	}
}
