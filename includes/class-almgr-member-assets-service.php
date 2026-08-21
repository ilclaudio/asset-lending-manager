<?php
/**
 * Shared service for assets currently held by a member.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides the domain query and authorization for member-held assets.
 */
class ALMGR_Member_Assets_Service {

	/**
	 * Shared asset catalog query service.
	 *
	 * @var ALMGR_Asset_Query_Service
	 */
	private $asset_queries;

	/**
	 * Shared record-level access policy.
	 *
	 * @var ALMGR_Access_Policy
	 */
	private $access_policy;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Asset_Query_Service $asset_queries Shared asset catalog query service.
	 * @param ALMGR_Access_Policy       $access_policy Shared record-level access policy.
	 */
	public function __construct( ALMGR_Asset_Query_Service $asset_queries, ALMGR_Access_Policy $access_policy ) {
		$this->asset_queries = $asset_queries;
		$this->access_policy = $access_policy;
	}

	/**
	 * Query published assets currently held by a member when the actor may view them.
	 *
	 * @param int    $actor_id  Authenticated actor user ID.
	 * @param int    $member_id Member whose assets are requested.
	 * @param array  $filters   Optional catalog filters and pagination.
	 * @param string $fields    WordPress query fields value.
	 * @return WP_Query|WP_Error
	 */
	public function get_assets_for_member( $actor_id, $member_id, array $filters = array(), $fields = 'all' ) {
		$actor_id  = absint( $actor_id );
		$member_id = absint( $member_id );

		if ( $member_id <= 0 || ! get_userdata( $member_id ) ) {
			return new WP_Error(
				'almgr_member_not_found',
				__( 'Member not found.', 'asset-lending-manager' ),
				array( 'status' => 404 )
			);
		}

		if ( ! $this->access_policy->can_view_member_assets( $actor_id, $member_id ) ) {
			return new WP_Error(
				'almgr_member_assets_forbidden',
				__( 'You do not have permission to view these member assets.', 'asset-lending-manager' ),
				array( 'status' => 403 )
			);
		}

		$filters['owner'] = $member_id;

		return $this->asset_queries->get_assets( $filters, $fields );
	}
}
