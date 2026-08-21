<?php
/**
 * Shared asset catalog query service.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides the shared asset catalog query used by frontend and API adapters.
 */
class ALMGR_Asset_Query_Service {

	/**
	 * Maximum number of catalog items returned in a page.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * Query published assets with the supported catalog filters.
	 *
	 * @param array  $filters Query filters.
	 * @param string $fields  WordPress query fields value.
	 * @return WP_Query
	 */
	public function get_assets( array $filters = array(), $fields = 'all' ) {
		$page               = max( 1, absint( $filters['page'] ?? 1 ) );
		$requested_per_page = isset( $filters['per_page'] ) ? (int) $filters['per_page'] : self::MAX_PER_PAGE;
		$per_page           = -1 === $requested_per_page ? -1 : min( self::MAX_PER_PAGE, max( 1, absint( $requested_per_page ) ) );
		$search             = isset( $filters['search'] ) ? sanitize_text_field( (string) $filters['search'] ) : '';
		$search             = mb_substr( $search, 0, 200 );

		$query_args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'fields'         => 'ids' === $fields ? 'ids' : 'all',
		);

		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		$tax_map   = array(
			'state'     => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			'type'      => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			'structure' => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
			'level'     => ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
		);
		$tax_query = array( 'relation' => 'AND' );

		foreach ( $tax_map as $filter_key => $taxonomy ) {
			$value = isset( $filters[ $filter_key ] ) ? sanitize_title( (string) $filters[ $filter_key ] ) : '';
			if ( '' === $value ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $value,
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Catalog taxonomy filters are an intentional, paginated feature.
		}

		$owner_id = absint( $filters['owner'] ?? 0 );
		if ( $owner_id > 0 ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Current-owner filtering is a documented, paginated catalog feature.
				array(
					'key'     => '_almgr_current_owner',
					'value'   => $owner_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			);
		}

		return new WP_Query( $query_args );
	}
}
