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
 *   GET /wp-json/almgr/v1/me/assets                 Current member assets     (almgr_view_asset)
 *   GET /wp-json/almgr/v1/members                   Paginated ALMGR user list  (almgr_edit_asset)
 *   GET /wp-json/almgr/v1/members/{member_id}/assets Assets held by a member   (almgr_edit_asset)
 *   GET /wp-json/almgr/v1/me/loan-requests         Current user's requests    (almgr_view_asset)
 *   GET /wp-json/almgr/v1/assets/{id}/loan-requests Asset requests            (owner/operator)
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
	 * Settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Shared asset read service.
	 *
	 * @var ALMGR_Asset_Read_Service
	 */
	private $asset_reads;

	/**
	 * Shared asset detail service.
	 *
	 * @var ALMGR_Asset_Detail_Service
	 */
	private $asset_details;

	/**
	 * Shared asset history service.
	 *
	 * @var ALMGR_Asset_History_Service
	 */
	private $asset_history;

	/**
	 * Shared member-held asset service.
	 *
	 * @var ALMGR_Member_Assets_Service
	 */
	private $member_assets;

	/**
	 * Shared loan request query service.
	 *
	 * @var ALMGR_Loan_Request_Query_Service
	 */
	private $request_query;

	/**
	 * Shared record-level access policy.
	 *
	 * @var ALMGR_Access_Policy
	 */
	private $access_policy;

	/**
	 * Shared active-loan count service.
	 *
	 * @var ALMGR_Active_Loan_Count_Service
	 */
	private $active_loan_counts;

	// -------------------------------------------------------------------------
	// Lifecycle
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager           $settings      Settings manager instance.
	 * @param ALMGR_Asset_Read_Service         $asset_reads   Shared asset read service.
	 * @param ALMGR_Asset_Detail_Service       $asset_details Shared asset detail service.
	 * @param ALMGR_Asset_History_Service      $asset_history Shared asset history service.
	 * @param ALMGR_Member_Assets_Service      $member_assets Shared member-held asset service.
	 * @param ALMGR_Loan_Request_Query_Service $request_query Shared request query service.
	 * @param ALMGR_Access_Policy              $access_policy Shared access policy.
	 * @param ALMGR_Active_Loan_Count_Service  $active_loan_counts Shared active-loan count service.
	 */
	public function __construct( ALMGR_Settings_Manager $settings, ALMGR_Asset_Read_Service $asset_reads, ALMGR_Asset_Detail_Service $asset_details, ALMGR_Asset_History_Service $asset_history, ALMGR_Member_Assets_Service $member_assets, $request_query = null, $access_policy = null, $active_loan_counts = null ) {
		$this->settings           = $settings;
		$this->asset_reads        = $asset_reads;
		$this->asset_details      = $asset_details;
		$this->asset_history      = $asset_history;
		$this->member_assets      = $member_assets;
		$this->request_query      = $request_query instanceof ALMGR_Loan_Request_Query_Service ? $request_query : new ALMGR_Loan_Request_Query_Service();
		$this->access_policy      = $access_policy instanceof ALMGR_Access_Policy ? $access_policy : new ALMGR_Access_Policy();
		$this->active_loan_counts = $active_loan_counts instanceof ALMGR_Active_Loan_Count_Service ? $active_loan_counts : new ALMGR_Active_Loan_Count_Service();
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
			'/me/assets',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_assets' ),
				'permission_callback' => array( $this, 'can_view_asset' ),
				'args'                => $this->get_member_assets_list_args(),
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

		register_rest_route(
			self::API_NAMESPACE,
			'/me/loan-requests',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_loan_requests' ),
				'permission_callback' => array( $this, 'can_view_asset' ),
				'args'                => $this->get_loan_requests_args(),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/assets/(?P<id>\d+)/loan-requests',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_asset_loan_requests' ),
				'permission_callback' => array( $this, 'can_view_asset_loan_requests' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'minimum'           => 1,
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_loan_requests_args()
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

	/**
	 * Permission callback for requests belonging to an asset.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return true|WP_Error
	 */
	public function can_view_asset_loan_requests( WP_REST_Request $request ) {
		$base_permission = $this->can_view_asset();
		if ( true !== $base_permission ) {
			return $base_permission;
		}

		if ( ! $this->access_policy->can_view_asset_requests( get_current_user_id(), (int) $request->get_param( 'id' ) ) ) {
			return new WP_Error( 'almgr_forbidden', __( 'You do not have permission to view these loan requests.', 'asset-lending-manager' ), array( 'status' => 403 ) );
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
				'default'           => ALMGR_Pagination::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => ALMGR_Pagination::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'search'    => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return is_string( $value ) && mb_strlen( $value ) <= 200;
				},
			),
			'state'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return '' === $value || (bool) term_exists( $value, ALMGR_ASSET_STATE_TAXONOMY_SLUG );
				},
			),
			'type'      => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return '' === $value || (bool) term_exists( $value, ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
				},
			),
			'structure' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return '' === $value || (bool) term_exists( $value, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );
				},
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
				'default'           => ALMGR_Pagination::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => ALMGR_Pagination::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return is_string( $value ) && mb_strlen( $value ) <= 200;
				},
			),
			'role'     => array(
				'type'              => 'string',
				'default'           => '',
				'enum'              => array( '', ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * Return pagination args for member-held asset collections.
	 *
	 * @return array
	 */
	private function get_member_assets_list_args() {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => ALMGR_Pagination::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => ALMGR_Pagination::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Return arguments for loan request collections.
	 *
	 * @return array
	 */
	private function get_loan_requests_args() {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => ALMGR_Pagination::DEFAULT_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => ALMGR_Pagination::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'status'   => array(
				'type'              => 'string',
				'default'           => '',
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
		$page     = ALMGR_Pagination::normalize_page( $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( (int) $request->get_param( 'per_page' ) );

		$result = $this->asset_reads->get_assets(
			array(
				'page'      => $page,
				'per_page'  => $per_page,
				'search'    => (string) $request->get_param( 'search' ),
				'state'     => (string) $request->get_param( 'state' ),
				'type'      => (string) $request->get_param( 'type' ),
				'structure' => (string) $request->get_param( 'structure' ),
				'owner'     => (int) $request->get_param( 'owner' ),
			)
		);
		$items  = array();
		foreach ( $result->get_items() as $asset ) {
			$prepared = $this->prepare_asset( $asset, 'list' );
			if ( null !== $prepared ) {
				$items[] = $prepared;
			}
		}

		$total       = $result->get_total();
		$total_pages = $result->get_pages();

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
		$data = $this->prepare_asset( $this->asset_details->get_asset_detail( $id ), 'detail' );

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
		$page     = ALMGR_Pagination::normalize_page( $request->get_param( 'page' ) );
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
		$loan_counts = $this->active_loan_counts->count_for_users( $user_ids );

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
		$result    = $this->member_assets->get_assets_for_member(
			get_current_user_id(),
			$member_id,
			array( 'per_page' => -1 )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->prepare_member_assets_response( $member_id, $result );
	}

	/**
	 * Handle GET /wp-json/almgr/v1/me/assets — assets held by the current user.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_my_assets( WP_REST_Request $request ) {
		$member_id = get_current_user_id();
		$result    = $this->member_assets->get_assets_for_member(
			$member_id,
			$member_id,
			array(
				'page'     => ALMGR_Pagination::normalize_page( $request->get_param( 'page' ) ),
				'per_page' => $this->clamp_per_page( (int) $request->get_param( 'per_page' ) ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->prepare_member_assets_response( $member_id, $result, true );
	}

	/**
	 * Handle GET /wp-json/almgr/v1/me/loan-requests.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_my_loan_requests( WP_REST_Request $request ) {
		$result = $this->request_query->get_for_user(
			get_current_user_id(),
			(string) $request->get_param( 'status' ),
			ALMGR_Pagination::normalize_page( $request->get_param( 'page' ) ),
			$this->clamp_per_page( (int) $request->get_param( 'per_page' ) )
		);

		return new WP_REST_Response(
			$this->prepare_loan_requests_response( $result ),
			200
		);
	}

	/**
	 * Handle GET /wp-json/almgr/v1/assets/{id}/loan-requests.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_asset_loan_requests( WP_REST_Request $request ) {
		$result = $this->request_query->get_for_asset(
			(int) $request->get_param( 'id' ),
			(string) $request->get_param( 'status' ),
			ALMGR_Pagination::normalize_page( $request->get_param( 'page' ) ),
			$this->clamp_per_page( (int) $request->get_param( 'per_page' ) )
		);

		return new WP_REST_Response(
			$this->prepare_loan_requests_response( $result ),
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
	 * @param object|null $asset Asset record from the shared read service.
	 * @param string      $context 'list' or 'detail'.
	 * @return array|null Asset data array, or null when the asset is invalid.
	 */
	private function prepare_asset( $asset, $context = 'list' ) {
		$wrapper = $asset;
		$post_id = $wrapper ? (int) $wrapper->id : 0;
		if ( null === $wrapper ) {
			return null;
		}

		$data = array(
			'id'             => $post_id,
			'code'           => $wrapper->code,
			'title'          => $wrapper->title,
			'permalink'      => $wrapper->permalink,
			'thumbnail_url'  => $wrapper->thumbnail_url,
			'structure'      => $wrapper->structure_slugs,
			'type'           => $wrapper->type_slugs,
			'state'          => $wrapper->almgr_state_slugs ?? array(),
			'level'          => $wrapper->level_slugs,
			'owner_id'       => $wrapper->owner_id,
			'owner_name'     => $wrapper->owner_name,
			'owner_username' => $wrapper->owner_username,
		);

		if ( 'detail' !== $context ) {
			return $data;
		}

		// Post content (HTML stripped for API consumers).
		$data['content'] = wp_strip_all_tags( $wrapper->content_html );

		// Kit/component relationships.
		$data['parent_kits'] = $wrapper->parent_kits ?? array();

		$data['components'] = $wrapper->components ?? array();

		// ACF fields visible to all authenticated users.
		// Keys use the prefixed internal name (almgr_*) to read from ACF,
		// but are exposed in the response without the prefix for API stability.
		$acf             = $wrapper->acf_fields ?? array();
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
			$history_result  = $this->asset_history->get_history( $post_id, 0, 10, 1 );
			$history_rows    = $history_result->get_items();
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
	 * Build the shared JSON representation of a loan request.
	 *
	 * @param object $request Loan request record.
	 * @return array
	 */
	private function project_loan_request( $request ) {
		return $this->request_query->project( $request );
	}

	/**
	 * Build the paginated loan request response.
	 *
	 * @param ALMGR_Loan_Request_Query_Result $result Shared query result.
	 * @return array
	 */
	private function prepare_loan_requests_response( $result ) {
		return array(
			'data'  => array_map( array( $this, 'project_loan_request' ), $result->get_items() ),
			'total' => $result->get_total(),
			'page'  => $result->get_page(),
			'pages' => $result->get_pages(),
		);
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

		$active_loans = isset( $loan_counts[ $user->ID ] ) ? (int) $loan_counts[ $user->ID ] : $this->active_loan_counts->count_for_user( $user->ID );

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
	 * @param object $asset Shared asset record.
	 * @return array
	 */
	private function prepare_member_asset( $asset ) {
		return array(
			'id'            => $asset->id,
			'code'          => $asset->code,
			'title'         => $asset->title,
			'structure'     => $asset->structure_slugs,
			'type'          => $asset->type_slugs,
			'external_code' => $asset->external_code,
			'location'      => $asset->location,
			'thumbnail_url' => $asset->thumbnail_url,
			'permalink'     => $asset->permalink,
		);
	}

	/**
	 * Build a REST response from a member-held asset query.
	 *
	 * @param int                     $member_id          Member user ID.
	 * @param ALMGR_Asset_Read_Result $result             Normalized member asset result.
	 * @param bool                    $include_pagination Whether to include pagination metadata.
	 * @return WP_REST_Response
	 */
	private function prepare_member_assets_response( $member_id, ALMGR_Asset_Read_Result $result, $include_pagination = false ) {
		$items = array();
		foreach ( $result->get_items() as $asset ) {
			$items[] = $this->prepare_member_asset( $asset );
		}

		$data = array(
			'member_id' => (int) $member_id,
			'total'     => $result->get_total(),
			'data'      => $items,
		);

		if ( $include_pagination ) {
			$data['pages'] = $result->get_pages();
		}

		$response = new WP_REST_Response( $data, 200 );
		if ( $include_pagination ) {
			$response->header( 'X-ALM-Total', $result->get_total() );
			$response->header( 'X-ALM-TotalPages', $result->get_pages() );
		}

		return $response;
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
		return ALMGR_Pagination::normalize_per_page( $value );
	}
}
