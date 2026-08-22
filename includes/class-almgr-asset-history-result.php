<?php
/**
 * Shared asset history result.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalized paginated result for asset history reads.
 */
class ALMGR_Asset_History_Result {

	/**
	 * History row objects.
	 *
	 * @var array
	 */
	private $items;

	/**
	 * Total visible rows.
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
	 * Rows per page.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Total pages.
	 *
	 * @var int
	 */
	private $pages;

	/**
	 * Constructor.
	 *
	 * @param array $items    History row objects.
	 * @param int   $total    Total visible rows.
	 * @param int   $page     Current page.
	 * @param int   $per_page Rows per page.
	 * @param int   $pages    Total pages.
	 */
	public function __construct( array $items, $total, $page, $per_page, $pages ) {
		$this->items    = $items;
		$this->total    = max( 0, (int) $total );
		$this->page     = max( 1, (int) $page );
		$this->per_page = max( 1, (int) $per_page );
		$this->pages    = max( 0, (int) $pages );
	}

	/**
	 * Return history rows.
	 *
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Return total visible rows.
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
	 * Return rows per page.
	 *
	 * @return int
	 */
	public function get_per_page() {
		return $this->per_page;
	}

	/**
	 * Return total pages.
	 *
	 * @return int
	 */
	public function get_pages() {
		return $this->pages;
	}
}
