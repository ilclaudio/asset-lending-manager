<?php
/**
 * Shared loan request read service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Owns loan request read queries shared by UI, REST and Abilities adapters.
 */
class ALMGR_Loan_Request_Query_Service {

	/**
	 * Return requests for an asset.
	 *
	 * @param int    $asset_id Asset ID.
	 * @param string $status   Optional request status. Empty means all statuses.
	 * @return ALMGR_Loan_Request_Query_Result
	 */
	public function get_for_asset( $asset_id, $status = 'pending' ) {
		global $wpdb;

		$asset_id   = absint( $asset_id );
		$table_name = $wpdb->prefix . 'almgr_loan_requests';

		if ( '' === (string) $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service owns the shared request read query.
			$items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE asset_id = %d ORDER BY request_date DESC',
					$table_name,
					$asset_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service owns the shared request read query.
			$items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE asset_id = %d AND status = %s ORDER BY request_date DESC',
					$table_name,
					$asset_id,
					sanitize_key( $status )
				)
			);
		}

		return new ALMGR_Loan_Request_Query_Result( is_array( $items ) ? $items : array() );
	}

	/**
	 * Return requests made by a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Optional request status. Empty means all statuses.
	 * @return ALMGR_Loan_Request_Query_Result
	 */
	public function get_for_user( $user_id, $status = '' ) {
		global $wpdb;

		$user_id    = absint( $user_id );
		$table_name = $wpdb->prefix . 'almgr_loan_requests';

		if ( '' === (string) $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service owns the shared request read query.
			$items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE requester_id = %d ORDER BY request_date DESC',
					$table_name,
					$user_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service owns the shared request read query.
			$items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE requester_id = %d AND status = %s ORDER BY request_date DESC',
					$table_name,
					$user_id,
					sanitize_key( $status )
				)
			);
		}

		return new ALMGR_Loan_Request_Query_Result( is_array( $items ) ? $items : array() );
	}

	/**
	 * Project a request record consistently for JSON-capable channels.
	 *
	 * @param object $request Loan request record.
	 * @return array
	 */
	public function project( $request ) {
		return array(
			'id'              => (int) $request->id,
			'asset_id'        => (int) $request->asset_id,
			'requester_id'    => (int) $request->requester_id,
			'owner_id'        => (int) $request->owner_id,
			'request_date'    => (string) $request->request_date,
			'request_message' => (string) $request->request_message,
			'status'          => (string) $request->status,
		);
	}
}
