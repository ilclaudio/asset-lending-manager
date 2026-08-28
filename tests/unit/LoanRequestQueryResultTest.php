<?php
/**
 * Unit tests for the normalized loan request query result.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies the shared loan request result contract.
 */
class ALMGR_Loan_Request_Query_Result_Unit_Test extends TestCase {

	/**
	 * Verify request records are exposed consistently.
	 *
	 * @return void
	 */
	public function test_result_exposes_items(): void {
		$items  = array( (object) array( 'status' => 'pending' ) );
		$result = new ALMGR_Loan_Request_Query_Result( $items, 5, 2, 2, 3 );

		$this->assertSame( $items, $result->get_items() );
		$this->assertSame( 5, $result->get_total() );
		$this->assertSame( 2, $result->get_page() );
		$this->assertSame( 2, $result->get_per_page() );
		$this->assertSame( 3, $result->get_pages() );
	}
}
