<?php
/**
 * Shared loan request query result.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalized result for loan request reads.
 */
class ALMGR_Loan_Request_Query_Result {

	/**
	 * Loan request records.
	 *
	 * @var array
	 */
	private $items;

	/**
	 * Constructor.
	 *
	 * @param array $items Loan request records.
	 */
	public function __construct( array $items ) {
		$this->items = array_values( $items );
	}

	/**
	 * Return loan request records.
	 *
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}
}
