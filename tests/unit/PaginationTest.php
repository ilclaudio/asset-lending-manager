<?php
/**
 * Unit tests for shared pagination rules.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pagination defaults and bounds used by services and adapters.
 */
class ALMGR_Pagination_Unit_Test extends TestCase {

	/**
	 * Verify page values are normalized to a positive integer.
	 *
	 * @return void
	 */
	public function test_normalize_page_enforces_lower_bound(): void {
		$this->assertSame( 1, ALMGR_Pagination::normalize_page( 0 ) );
		$this->assertSame( 1, ALMGR_Pagination::normalize_page( -5 ) );
		$this->assertSame( 12, ALMGR_Pagination::normalize_page( '12' ) );
	}

	/**
	 * Verify per-page values use the shared default and bounds.
	 *
	 * @return void
	 */
	public function test_normalize_per_page_uses_shared_bounds(): void {
		$this->assertSame( ALMGR_Pagination::DEFAULT_PER_PAGE, ALMGR_Pagination::normalize_per_page() );
		$this->assertSame( 1, ALMGR_Pagination::normalize_per_page( 0 ) );
		$this->assertSame( ALMGR_Pagination::MAX_PER_PAGE, ALMGR_Pagination::normalize_per_page( 999 ) );
	}

	/**
	 * Verify unlimited catalog reads remain an explicit opt-in.
	 *
	 * @return void
	 */
	public function test_normalize_per_page_preserves_unlimited_only_when_allowed(): void {
		$this->assertSame( 1, ALMGR_Pagination::normalize_per_page( -1 ) );
		$this->assertSame( -1, ALMGR_Pagination::normalize_per_page( -1, true ) );
	}
}
