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

	/**
	 * Determine whether an actor may view requests for an asset.
	 *
	 * Operators may inspect every asset. Other users may inspect requests for
	 * assets they currently own and still need asset-view capability.
	 *
	 * @param int $actor_id Authenticated actor user ID.
	 * @param int $asset_id Asset whose requests are requested.
	 * @return bool
	 */
	public function can_view_asset_requests( $actor_id, $asset_id ) {
		$actor_id = absint( $actor_id );
		$asset_id = absint( $asset_id );

		if ( $actor_id <= 0 || $asset_id <= 0 || ! user_can( $actor_id, ALMGR_VIEW_ASSET ) ) {
			return false;
		}

		return user_can( $actor_id, ALMGR_EDIT_ASSET ) || (int) get_post_meta( $asset_id, '_almgr_current_owner', true ) === $actor_id;
	}

	/**
	 * Determine whether an actor may view one loan request.
	 *
	 * @param int    $actor_id Authenticated actor user ID.
	 * @param object $request  Loan request record.
	 * @return bool
	 */
	public function can_view_loan_request( $actor_id, $request ) {
		$actor_id = absint( $actor_id );
		if ( $actor_id <= 0 || ! is_object( $request ) || ! user_can( $actor_id, ALMGR_VIEW_ASSET ) ) {
			return false;
		}

		return $actor_id === (int) $request->requester_id || $this->can_view_asset_requests( $actor_id, $request->asset_id );
	}
}
