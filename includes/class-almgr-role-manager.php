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
	 * Allow ALMGR operators to edit the metadata of images that belong to an asset.
	 *
	 * This enables title and alternative text edits in the featured-image modal
	 * without granting generic post/page editing caps. The grant is limited to
	 * images related to an `almgr_asset` (see is_asset_image()): images used by
	 * other content, themes, or other authors stay out of reach.
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

		if ( ! $this->is_asset_image( $attachment ) ) {
			return $caps;
		}

		return array( ALMGR_EDIT_ASSET );
	}

	/**
	 * Check whether an image attachment belongs to an ALMGR asset.
	 *
	 * An image belongs to an asset when it is uploaded to an asset (its parent
	 * is an `almgr_asset`, which is how the featured-image modal creates it
	 * before it is selected) or when it is currently the featured image of an
	 * asset. Both conditions are checked against the live data on every call, so
	 * replacing a featured image revokes the access to the old one at once.
	 *
	 * @param WP_Post $attachment Attachment post.
	 * @return bool
	 */
	private function is_asset_image( $attachment ) {
		$parent_id = (int) $attachment->post_parent;
		if ( $parent_id > 0 ) {
			$parent = get_post( $parent_id );
			if ( $parent && ALMGR_ASSET_CPT_SLUG === $parent->post_type ) {
				return true;
			}
		}

		$featured_in = new WP_Query(
			array(
				'post_type'              => ALMGR_ASSET_CPT_SLUG,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future', 'auto-draft' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single-row lookup of one attachment ID among asset posts, run only for operators during attachment capability checks.
					array(
						'key'     => '_thumbnail_id',
						'value'   => (int) $attachment->ID,
						'compare' => '=',
					),
				),
			)
		);

		return ! empty( $featured_in->posts );
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

	/**
	 * Check if the current user can use the contact resource form.
	 *
	 * Members, operators, and administrators all have ALMGR_VIEW_ASSET.
	 *
	 * @return bool
	 */
	public function current_user_can_contact_resource() {
		return is_user_logged_in() && current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Return unique email addresses of all users with the operator role.
	 *
	 * Falls back to an empty array when no operator exists; callers are
	 * responsible for providing a fallback address (e.g. admin email).
	 *
	 * @return string[] List of sanitized, non-empty email addresses.
	 */
	public function get_operator_emails() {
		$query = new WP_User_Query(
			array(
				'role'   => ALMGR_OPERATOR_ROLE,
				'fields' => array( 'user_email' ),
			)
		);

		$emails = array();
		foreach ( $query->get_results() as $user ) {
			if ( ! is_object( $user ) || ! isset( $user->user_email ) ) {
				continue;
			}
			$email = sanitize_email( $user->user_email );
			if ( $email ) {
				$emails[] = $email;
			}
		}

		return array_values( array_unique( $emails ) );
	}
}
