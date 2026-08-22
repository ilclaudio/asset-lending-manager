<?php
/**
 * Unit tests for the normalized asset history result.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies the shared history result contract without a WordPress database.
 */
class ALMGR_Asset_History_Result_Unit_Test extends TestCase {

	/**
	 * Verify history rows and pagination metadata are exposed consistently.
	 *
	 * @return void
	 */
	public function test_result_exposes_items_and_pagination_metadata(): void {
		$rows   = array( (object) array( 'status' => 'approved' ) );
		$result = new ALMGR_Asset_History_Result( $rows, 21, 2, 10, 3 );

		$this->assertSame( $rows, $result->get_items() );
		$this->assertSame( 21, $result->get_total() );
		$this->assertSame( 2, $result->get_page() );
		$this->assertSame( 10, $result->get_per_page() );
		$this->assertSame( 3, $result->get_pages() );
	}
}
