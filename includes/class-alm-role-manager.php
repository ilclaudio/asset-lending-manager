<?php
/**
 * Asset Lending Manager - Role Manager
 *
 * Handles custom roles and capabilities for the Asset Lending Manager plugin.
 * This class is responsible for:
 * - Creating custom roles (alm_member, alm_operator)
 * - Defining and assigning custom capabilities
 * - Granting capabilities to administrators
 * - Providing helper methods for permission checks
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ALM_Role_Manager
 *
 * Manages WordPress roles and capabilities used by the plugin.
 * The plugin logic must always check capabilities, never roles.
 */
class ALM_Role_Manager {

	/**
	 * List of custom plugin capabilities.
	 *
	 * @var string[]
	 */
	private $capabilities = array(
		'alm_view_devices',
		'alm_view_device',
		'alm_create_device',
		'alm_edit_device',
	);

	/**
	 * Register WordPress hooks.
	 *
	 * Called by the Plugin Manager during bootstrap.
	 *
	 * @return void
	 */
	public function register() {
		// No runtime hooks needed for now.
	}

	/**
	 * Plugin activation callback.
	 *
	 * Creates roles and assigns capabilities.
	 * This method must be idempotent.
	 *
	 * @return void
	 */
	public function activate() {
		$this->add_roles();
		$this->add_capabilities();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * By design, roles and capabilities are not removed automatically
	 * to avoid destructive behavior.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Do nothing.
	}

	/**
	 * Create custom plugin roles if they do not already exist.
	 *
	 * @return void
	 */
	private function add_roles() {

		if ( ! get_role( 'alm_member' ) ) {
			add_role(
				'alm_member',
				__( 'Member', 'asset-lending-manager' ),
				array(
					'read' => true,
				)
			);
		}

		if ( ! get_role( 'alm_operator' ) ) {
			add_role(
				'alm_operator',
				__( 'Operator', 'asset-lending-manager' ),
				array(
					'read' => true,
				)
			);
		}
	}

	/**
	 * Assign custom capabilities to plugin roles and administrators.
	 *
	 * @return void
	 */
	private function add_capabilities() {

		$alm_member   = get_role( 'alm_member' );
		$alm_operator = get_role( 'alm_operator' );
		$admin        = get_role( 'administrator' );

		// Member: read-only access to devices.
		if ( $alm_member ) {
			$alm_member->add_cap( 'alm_view_devices' );
			$alm_member->add_cap( 'alm_view_device' );
		}

		// Operator: full device management.
		if ( $alm_operator ) {
			foreach ( $this->capabilities as $capability ) {
				$alm_operator->add_cap( $capability );
			}
		}

		// Administrator: always grant plugin capabilities.
		if ( $admin ) {
			foreach ( $this->capabilities as $capability ) {
				$admin->add_cap( $capability );
			}
		}
	}

	/**
	 * Check if the current user can manage devices.
	 *
	 * @return bool
	 */
	public function current_user_can_manage_devices() {
		return current_user_can( 'alm_create_device' ) || current_user_can( 'alm_edit_device' );
	}

	/**
	 * Check if the current user can view devices.
	 *
	 * @return bool
	 */
	public function current_user_can_view_devices() {
		return current_user_can( 'alm_view_devices' );
	}
}
