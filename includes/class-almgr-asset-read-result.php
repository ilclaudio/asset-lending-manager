<?php
/**
 * Normalized result for shared asset read operations.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Carries shared asset records and pagination metadata to channel adapters.
 */
class ALMGR_Asset_Read_Result {

	/**
	 * Asset records returned by the read service.
	 *
	 * @var object[]
	 */
	private $items;

	/**
	 * Total matching assets.
	 *
	 * @var int
	 */
	private $total;

	/**
	 * Current page number.
	 *
	 * @var int
	 */
	private $page;

	/**
	 * Requested items per page.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Total number of result pages.
	 *
	 * @var int
	 */
	private $pages;

	/**
	 * Constructor.
	 *
	 * @param object[] $items    Shared asset records.
	 * @param int      $total    Total matching assets.
	 * @param int      $page     Current page number.
	 * @param int      $per_page Requested items per page.
	 * @param int      $pages    Total result pages.
	 */
	public function __construct( array $items, $total, $page, $per_page, $pages ) {
		$this->items    = array_values( $items );
		$this->total    = max( 0, (int) $total );
		$this->page     = max( 1, (int) $page );
		$this->per_page = (int) $per_page;
		$this->pages    = max( 0, (int) $pages );
	}

	/**
	 * Return shared asset records.
	 *
	 * @return object[]
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Return total matching assets.
	 *
	 * @return int
	 */
	public function get_total() {
		return $this->total;
	}

	/**
	 * Return current page number.
	 *
	 * @return int
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * Return requested items per page.
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
