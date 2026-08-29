<?php
/**
 * Unit checks for the documented REST/Abilities contracts.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Prevents the most important documented REST defaults and error codes drifting.
 */
class ALMGR_Documentation_Contract_Unit_Test extends TestCase {

	/**
	 * Verify the REST reference contains the implementation's public contract.
	 *
	 * @return void
	 */
	public function test_rest_reference_matches_core_contract(): void {
		$document = file_get_contents( dirname( __DIR__, 2 ) . '/DOC/REST_API_REFERENCE.md' );

		$this->assertIsString( $document );
		$this->assertStringContainsString( '`GET /me/assets`', $document );
		$this->assertStringContainsString( '`almgr_api_disabled`', $document );
		$this->assertStringNotContainsString( '`almgr_rest_disabled`', $document );
		$this->assertStringContainsString( '| `warehouse` | array |', $document );
		$this->assertStringContainsString( '| `owner` | integer | 0 |', $document );
	}
}
