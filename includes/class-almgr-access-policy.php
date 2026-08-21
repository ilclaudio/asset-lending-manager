<?php
/**
 * Shared domain access rules.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Centralizes record-level authorization rules used by ALMGR services.
 */
class ALMGR_Access_Policy {

	/**
	 * Determine whether an actor may view the assets currently held by a member.
	 *
	 * A user with the asset-edit capability may inspect any member. Other users
	 * may inspect only their own held assets and still need view-asset access.
	 *
	 * @param int $actor_id  Authenticated actor user ID.
	 * @param int $member_id Member whose assets are requested.
	 * @return bool
	 */
	public function can_view_member_assets( $actor_id, $member_id ) {
		$actor_id  = absint( $actor_id );
		$member_id = absint( $member_id );

		if ( $actor_id <= 0 || $member_id <= 0 ) {
			return false;
		}

		if ( user_can( $actor_id, ALMGR_EDIT_ASSET ) ) {
			return true;
		}

		return $actor_id === $member_id && user_can( $actor_id, ALMGR_VIEW_ASSET );
	}
}
