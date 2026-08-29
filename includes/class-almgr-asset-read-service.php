<?php
/**
 * Shared asset read service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the shared asset records consumed by frontend and REST adapters.
 */
class ALMGR_Asset_Read_Service {

	/**
	 * Shared asset catalog query service.
	 *
	 * @var ALMGR_Asset_Query_Service
	 */
	private $asset_queries;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Asset_Query_Service $asset_queries Shared asset catalog query service.
	 */
	public function __construct( ALMGR_Asset_Query_Service $asset_queries ) {
		$this->asset_queries = $asset_queries;
	}

	/**
	 * Return shared asset records for catalog filters.
	 *
	 * @param array $filters Catalog filters and pagination.
	 * @return ALMGR_Asset_Read_Result
	 */
	public function get_assets( array $filters = array() ) {
		$query_result = $this->asset_queries->get_assets( $filters );
		$items        = array();

		foreach ( $query_result->get_items() as $asset_id ) {
			$asset = $this->get_asset( $asset_id );
			if ( null !== $asset ) {
				$items[] = $asset;
			}
		}

		return new ALMGR_Asset_Read_Result(
			$items,
			$query_result->get_total(),
			$query_result->get_page(),
			$query_result->get_per_page(),
			$query_result->get_pages()
		);
	}

	/**
	 * Return one shared asset record.
	 *
	 * The returned wrapper is the canonical read model for the current asset
	 * catalog. Channel adapters may project it, but must not re-query the asset
	 * or rebuild its common fields independently.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return object|null
	 */
	public function get_asset( $asset_id ) {
		$asset_id = absint( $asset_id );
		$asset    = ALMGR_Asset_Manager::get_asset_wrapper( $asset_id );

		if ( ! $asset ) {
			return null;
		}

		$asset->code            = ALMGR_Asset_Manager::get_asset_code( $asset_id );
		$asset->thumbnail_url   = get_the_post_thumbnail_url( $asset_id, 'thumbnail' ) ? get_the_post_thumbnail_url( $asset_id, 'thumbnail' ) : null;
		$asset->structure_slugs = $this->get_term_slugs( $asset_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );
		$asset->type_slugs      = $this->get_term_slugs( $asset_id, ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
		$asset->level_slugs     = $this->get_term_slugs( $asset_id, ALMGR_ASSET_LEVEL_TAXONOMY_SLUG );
		$asset->warehouse_slugs = ALMGR_Asset_Manager::is_warehouse_enabled()
			? $this->get_term_slugs( $asset_id, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG )
			: array();
		$asset->external_code   = (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_external_code', $asset_id );
		$asset->location        = (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_location', $asset_id );
		$owner_data             = $asset->owner_id > 0 ? get_userdata( $asset->owner_id ) : false;
		$asset->owner_username  = $owner_data ? $owner_data->user_login : '';

		return $asset;
	}

	/**
	 * Return term slugs assigned to an asset.
	 *
	 * @param int    $asset_id Asset post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string[]
	 */
	private function get_term_slugs( $asset_id, $taxonomy ) {
		$terms = get_the_terms( $asset_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return array_values( array_map( 'strval', wp_list_pluck( $terms, 'slug' ) ) );
	}
}
