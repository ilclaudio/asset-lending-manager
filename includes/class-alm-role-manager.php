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

defined( 'ABSPATH' ) || exit;

require_once 'class-alm-capabilities.php';

/**
 * Class ALM_Role_Manager
 *
 * Manages WordPress roles and capabilities used by the plugin.
 * The plugin logic must always check capabilities, never roles.
 */
class ALM_Role_Manager {

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
		// Create MEMBER role.
		if ( ! get_role( ALM_MEMBER_ROLE ) ) {
			add_role(
				ALM_MEMBER_ROLE,
				__( 'Member', 'asset-lending-manager' ),
				array(
					'read' => true,
				)
			);
		}
		// Create OPERATOR role.
		if ( ! get_role( ALM_OPERATOR_ROLE ) ) {
			add_role(
				ALM_OPERATOR_ROLE,
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
		// Administrator: always grant plugin capabilities.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( ALM_Capabilities::get_all_device_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}
		// Operator: full device management.
		$alm_operator = get_role( ALM_OPERATOR_ROLE );
		if ( $alm_operator ) {
			foreach ( ALM_Capabilities::get_all_device_caps() as $cap ) {
				$alm_operator->add_cap( $cap );
			}
		}
		// Member: read-only access to devices.
		$alm_member = get_role( ALM_MEMBER_ROLE );
		if ( $alm_member ) {
			$alm_member->add_cap( ALM_VIEW_DEVICES );
			$alm_member->add_cap( ALM_VIEW_DEVICE );
		}
	}

	/**
	 * Check if the current user can manage devices.
	 *
	 * @return bool
	 */
	public function current_user_can_manage_devices() {
		return current_user_can( ALM_CREATE_DEVICE ) || current_user_can( ALM_EDIT_DEVICE );
	}

	/**
	 * Check if the current user can view devices.
	 *
	 * @return bool
	 */
	public function current_user_can_view_devices() {
		return current_user_can( ALM_VIEW_DEVICES );
	}
}
