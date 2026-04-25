<?php
/**
 * Asset Lending Manager - Role Manager
 *
 * Handles custom roles and capabilities for the Asset Lending Manager plugin.
 * This class is responsible for:
 * - Creating custom roles (almgr_member, almgr_operator)
 * - Defining and assigning custom capabilities
 * - Granting capabilities to administrators
 * - Providing helper methods for permission checks
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-almgr-capabilities.php';

/**
 * Class ALMGR_Role_Manager
 *
 * Manages WordPress roles and capabilities used by the plugin.
 * The plugin logic must always check capabilities, never roles.
 */
class ALMGR_Role_Manager {

	/**
	 * Register WordPress hooks.
	 *
	 * Called by the Plugin Manager during bootstrap.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'map_meta_cap', array( $this, 'map_image_attachment_capabilities' ), 10, 4 );
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
		if ( ! get_role( ALMGR_MEMBER_ROLE ) ) {
			add_role(
				ALMGR_MEMBER_ROLE,
				__( 'Member', 'asset-lending-manager' ),
				array(
					'read' => true,
				)
			);
		}
		// Create OPERATOR role.
		if ( ! get_role( ALMGR_OPERATOR_ROLE ) ) {
			add_role(
				ALMGR_OPERATOR_ROLE,
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
			foreach ( ALMGR_Capabilities::get_all_asset_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}
		// Operator: full asset management.
		$almgr_operator = get_role( ALMGR_OPERATOR_ROLE );
		if ( $almgr_operator ) {
			foreach ( ALMGR_Capabilities::get_all_asset_caps() as $cap ) {
				$almgr_operator->add_cap( $cap );
			}
			foreach ( $this->get_operator_media_caps() as $cap ) {
				$almgr_operator->add_cap( $cap );
			}
		}
		// Member: read-only access to assets.
		$almgr_member = get_role( ALMGR_MEMBER_ROLE );
		if ( $almgr_member ) {
			$almgr_member->add_cap( ALMGR_VIEW_ASSETS );
			$almgr_member->add_cap( ALMGR_VIEW_ASSET );
		}
	}

	/**
	 * Return WordPress media capabilities granted to operators.
	 *
	 * @return string[]
	 */
	private function get_operator_media_caps() {
		return array(
			'upload_files',
		);
	}

	/**
	 * Allow ALMGR operators to edit image attachment metadata.
	 *
	 * This enables title and alternative text edits in the Media Library and
	 * featured-image modal without granting generic post/page editing caps.
	 *
	 * @param string[] $caps    Primitive capabilities required by WordPress.
	 * @param string   $cap     Requested meta capability.
	 * @param int      $user_id User ID.
	 * @param mixed[]  $args    Additional arguments, usually containing post ID.
	 * @return string[]
	 */
	public function map_image_attachment_capabilities( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'edit_post', 'edit_post_meta' ), true ) ) {
			return $caps;
		}

		if ( 'edit_post_meta' === $cap ) {
			$meta_key = isset( $args[1] ) ? (string) $args[1] : '';
			if ( '_wp_attachment_image_alt' !== $meta_key ) {
				return $caps;
			}
		}

		$attachment_id = isset( $args[0] ) ? absint( $args[0] ) : 0;
		if ( $attachment_id <= 0 ) {
			return $caps;
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return $caps;
		}

		if ( ! wp_attachment_is_image( $attachment ) ) {
			return $caps;
		}

		if ( ! user_can( $user_id, ALMGR_EDIT_ASSET ) ) {
			return $caps;
		}

		return array( ALMGR_EDIT_ASSET );
	}

	/**
	 * Check if the current user can manage assets.
	 *
	 * @return bool
	 */
	public function current_user_can_manage_assets() {
		return current_user_can( ALMGR_EDIT_ASSET );
	}

	/**
	 * Check if the current user can view assets.
	 *
	 * @return bool
	 */
	public function current_user_can_view_assets() {
		return current_user_can( ALMGR_VIEW_ASSETS );
	}
}
