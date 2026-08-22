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
		$result = new ALMGR_Loan_Request_Query_Result( $items );

		$this->assertSame( $items, $result->get_items() );
	}
}
