<?php
/**
 * Shared asset history read service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides one history read contract for frontend, REST and Abilities.
 */
class ALMGR_Asset_History_Service {

	/**
	 * Loan workflow/history persistence service.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	private $loan_manager;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Loan_Manager $loan_manager Existing history query owner.
	 */
	public function __construct( ALMGR_Loan_Manager $loan_manager ) {
		$this->loan_manager = $loan_manager;
	}

	/**
	 * Return visible history rows for an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @param int $user_id  Actor used by the loan manager visibility rules.
	 * @param int $per_page Rows per page.
	 * @param int $page     Current page.
	 * @return ALMGR_Asset_History_Result
	 */
	public function get_history( $asset_id, $user_id = 0, $per_page = ALMGR_Pagination::DEFAULT_PER_PAGE, $page = 1 ) {
		$asset_id = absint( $asset_id );
		$per_page = ALMGR_Pagination::normalize_per_page( $per_page );
		$page     = ALMGR_Pagination::normalize_page( $page );

		$result = $this->loan_manager->get_asset_history_paginated( $asset_id, $per_page, $page, (int) $user_id );
		$total  = isset( $result['total'] ) ? (int) $result['total'] : 0;
		$pages  = $total > 0 ? (int) ceil( $total / $per_page ) : 0;
		$page   = $pages > 0 ? min( $page, $pages ) : 1;

		return new ALMGR_Asset_History_Result(
			isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : array(),
			$total,
			$page,
			$per_page,
			$pages
		);
	}
}
