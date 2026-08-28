<?php
/**
 * Shared active-loan counting service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Counts assets currently assigned to ALM users.
 */
class ALMGR_Active_Loan_Count_Service {

	/**
	 * Count assets currently assigned to one user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	public function count_for_user( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$counts = $this->count_for_users( array( $user_id ) );
		return isset( $counts[ $user_id ] ) ? $counts[ $user_id ] : 0;
	}

	/**
	 * Count assets currently assigned to multiple users in one query.
	 *
	 * @param int[] $user_ids WordPress user IDs.
	 * @return array<int,int> Map of user ID to active-loan count.
	 */
	public function count_for_users( array $user_ids ) {
		global $wpdb;

		$user_ids = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
		if ( empty( $user_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		$args         = array_merge(
			array(
				'_almgr_current_owner',
				'publish',
				ALMGR_ASSET_CPT_SLUG,
			),
			$user_ids
		);
		$query        = "SELECT pm.meta_value AS owner_id, COUNT(DISTINCT pm.post_id) AS loan_count
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			  AND p.post_status = %s
			  AND p.post_type = %s
			  AND pm.meta_value IN ( $placeholders )
			GROUP BY pm.meta_value"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholder list is generated from sanitized integer IDs only.

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string contains a documented dynamic placeholder list.
				...$args
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$counts = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$counts[ (int) $row->owner_id ] = (int) $row->loan_count;
		}

		return $counts;
	}
}
