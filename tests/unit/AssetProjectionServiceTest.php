<?php
/**
 * Unit tests for the shared asset projections.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Ensures REST and Abilities have one common implementation for asset shapes.
 */
class ALMGR_Asset_Projection_Service_Unit_Test extends TestCase {

	/**
	 * Verify the common asset projection preserves the transport contract.
	 *
	 * @return void
	 */
	public function test_asset_projection_contains_common_fields(): void {
		$asset = (object) array(
			'id'               => '12',
			'code'             => 'AST-12',
			'title'            => 'Test asset',
			'permalink'        => 'https://example.org/asset',
			'thumbnail_url'    => null,
			'structure_slugs'  => array( 'component' ),
			'type_slugs'       => array( 'generic' ),
			'almgr_state_slugs' => array( 'available' ),
			'level_slugs'      => array( 'basic' ),
			'warehouse_slugs'  => array( 'main-warehouse' ),
			'owner_id'         => '0',
			'owner_name'       => '',
			'owner_username'   => '',
		);

		$this->assertSame(
			array(
				'id'             => 12,
				'code'           => 'AST-12',
				'title'          => 'Test asset',
				'permalink'      => 'https://example.org/asset',
				'thumbnail_url'  => null,
				'structure'      => array( 'component' ),
				'type'           => array( 'generic' ),
				'state'          => array( 'available' ),
				'level'          => array( 'basic' ),
				'warehouse'      => array( 'main-warehouse' ),
				'owner_id'       => 0,
				'owner_name'     => '',
				'owner_username' => '',
			),
			ALMGR_Asset_Projection_Service::asset( $asset )
		);
	}

	/**
	 * Verify the member-held asset projection is shared and stable.
	 *
	 * @return void
	 */
	public function test_member_asset_projection_contains_common_fields(): void {
		$asset = (object) array(
			'id'              => '12',
			'code'            => 'AST-12',
			'title'           => 'Test asset',
			'structure_slugs' => array( 'component' ),
			'type_slugs'      => array( 'generic' ),
			'external_code'   => 42,
			'location'        => 'Shelf A',
			'thumbnail_url'   => null,
			'permalink'       => 'https://example.org/asset',
		);

		$this->assertSame( 12, ALMGR_Asset_Projection_Service::member_asset( $asset )['id'] );
		$this->assertSame( '42', ALMGR_Asset_Projection_Service::member_asset( $asset )['external_code'] );
	}
}
