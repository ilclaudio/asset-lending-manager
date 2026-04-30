<?php
/**
 * REST API Manager for Asset Lending Manager plugin.
 *
 * Implements a read-only JSON API for ALMGR assets and members via native
 * WordPress REST API routes under /wp-json/almgr/v1/. Authentication is fully
 * delegated to WordPress core (cookie session, REST nonce, Application Passwords).
 * No custom login logic is used.
 *
 * Endpoints:
 *   GET /wp-json/almgr/v1/assets                    Paginated asset list      (almgr_view_assets)
 *   GET /wp-json/almgr/v1/assets/{id}               Single asset detail       (almgr_view_asset)
 *   GET /wp-json/almgr/v1/members                   Paginated ALMGR user list  (almgr_edit_asset)
 *   GET /wp-json/almgr/v1/members/{member_id}/assets Assets held by a member   (almgr_edit_asset)
 *
 * Response fields differ by caller capability:
 *   - All authenticated users: public asset fields and ACF fields.
 *   - Operators (almgr_edit_asset): additionally cost, data_acquisto, notes,
 *     loan history (detail endpoint), and the /members endpoint.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages the ALMGR read-only JSON API endpoints.
 */
class ALMGR_REST_Manager {

	/**
	 * WordPress REST API namespace for all ALMGR routes.
	 *
	 * @var string
	 */
	const API_NAMESPACE = 'almgr/v1';

	/**
	 * Default number of items per page for paginated responses.
	 *
	 * @var int
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Maximum number of items per page accepted from callers.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * Settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Loan manager instance, injected for asset history retrieval.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	private $loan_manager;

	// -------------------------------------------------------------------------
	// Lifecycle
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager $settings     Settings manager instance.
	 * @param ALMGR_Loan_Manager     $loan_manager Loan manager instance.
	 */
	public function __construct( ALMGR_Settings_Manager $settings, ALMGR_Loan_Manager $loan_manager ) {
		$this->settings     = $settings;
		$this->loan_manager = $loan_manager;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	/**
	 * Register all ALMGR REST API routes.
	 *
	 * Called on rest_api_init. Routes are available under /wp-json/almgr/v1/.
	 * WordPress core handles all authentication (cookie, nonce, Application Passwords).
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::API_NAMESPACE,
			'/assets',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_assets' ),
				'permission_callback' => array( $this, 'can_view_assets' ),
				'args'                => $this->get_assets_args(),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/assets/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_asset' ),
				'permission_callback' => array( $this, 'can_view_asset' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/members',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_members' ),
				'permission_callback' => array( $this, 'can_edit_asset' ),
				'args'                => $this->get_members_args(),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/members/(?P<member_id>\d+)/assets',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_member_assets' ),
				'permission_callback' => array( $this, 'can_edit_asset' ),
				'args'                => array(
					'member_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * Permission callback for endpoints requiring almgr_view_assets.
	 *
	 * @return true|WP_Error
	 */
	public function can_view_assets() {
		if ( ! $this->settings->get( 'rest_api.enabled', true ) ) {
			return new WP_Error(
				'almgr_api_disabled',
				__( 'The ALM API is disabled.', 'asset-lending-manager' ),
				array( 'status' => 503 )
			);
		}
		if ( ! current_user_can( ALMGR_VIEW_ASSETS ) ) {
			return new WP_Error(
				'almgr_forbidden',
				__( 'You do not have permission to view assets.', 'asset-lending-manager' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Permission callback for endpoints requiring almgr_view_asset.
	 *
	 * @return true|WP_Error
	 */
	public function can_view_asset() {
		if ( ! $this->settings->get( 'rest_api.enabled', true ) ) {
			return new WP_Error(
				'almgr_api_disabled',
				__( 'The ALM API is disabled.', 'asset-lending-manager' ),
				array( 'status' => 503 )
			);
		}
		if ( ! current_user_can( ALMGR_VIEW_ASSET ) ) {
			return new WP_Error(
				'almgr_forbidden',
				__( 'You do not have permission to view assets.', 'asset-lending-manager' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Permission callback for endpoints requiring almgr_edit_asset.
	 *
	 * @return true|WP_Error
	 */
	public function can_edit_asset() {
		if ( ! $this->settings->get( 'rest_api.enabled', true ) ) {
			return new WP_Error(
				'almgr_api_disabled',
				__( 'The ALM API is disabled.', 'asset-lending-manager' ),
				array( 'status' => 503 )
			);
		}
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			return new WP_Error(
				'almgr_forbidden',
				__( 'You do not have permission to access this resource.', 'asset-lending-manager' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Args schemas
	// -------------------------------------------------------------------------

	/**
	 * Return the args schema for GET /assets.
	 *
	 * @return array
	 */
	private function get_assets_args() {
		return array(
			'page'      => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'  => array(
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'search'    => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'state'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'      => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'structure' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'owner'     => array(
				'type'              => 'integer',
				'default'           => 0,
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Return the args schema for GET /members.
	 *
	 * @return array
	 */
	private function get_members_args() {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'role'     => array(
				'type'              => 'string',
				'default'           => '',
				'enum'              => array( '', ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	// -------------------------------------------------------------------------
	// Endpoint handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle GET /wp-json/almgr/v1/assets — paginated asset list.
	 *
	 * Query parameters: page, per_page, search, state, type, structure, owner.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_assets( WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( (int) $request->get_param( 'per_page' ) );

		$args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'fields'         => 'ids',
		);

		$search = (string) $request->get_param( 'search' );
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$tax_query = array();
		$tax_map   = array(
			'state'     => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			'type'      => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			'structure' => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
		);
		foreach ( $tax_map as $param => $taxonomy ) {
			$value = (string) $request->get_param( $param );
			if ( '' !== $value ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $value,
				);
			}
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$owner = (int) $request->get_param( 'owner' );
		if ( $owner > 0 ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_almgr_current_owner',
					'value' => $owner,
				),
			);
		}

		$query = new WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$prepared = $this->prepare_asset( (int) $post_id, 'list' );
			if ( null !== $prepared ) {
				$items[] = $prepared;
			}
		}

		$total       = (int) $query->found_posts;
		$total_pages = (int) $query->max_num_pages;

		$response = new WP_REST_Response(
			array(
				'data'  => $items,
				'total' => $total,
				'pages' => $total_pages,
			),
			200
		);
		$response->header( 'X-ALM-Total', $total );
		$response->header( 'X-ALM-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Handle GET /wp-json/almgr/v1/assets/{id} — single asset detail.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_asset( WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$data = $this->prepare_asset( $id, 'detail' );

		if ( null === $data ) {
			return new WP_Error(
				'almgr_not_found',
				__( 'Asset not found.', 'asset-lending-manager' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Handle GET /wp-json/almgr/v1/members — paginated ALMGR user list (operator only).
	 *
	 * Query parameters: page, per_page, search, role.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_members( WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( (int) $request->get_param( 'per_page' ) );
		$offset   = ( $page - 1 ) * $per_page;

		$query_args = array(
			'role__in'    => array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
			'number'      => $per_page,
			'offset'      => $offset,
			'orderby'     => 'display_name',
			'order'       => 'ASC',
			'count_total' => true,
		);

		$search = (string) $request->get_param( 'search' );
		if ( '' !== $search ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$role_filter = (string) $request->get_param( 'role' );
		if ( in_array( $role_filter, array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ), true ) ) {
			$query_args['role__in'] = array( $role_filter );
		}

		$user_query  = new WP_User_Query( $query_args );
		$total       = (int) $user_query->get_total();
		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		$users       = $user_query->get_results();
		$user_ids    = wp_list_pluck( $users, 'ID' );
		$loan_counts = $this->batch_active_loan_counts( $user_ids );

		$items = array();
		foreach ( $users as $user ) {
			$items[] = $this->prepare_member( $user, $loan_counts );
		}

		$response = new WP_REST_Response(
			array(
				'data'  => $items,
				'total' => $total,
				'pages' => $total_pages,
			),
			200
		);
		$response->header( 'X-ALM-Total', $total );
		$response->header( 'X-ALM-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Handle GET /wp-json/almgr/v1/members/{member_id}/assets — assets held by a member.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_member_assets( WP_REST_Request $request ) {
		$member_id = (int) $request->get_param( 'member_id' );

		$user = get_userdata( $member_id );
		if ( ! $user ) {
			return new WP_Error(
				'almgr_not_found',
				__( 'Member not found.', 'asset-lending-manager' ),
				array( 'status' => 404 )
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => ALMGR_ASSET_CPT_SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_almgr_current_owner',
						'value' => $member_id,
					),
				),
			)
		);

		$items = array();
		foreach ( $query->posts as $post_id ) {
			$items[] = $this->prepare_member_asset( (int) $post_id );
		}

		return new WP_REST_Response(
			array(
				'member_id' => $member_id,
				'total'     => count( $items ),
				'data'      => $items,
			),
			200
		);
	}

	// -------------------------------------------------------------------------
	// Data preparation
	// -------------------------------------------------------------------------

	/**
	 * Build the JSON-safe array representation of an asset.
	 *
	 * Context 'list' returns the lightweight shape used in paginated lists.
	 * Context 'detail' additionally includes content, ACF fields, kit
	 * components, parent kits, and (for operators) cost, purchase date, notes,
	 * and loan history.
	 *
	 * @param int    $post_id Asset post ID.
	 * @param string $context 'list' or 'detail'.
	 * @return array|null Asset data array, or null when the asset is invalid.
	 */
	private function prepare_asset( $post_id, $context = 'list' ) {
		$wrapper = ALMGR_Asset_Manager::get_asset_wrapper( $post_id );
		if ( null === $wrapper ) {
			return null;
		}

		// Fetch taxonomy slugs directly. get_the_terms() results are cached by
		// WordPress, so these calls do not add extra DB queries after get_asset_wrapper().
		$structure_terms = get_the_terms( $post_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );
		$type_terms      = get_the_terms( $post_id, ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
		$level_terms     = get_the_terms( $post_id, ALMGR_ASSET_LEVEL_TAXONOMY_SLUG );

		$owner_data = $wrapper->owner_id > 0 ? get_userdata( $wrapper->owner_id ) : false;
		$data       = array(
			'id'             => $post_id,
			'code'           => ALMGR_Asset_Manager::get_asset_code( $post_id ),
			'title'          => $wrapper->title,
			'permalink'      => $wrapper->permalink,
			'thumbnail_url'  => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : null,
			'structure'      => ( $structure_terms && ! is_wp_error( $structure_terms ) ) ? wp_list_pluck( $structure_terms, 'slug' ) : array(),
			'type'           => ( $type_terms && ! is_wp_error( $type_terms ) ) ? wp_list_pluck( $type_terms, 'slug' ) : array(),
			'state'          => $wrapper->almgr_state_slugs ?? array(),
			'level'          => ( $level_terms && ! is_wp_error( $level_terms ) ) ? wp_list_pluck( $level_terms, 'slug' ) : array(),
			'owner_id'       => $wrapper->owner_id,
			'owner_name'     => $wrapper->owner_name,
			'owner_username' => $owner_data ? $owner_data->user_login : '',
		);

		if ( 'detail' !== $context ) {
			return $data;
		}

		// Post content (HTML stripped for API consumers).
		$data['content'] = wp_strip_all_tags( $wrapper->content );

		// Kit/component relationships.
		$data['parent_kits'] = $wrapper->parent_kits ?? array();

		$raw_components     = ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_components', $post_id );
		$data['components'] = array();
		if ( is_array( $raw_components ) ) {
			foreach ( $raw_components as $component ) {
				$cid = is_object( $component ) ? $component->ID : (int) $component;
				if ( $cid > 0 ) {
					$data['components'][] = array(
						'id'        => $cid,
						'title'     => get_the_title( $cid ),
						'permalink' => get_permalink( $cid ),
					);
				}
			}
		}

		// ACF fields visible to all authenticated users.
		// Keys use the prefixed internal name (almgr_*) to read from ACF,
		// but are exposed in the response without the prefix for API stability.
		$acf             = ALMGR_ACF_Asset_Adapter::get_custom_fields( $post_id );
		$public_acf_keys = array(
			'almgr_manufacturer',
			'almgr_model',
			'almgr_serial_number',
			'almgr_external_code',
			'almgr_location',
			'almgr_dimensions',
			'almgr_weight',
			'almgr_user_manual',
			'almgr_technical_data_sheet',
		);
		foreach ( $public_acf_keys as $key ) {
			$response_key          = str_replace( 'almgr_', '', $key );
			$data[ $response_key ] = isset( $acf[ $key ] ) ? ( isset( $acf[ $key ]['value'] ) ? $acf[ $key ]['value'] : null ) : null;
		}

		// Operator-only fields: cost, purchase date, notes, and loan history.
		if ( $this->is_operator() ) {
			$operator_keys = array( 'almgr_cost', 'almgr_data_acquisto', 'almgr_notes' );
			foreach ( $operator_keys as $key ) {
				$response_key          = str_replace( 'almgr_', '', $key );
				$data[ $response_key ] = isset( $acf[ $key ] ) ? ( isset( $acf[ $key ]['value'] ) ? $acf[ $key ]['value'] : null ) : null;
			}

			// Last 10 history entries. Current user is operator so get_asset_history
			// returns all records regardless of the user_id argument.
			$history_rows    = $this->loan_manager->get_asset_history( $post_id, 0 );
			$data['history'] = array();
			foreach ( $history_rows as $row ) {
				$changed_by_user   = $row->changed_by > 0 ? get_userdata( (int) $row->changed_by ) : false;
				$data['history'][] = array(
					'status'          => $row->status,
					'changed_at'      => mysql2date( 'c', $row->changed_at ),
					'changed_by_name' => $changed_by_user ? $changed_by_user->display_name : '',
					'message'         => (string) $row->message,
				);
			}
		}

		return $data;
	}

	/**
	 * Build the JSON-safe array representation of an ALMGR user.
	 *
	 * @param WP_User $user        WordPress user object.
	 * @param array   $loan_counts Optional pre-fetched map of user_id => active loan count.
	 *                             When provided, avoids an extra per-user query.
	 * @return array
	 */
	private function prepare_member( WP_User $user, array $loan_counts = array() ) {
		$role_map    = array(
			ALMGR_MEMBER_ROLE   => 'member',
			ALMGR_OPERATOR_ROLE => 'operator',
		);
		$almgr_roles = array();
		foreach ( (array) $user->roles as $role ) {
			if ( isset( $role_map[ $role ] ) ) {
				$almgr_roles[] = $role_map[ $role ];
			}
		}

		$active_loans = isset( $loan_counts[ $user->ID ] ) ? (int) $loan_counts[ $user->ID ] : $this->count_active_loans( $user->ID );

		return array(
			'id'                 => $user->ID,
			'display_name'       => $user->display_name,
			'email'              => $user->user_email,
			'almgr_roles'        => $almgr_roles,
			'active_loans_count' => $active_loans,
		);
	}

	/**
	 * Build the lightweight asset shape for the member assets endpoint.
	 *
	 * Returns only the fields relevant for "what does this member currently hold":
	 * identification (id, code, title), category (structure, type),
	 * physical info (external_code, location), and navigation (thumbnail_url, permalink).
	 * State is omitted — assets returned by this endpoint are always on-loan.
	 *
	 * @param int $post_id Asset post ID.
	 * @return array
	 */
	private function prepare_member_asset( $post_id ) {
		$wrapper      = ALMGR_Asset_Manager::get_asset_wrapper( $post_id );
		$type_terms   = get_the_terms( $post_id, ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
		$struct_terms = get_the_terms( $post_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );

		return array(
			'id'            => $post_id,
			'code'          => ALMGR_Asset_Manager::get_asset_code( $post_id ),
			'title'         => $wrapper ? $wrapper->title : get_the_title( $post_id ),
			'structure'     => ( $struct_terms && ! is_wp_error( $struct_terms ) ) ? wp_list_pluck( $struct_terms, 'slug' ) : array(),
			'type'          => ( $type_terms && ! is_wp_error( $type_terms ) ) ? wp_list_pluck( $type_terms, 'slug' ) : array(),
			'external_code' => (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_external_code', $post_id ),
			'location'      => (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_location', $post_id ),
			'thumbnail_url' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : null,
			'permalink'     => get_permalink( $post_id ),
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Return true when the current user has operator or administrator privileges.
	 *
	 * @return bool
	 */
	private function is_operator() {
		return current_user_can( ALMGR_EDIT_ASSET );
	}

	/**
	 * Clamp a per_page value to the valid range [1, MAX_PER_PAGE].
	 *
	 * @param int $value Requested value.
	 * @return int
	 */
	private function clamp_per_page( $value ) {
		return min( self::MAX_PER_PAGE, max( 1, (int) $value ) );
	}

	/**
	 * Return a map of active-loan counts for multiple users in a single query.
	 *
	 * Replaces N individual count_active_loans() calls with one grouped $wpdb query,
	 * eliminating the N+1 pattern in get_members().
	 *
	 * @param int[] $user_ids Array of WordPress user IDs.
	 * @return array<int,int> Map of user_id => active loan count. Missing users have count 0.
	 */
	private function batch_active_loan_counts( array $user_ids ) {
		if ( empty( $user_ids ) ) {
			return array();
		}

		global $wpdb;

		$safe_ids  = implode( ',', array_map( 'absint', $user_ids ) );
		$asset_cpt = ALMGR_ASSET_CPT_SLUG;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS owner_id, COUNT(*) AS loan_count
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				  AND pm.meta_value IN ( {$safe_ids} )
				  AND p.post_status = %s
				  AND p.post_type = %s
				GROUP BY pm.meta_value",
				'_almgr_current_owner',
				'publish',
				$asset_cpt
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (int) $row->owner_id ] = (int) $row->loan_count;
		}

		return $counts;
	}

	/**
	 * Count assets currently assigned to a given user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	private function count_active_loans( $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => ALMGR_ASSET_CPT_SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_almgr_current_owner',
						'value' => $user_id,
					),
				),
			)
		);
		return (int) $query->found_posts;
	}
}
