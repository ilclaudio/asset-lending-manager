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
	 * Get default settings.
	 *
	 * Defined as a method (not a property) so that __() translation functions
	 * are called at runtime, after the plugin text domain has been loaded.
	 * Template defaults delegate to alm_get_email_templates() for the same reason.
	 *
	 * @return array
	 */
	private function get_defaults(): array {
		// alm_get_email_templates() uses __() and must not be called before the 'init'
		// action (WordPress 6.7 JIT textdomain loading). Templates are only needed when
		// sending emails, which always happens after 'init', so empty arrays are safe before.
		$templates = did_action( 'init' ) ? alm_get_email_templates() : array( 'subject' => array(), 'body' => array() );
		return array(
			'email'         => array(
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
			'template'      => array(
				'subject' => $templates['subject'],
				'body'    => $templates['body'],
			),
			'loans'         => array(
				'loan_requests_enabled'              => true,
				'max_active_per_user'                => 0,
				'allow_multiple_requests'            => true,
				'approver_policy_for_unowned_assets' => 'none',
				'request_message_max_length'         => 500,
				'rejection_message_max_length'       => 255,
				'direct_assign_reason_max_length'    => 500,
			),
			'direct_assign' => array(
				'enabled'              => true,
				'allowed_target_roles' => array( ALM_MEMBER_ROLE, ALM_OPERATOR_ROLE ),
				'require_reason'       => false,
			),
			'workflow'      => array(
				'cancel_concurrent_requests_on_assign'        => true,
				'cancel_component_requests_when_kit_assigned' => true,
				'automatic_operations_actor_user_id'          => 1,
			),
			'frontend'      => array(
				'assets_page_id'          => 0,
				'login_redirect_page_id'  => 0,
				'logout_redirect_page_id' => 0,
				'asset_list_per_page'     => ALM_ASSET_LIST_PER_PAGE,
				'default_filters_open'    => false,
			),
			'autocomplete'  => array(
				'min_chars'                      => 3,
				'max_results'                    => ALM_AUTOCOMPLETE_MAX_RESULTS,
				'description_length'             => ALM_AUTOCOMPLETE_DESC_LENGTH,
				'public_assets_endpoint_enabled' => true,
			),
			'logging'       => array(
				'enabled'            => false,
				'level'              => 'error',
				'mask_personal_data' => false,
				'log_email_events'   => false,
			),
			'asset'         => array(
				'code_prefix' => ALM_ASSET_CODE_PREFIX,
			),
		);
	}

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

		return $this->deep_merge( $this->get_defaults(), $saved );
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
		update_option( $this->option_name, $this->get_defaults() );
	}

	/**
	 * Set multiple settings at once using dot notation.
	 *
	 * Performs a single database write for all provided changes.
	 *
	 * @param array $changes Associative array of dot-notation key => value pairs.
	 * @return void
	 */
	public function set_batch( array $changes ) {
		$settings = $this->get_all();
		foreach ( $changes as $key => $value ) {
			$keys = explode( '.', $key );
			$ref  = &$settings;
			foreach ( $keys as $segment ) {
				if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
					$ref[ $segment ] = array();
				}
				$ref = &$ref[ $segment ];
			}
			$ref = $value;
			unset( $ref );
		}
		update_option( $this->option_name, $settings );
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
