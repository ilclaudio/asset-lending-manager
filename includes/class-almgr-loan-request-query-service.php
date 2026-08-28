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
	 * Return one loan request by ID.
	 *
	 * @param int $request_id Loan request ID.
	 * @return object|null Loan request record, or null when not found.
	 */
	public function get_by_id( $request_id ) {
		global $wpdb;

		$request_id = absint( $request_id );
		if ( $request_id <= 0 ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'almgr_loan_requests';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This service owns the shared request read query.
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$table_name,
				$request_id
			)
		);
	}

	/**
	 * Return requests for an asset.
	 *
	 * @param int    $asset_id Asset ID.
	 * @param string $status   Optional request status. Empty means all statuses.
	 * @param int    $page     Optional page. Zero means no pagination.
	 * @param int    $per_page Optional page size. Zero means no pagination.
	 * @return ALMGR_Loan_Request_Query_Result
	 */
	public function get_for_asset( $asset_id, $status = 'pending', $page = 0, $per_page = 0 ) {
		return $this->query_requests( 'asset_id', $asset_id, $status, $page, $per_page );
	}

	/**
	 * Return requests made by a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Optional request status. Empty means all statuses.
	 * @param int    $page    Optional page. Zero means no pagination.
	 * @param int    $per_page Optional page size. Zero means no pagination.
	 * @return ALMGR_Loan_Request_Query_Result
	 */
	public function get_for_user( $user_id, $status = '', $page = 0, $per_page = 0 ) {
		return $this->query_requests( 'requester_id', $user_id, $status, $page, $per_page );
	}

	/**
	 * Execute a normalized request query.
	 *
	 * @param string $column   Filter column owned by this service.
	 * @param int    $object_id Filter value.
	 * @param string $status   Optional status filter.
	 * @param int    $page     Optional page. Zero means no pagination.
	 * @param int    $per_page Optional page size. Zero means no pagination.
	 * @return ALMGR_Loan_Request_Query_Result
	 */
	private function query_requests( $column, $object_id, $status, $page, $per_page ) {
		global $wpdb;

		$object_id  = absint( $object_id );
		$page       = max( 0, (int) $page );
		$per_page   = max( 0, (int) $per_page );
		$table_name = $wpdb->prefix . 'almgr_loan_requests';
		$status     = sanitize_key( $status );

		if ( '' === $status ) {
			$count_query = $wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				$table_name,
				$column,
				$object_id
			);
		} else {
			$count_query = $wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d AND status = %s',
				$table_name,
				$column,
				$object_id,
				$status
			);
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This service owns the shared request count query, which is prepared above.
		$total = (int) $wpdb->get_var( $count_query );

		if ( '' === $status && $page > 0 && $per_page > 0 ) {
			$query = $wpdb->prepare(
				'SELECT * FROM %i WHERE %i = %d ORDER BY request_date DESC LIMIT %d OFFSET %d',
				$table_name,
				$column,
				$object_id,
				$per_page,
				( $page - 1 ) * $per_page
			);
		} elseif ( '' !== $status && $page > 0 && $per_page > 0 ) {
			$query = $wpdb->prepare(
				'SELECT * FROM %i WHERE %i = %d AND status = %s ORDER BY request_date DESC LIMIT %d OFFSET %d',
				$table_name,
				$column,
				$object_id,
				$status,
				$per_page,
				( $page - 1 ) * $per_page
			);
		} elseif ( '' === $status ) {
			$query = $wpdb->prepare(
				'SELECT * FROM %i WHERE %i = %d ORDER BY request_date DESC',
				$table_name,
				$column,
				$object_id
			);
		} else {
			$query = $wpdb->prepare(
				'SELECT * FROM %i WHERE %i = %d AND status = %s ORDER BY request_date DESC',
				$table_name,
				$column,
				$object_id,
				$status
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This service owns the shared request read query, which is prepared above.
		$items = $wpdb->get_results( $query );

		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : ( $total > 0 ? 1 : 0 );
		return new ALMGR_Loan_Request_Query_Result( is_array( $items ) ? $items : array(), $total, $page > 0 ? $page : 1, $per_page, $pages );
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
