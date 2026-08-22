<?php
/**
 * Shared asset detail read service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the shared detail record consumed by frontend and REST adapters.
 */
class ALMGR_Asset_Detail_Service {

	/**
	 * Shared asset read service.
	 *
	 * @var ALMGR_Asset_Read_Service
	 */
	private $asset_reads;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Asset_Read_Service $asset_reads Shared asset read service.
	 */
	public function __construct( ALMGR_Asset_Read_Service $asset_reads ) {
		$this->asset_reads = $asset_reads;
	}

	/**
	 * Return the shared detail record for an asset.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return object|null
	 */
	public function get_asset_detail( $asset_id ) {
		$asset = $this->asset_reads->get_asset( $asset_id );

		if ( ! $asset ) {
			return null;
		}

		$asset->asset_fields      = ALMGR_Asset_Manager::get_asset_custom_fields( $asset->id );
		$asset->acf_fields        = ALMGR_ACF_Asset_Adapter::get_custom_fields( $asset->id );
		$asset->content_html      = (string) apply_filters( 'the_content', get_post_field( 'post_content', $asset->id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applies the core content rendering pipeline to asset descriptions.
		$asset->detail_image_html = has_post_thumbnail( $asset->id )
			? get_the_post_thumbnail( $asset->id, 'large' )
			: (string) $asset->thumbnail;
		$asset->components        = $this->get_components( $asset->id );
		$asset->state_slug        = ! empty( $asset->almgr_state_slugs ) ? (string) $asset->almgr_state_slugs[0] : '';
		$asset->state_label       = $this->get_state_label( $asset );

		return $asset;
	}

	/**
	 * Return component records for a kit.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_components( $asset_id ) {
		$raw_components = ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_components', $asset_id );
		$components     = array();

		if ( ! is_array( $raw_components ) ) {
			return $components;
		}

		foreach ( $raw_components as $component ) {
			$component_id = is_object( $component ) ? (int) $component->ID : absint( $component );
			if ( $component_id <= 0 ) {
				continue;
			}

			$components[] = array(
				'id'        => $component_id,
				'title'     => get_the_title( $component_id ),
				'permalink' => get_permalink( $component_id ),
			);
		}

		return $components;
	}

	/**
	 * Return the translated state label for an asset.
	 *
	 * @param object $asset Shared asset record.
	 * @return string
	 */
	private function get_state_label( $asset ) {
		if ( '' === $asset->state_slug ) {
			return '';
		}

		$state_terms = get_the_terms( $asset->id, ALMGR_ASSET_STATE_TAXONOMY_SLUG );
		$state_name  = '';
		if ( ! empty( $state_terms ) && ! is_wp_error( $state_terms ) ) {
			$state_name = (string) $state_terms[0]->name;
		}

		return ALMGR_Asset_Manager::get_state_label( $asset->state_slug, $state_name );
	}
}
