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

	/**
	 * Regression test: a non-operator caller must never receive the
	 * operator-only ACF fields (cost, purchase date, notes) — this is the
	 * exact filter that closes the almgr/get-asset Ability data-exposure gap.
	 *
	 * @return void
	 */
	public function test_filter_acf_fields_removes_operator_only_keys_for_non_operators(): void {
		$acf_fields = array(
			'almgr_manufacturer'  => array( 'value' => 'Acme' ),
			'almgr_cost'          => array( 'value' => '999.99' ),
			'almgr_data_acquisto' => array( 'value' => '2026-01-01' ),
			'almgr_notes'         => array( 'value' => 'Internal note.' ),
		);

		$filtered = ALMGR_Asset_Projection_Service::filter_acf_fields( $acf_fields, false );

		$this->assertArrayHasKey( 'almgr_manufacturer', $filtered );
		$this->assertArrayNotHasKey( 'almgr_cost', $filtered );
		$this->assertArrayNotHasKey( 'almgr_data_acquisto', $filtered );
		$this->assertArrayNotHasKey( 'almgr_notes', $filtered );
	}

	/**
	 * Verify an operator caller receives every ACF field unfiltered.
	 *
	 * @return void
	 */
	public function test_filter_acf_fields_keeps_all_keys_for_operators(): void {
		$acf_fields = array(
			'almgr_manufacturer' => array( 'value' => 'Acme' ),
			'almgr_cost'         => array( 'value' => '999.99' ),
			'almgr_notes'        => array( 'value' => 'Internal note.' ),
		);

		$this->assertSame( $acf_fields, ALMGR_Asset_Projection_Service::filter_acf_fields( $acf_fields, true ) );
	}
}
