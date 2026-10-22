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
	/**
	 * Catalog filters exposed by the REST API and the Abilities.
	 *
	 * Single definition read by both adapters (argument schema, input schema and
	 * request-to-filter mapping). The frontend also supports `level`, which is not
	 * part of the API contract. Each entry holds `type` and `default`, plus the
	 * optional `minimum`, `max_length`, `taxonomy` (term must exist) and
	 * `description` keys.
	 *
	 * @return array[]
	 */
	public static function get_api_filter_definitions() {
		return array(
			'search'               => array(
				'type'       => 'string',
				'default'    => '',
				'max_length' => 200,
			),
			'state'                => array(
				'type'     => 'string',
				'default'  => '',
				'taxonomy' => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			),
			'type'                 => array(
				'type'     => 'string',
				'default'  => '',
				'taxonomy' => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			),
			'structure'            => array(
				'type'     => 'string',
				'default'  => '',
				'taxonomy' => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
			),
			'owner'                => array(
				'type'    => 'integer',
				'default' => 0,
				'minimum' => 0,
			),
			'warehouse'            => array(
				'type'        => 'string',
				'default'     => '',
				'description' => __( 'Home warehouse slug. Available only while warehouse management is enabled. Cannot be combined with warehouse_unassigned.', 'asset-lending-manager' ),
			),
			'warehouse_unassigned' => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'When true, return only assets with no home warehouse. Available only while warehouse management is enabled. Cannot be combined with warehouse.', 'asset-lending-manager' ),
			),
		);
	}

	/**
	 * Query published assets with the supported catalog filters.
	 *
	 * @param array $filters Query filters.
	 * @return ALMGR_Asset_Query_Result|WP_Error
	 */
	public function get_assets( array $filters = array() ) {
		$warehouse = $this->resolve_warehouse_filter( $filters );
		if ( is_wp_error( $warehouse ) ) {
			return $warehouse;
		}

		$page               = ALMGR_Pagination::normalize_page( $filters['page'] ?? 1 );
		$requested_per_page = $filters['per_page'] ?? ALMGR_Pagination::MAX_PER_PAGE;
		$per_page           = ALMGR_Pagination::normalize_per_page( $requested_per_page, true );
		$search             = isset( $filters['search'] ) ? sanitize_text_field( (string) $filters['search'] ) : '';
		$search             = mb_substr( $search, 0, 200 );

		$query_args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'fields'         => 'ids',
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

		if ( $warehouse['unassigned'] ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG,
				'operator' => 'NOT EXISTS',
			);
		} elseif ( '' !== $warehouse['slug'] ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $warehouse['slug'],
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

		$query = new WP_Query( $query_args );

		return new ALMGR_Asset_Query_Result(
			$query->posts,
			$query->found_posts,
			$page,
			$per_page,
			$query->max_num_pages
		);
	}

	/**
	 * Validate and normalize the warehouse filters.
	 *
	 * Requesting either warehouse filter while warehouse management is disabled
	 * is an error, not a silent no-op, so a client cannot infer anything from a
	 * response that merely looks unfiltered. Every channel gets the same error
	 * from this single decision point.
	 *
	 * @param array $filters Query filters.
	 * @return array|WP_Error Array with `slug` (string) and `unassigned` (bool).
	 */
	private function resolve_warehouse_filter( array $filters ) {
		$raw_slug   = isset( $filters['warehouse'] ) ? trim( (string) $filters['warehouse'] ) : '';
		$unassigned = filter_var( $filters['warehouse_unassigned'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( '' === $raw_slug && ! $unassigned ) {
			return array(
				'slug'       => '',
				'unassigned' => false,
			);
		}

		if ( ! ALMGR_Asset_Manager::is_warehouse_enabled() ) {
			return new WP_Error(
				'almgr_warehouse_disabled',
				__( 'Warehouse filtering is not available.', 'asset-lending-manager' ),
				array( 'status' => 400 )
			);
		}

		if ( '' !== $raw_slug && $unassigned ) {
			return new WP_Error(
				'almgr_invalid_warehouse_filter',
				__( 'The warehouse and warehouse_unassigned filters cannot be combined.', 'asset-lending-manager' ),
				array( 'status' => 400 )
			);
		}

		if ( $unassigned ) {
			return array(
				'slug'       => '',
				'unassigned' => true,
			);
		}

		$slug = sanitize_title( $raw_slug );
		if ( '' === $slug || ! term_exists( $slug, ALMGR_ASSET_WAREHOUSE_TAXONOMY_SLUG ) ) {
			return new WP_Error(
				'almgr_warehouse_not_found',
				__( 'Warehouse not found.', 'asset-lending-manager' ),
				array( 'status' => 400 )
			);
		}

		return array(
			'slug'       => $slug,
			'unassigned' => false,
		);
	}
}
