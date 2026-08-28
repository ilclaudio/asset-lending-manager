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
	 * Total matching requests.
	 *
	 * @var int
	 */
	private $total;

	/**
	 * Current page.
	 *
	 * @var int
	 */
	private $page;

	/**
	 * Requests per page.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Total result pages.
	 *
	 * @var int
	 */
	private $pages;

	/**
	 * Constructor.
	 *
	 * @param array $items    Loan request records.
	 * @param int   $total    Total matching requests.
	 * @param int   $page     Current page.
	 * @param int   $per_page Requests per page.
	 * @param int   $pages    Total result pages.
	 */
	public function __construct( array $items, $total = null, $page = 1, $per_page = 0, $pages = null ) {
		$this->items    = array_values( $items );
		$this->total    = null === $total ? count( $this->items ) : max( 0, (int) $total );
		$this->page     = max( 1, (int) $page );
		$this->per_page = max( 0, (int) $per_page );
		$this->pages    = null === $pages
			? ( $this->per_page > 0 ? (int) ceil( $this->total / $this->per_page ) : ( $this->total > 0 ? 1 : 0 ) )
			: max( 0, (int) $pages );
	}

	/**
	 * Return loan request records.
	 *
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Return total matching requests.
	 *
	 * @return int
	 */
	public function get_total() {
		return $this->total;
	}

	/**
	 * Return current page.
	 *
	 * @return int
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * Return requests per page.
	 *
	 * @return int
	 */
	public function get_per_page() {
		return $this->per_page;
	}

	/**
	 * Return total result pages.
	 *
	 * @return int
	 */
	public function get_pages() {
		return $this->pages;
	}
}
