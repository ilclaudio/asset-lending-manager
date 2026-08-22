<?php
/**
 * Unit tests for the normalized asset query result.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies the shared result contract without a WordPress database.
 */
class ALMGR_Asset_Query_Result_Unit_Test extends TestCase {

	/**
	 * Verify IDs and pagination metadata are normalized and exposed consistently.
	 *
	 * @return void
	 */
	public function test_result_normalizes_items_and_exposes_pagination_metadata(): void {
		$result = new ALMGR_Asset_Query_Result( array( '12', 0, '-4', 27 ), 27, 2, 10, 3 );

		$this->assertSame( array( 12, 0, 4, 27 ), $result->get_items() );
		$this->assertSame( 27, $result->get_total() );
		$this->assertSame( 2, $result->get_page() );
		$this->assertSame( 10, $result->get_per_page() );
		$this->assertSame( 3, $result->get_pages() );
	}
}
