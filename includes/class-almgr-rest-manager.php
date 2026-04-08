<?php
/**
 * REST API Manager for Asset Lending Manager plugin.
 *
 * Implements a read-only JSON API for ALM assets and members via custom
 * WordPress rewrite rules. Routes under almgr/v1/ work independently of the
 * WordPress REST API global setting.
 *
 * Authentication: WordPress Application Passwords (WP 5.6+) via Authorization
 * header, or a WordPress session cookie. All endpoints require a logged-in
 * user with the appropriate ALM capability.
 *
 * Endpoints:
 *   GET /almgr/v1/assets          Paginated asset list          (almgr_view_assets)
 *   GET /almgr/v1/assets/{id}     Single asset detail           (almgr_view_asset)
 *   GET /almgr/v1/members         Paginated ALM user list       (almgr_edit_asset)
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
 * Manages the ALM read-only JSON API endpoints.
 */
class ALMGR_REST_Manager {

	/**
	 * URL prefix for all API routes.
	 *
	 * @var string
	 */
	const API_BASE = 'almgr/v1';

	/**
	 * WordPress query var that identifies the API route type.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'almgr_api_route';

	/**
	 * WordPress query var that carries the resource ID.
	 *
	 * @var string
	 */
	const QUERY_ID = 'almgr_api_id';

	/**
	 * WordPress query var that carries the member ID for nested routes.
	 *
	 * @var string
	 */
	const QUERY_MEMBER_ID = 'almgr_api_member_id';

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
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'parse_request', array( $this, 'handle_request' ) );
		// Enable Application Passwords for ALM routes, which use custom rewrite
		// rules instead of the WP REST API infrastructure.
		add_filter( 'determine_current_user', array( $this, 'authenticate_almgr_request' ), 15 );
	}

	// -------------------------------------------------------------------------
	// Routing
	// -------------------------------------------------------------------------

	/**
	 * Register custom rewrite rules for ALM API routes.
	 *
	 * Must be followed by a permalink flush (Settings → Permalinks) on first
	 * activation. The plugin activation hook flushes rules automatically.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^almgr/v1/assets/([0-9]+)/?$',
			'index.php?almgr_api_route=asset&almgr_api_id=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^almgr/v1/assets/?$',
			'index.php?almgr_api_route=assets',
			'top'
		);
		add_rewrite_rule(
			'^almgr/v1/members/?$',
			'index.php?almgr_api_route=members',
			'top'
		);
		add_rewrite_rule(
			'^almgr/v1/members/([0-9]+)/assets/?$',
			'index.php?almgr_api_route=member_assets&almgr_api_member_id=$matches[1]',
			'top'
		);
	}

	/**
	 * Add ALM API query vars to the WordPress allowed list.
	 *
	 * @param array $vars Registered query vars.
	 * @return array
	 */
	public function add_query_vars( array $vars ) {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::QUERY_ID;
		$vars[] = self::QUERY_MEMBER_ID;
		return $vars;
	}

	// -------------------------------------------------------------------------
	// Authentication
	// -------------------------------------------------------------------------

	/**
	 * Process Application Passwords (Basic Auth) for ALM API routes.
	 *
	 * WordPress only enables Application Passwords for routes served by its
	 * REST API infrastructure. This filter extends that support to ALM custom
	 * rewrite rule routes by temporarily asserting an API request context while
	 * the credentials are validated.
	 *
	 * @param int|false $user_id Currently determined user ID, or false.
	 * @return int|false
	 */
	public function authenticate_almgr_request( $user_id ) {
		if ( $user_id ) {
			return $user_id;
		}

		// Only act for requests targeting an ALM API route.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! $this->is_almgr_api_uri( $request_uri ) ) {
			return $user_id;
		}

		// Some servers provide the Authorization header differently than PHP_AUTH_*.
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
			if ( 0 === stripos( $auth_header, 'basic ' ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Required to decode Basic Auth credentials for Application Passwords.
				$decoded = base64_decode( substr( $auth_header, 6 ), true );
				if ( $decoded ) {
					$parts                    = explode( ':', $decoded, 2 );
					$_SERVER['PHP_AUTH_USER'] = $parts[0];
					$_SERVER['PHP_AUTH_PW']   = isset( $parts[1] ) ? $parts[1] : '';
				}
			}
		}

		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $user_id;
		}

		// Signal API context so Application Passwords authentication is active.
		add_filter( 'application_password_is_api_request', '__return_true' );
		$auth_user = sanitize_user( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must be passed raw to wp_authenticate().
		$auth_pass = isset( $_SERVER['PHP_AUTH_PW'] ) ? (string) wp_unslash( $_SERVER['PHP_AUTH_PW'] ) : '';
		$user      = wp_authenticate( $auth_user, $auth_pass );
		remove_filter( 'application_password_is_api_request', '__return_true' );

		if ( $user instanceof WP_User ) {
			return $user->ID;
		}

		return $user_id;
	}

	// -------------------------------------------------------------------------
	// Request dispatcher
	// -------------------------------------------------------------------------

	/**
	 * Handle incoming ALM API requests.
	 *
	 * Fires on the parse_request action. Returns immediately when the request
	 * does not target an ALM API route.
	 *
	 * @param WP $wp WordPress request object.
	 * @return void
	 */
	public function handle_request( WP $wp ) {
		$route = isset( $wp->query_vars[ self::QUERY_VAR ] ) ? $wp->query_vars[ self::QUERY_VAR ] : '';
		if ( '' === $route ) {
			return;
		}

		if ( ! $this->settings->get( 'rest_api.enabled', true ) ) {
			$this->send_error( 'almgr_api_disabled', __( 'The ALM API is disabled.', 'asset-lending-manager' ), 503 );
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method ) {
			$this->send_error( 'almgr_method_not_allowed', __( 'Method not allowed.', 'asset-lending-manager' ), 405 );
		}

		if ( ! is_user_logged_in() ) {
			$this->send_error( 'almgr_unauthorized', __( 'Authentication required.', 'asset-lending-manager' ), 401 );
		}

		switch ( $route ) {
			case 'assets':
				$this->handle_get_assets();
				break;
			case 'asset':
				$id = isset( $wp->query_vars[ self::QUERY_ID ] ) ? absint( $wp->query_vars[ self::QUERY_ID ] ) : 0;
				$this->handle_get_asset( $id );
				break;
			case 'members':
				$this->handle_get_members();
				break;
			case 'member_assets':
				$member_id = isset( $wp->query_vars[ self::QUERY_MEMBER_ID ] ) ? absint( $wp->query_vars[ self::QUERY_MEMBER_ID ] ) : 0;
				$this->handle_get_member_assets( $member_id );
				break;
			default:
				$this->send_error( 'almgr_not_found', __( 'Route not found.', 'asset-lending-manager' ), 404 );
		}
	}

	// -------------------------------------------------------------------------
	// Endpoint handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle GET /almgr/v1/assets — paginated asset list.
	 *
	 * Query parameters:
	 *   page      (int)    Page number, default 1.
	 *   per_page  (int)    Items per page, default 20, max 100.
	 *   search    (string) Full-text search term.
	 *   state     (string) Filter by state taxonomy slug.
	 *   type      (string) Filter by type taxonomy slug.
	 *   structure (string) Filter by structure taxonomy slug.
	 *   owner     (int)    Filter by owner user ID (_almgr_current_owner meta).
	 *
	 * @return void
	 */
	private function handle_get_assets() {
		if ( ! current_user_can( ALMGR_VIEW_ASSETS ) ) {
			$this->send_error( 'almgr_forbidden', __( 'You do not have permission to view assets.', 'asset-lending-manager' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET, nonce not applicable.
		$page     = max( 1, absint( isset( $_GET['page'] ) ? $_GET['page'] : 1 ) );
		$per_page = $this->clamp_per_page( absint( isset( $_GET['per_page'] ) ? $_GET['per_page'] : self::DEFAULT_PER_PAGE ) );

		$args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'fields'         => 'ids',
		);

		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		// Build tax_query from optional taxonomy filters.
		$tax_query = array();
		$tax_map   = array(
			'state'     => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
			'type'      => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
			'structure' => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
		);
		foreach ( $tax_map as $param => $taxonomy ) {
			$value = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
			if ( '' !== $value ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $value,
				);
			}
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- taxonomy filter, no alternative.
		}

		$owner = isset( $_GET['owner'] ) ? absint( $_GET['owner'] ) : 0;
		if ( $owner > 0 ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- owner filter via post meta, no alternative.
				array(
					'key'   => '_almgr_current_owner',
					'value' => $owner,
				),
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

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

		$this->send_json(
			array(
				'data'  => $items,
				'total' => $total,
				'pages' => $total_pages,
			),
			200,
			array(
				'X-ALM-Total'      => $total,
				'X-ALM-TotalPages' => $total_pages,
			)
		);
	}

	/**
	 * Handle GET /almgr/v1/assets/{id} — single asset detail.
	 *
	 * @param int $id Asset post ID.
	 * @return void
	 */
	private function handle_get_asset( $id ) {
		if ( ! current_user_can( ALMGR_VIEW_ASSET ) ) {
			$this->send_error( 'almgr_forbidden', __( 'You do not have permission to view assets.', 'asset-lending-manager' ), 403 );
		}

		if ( $id <= 0 ) {
			$this->send_error( 'almgr_not_found', __( 'Asset not found.', 'asset-lending-manager' ), 404 );
		}

		$data = $this->prepare_asset( $id, 'detail' );
		if ( null === $data ) {
			$this->send_error( 'almgr_not_found', __( 'Asset not found.', 'asset-lending-manager' ), 404 );
		}

		$this->send_json( $data );
	}

	/**
	 * Handle GET /almgr/v1/members — paginated ALM user list (operator only).
	 *
	 * Query parameters:
	 *   page     (int)    Page number, default 1.
	 *   per_page (int)    Items per page, default 20, max 100.
	 *   search   (string) Search term (login, email, display name).
	 *   role     (string) Filter by ALM role slug (almgr_member or almgr_operator).
	 *
	 * @return void
	 */
	private function handle_get_members() {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			$this->send_error( 'almgr_forbidden', __( 'You do not have permission to view members.', 'asset-lending-manager' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET, nonce not applicable.
		$page     = max( 1, absint( isset( $_GET['page'] ) ? $_GET['page'] : 1 ) );
		$per_page = $this->clamp_per_page( absint( isset( $_GET['per_page'] ) ? $_GET['per_page'] : self::DEFAULT_PER_PAGE ) );
		$offset   = ( $page - 1 ) * $per_page;

		$query_args = array(
			'role__in'    => array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
			'number'      => $per_page,
			'offset'      => $offset,
			'orderby'     => 'display_name',
			'order'       => 'ASC',
			'count_total' => true,
		);

		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		if ( '' !== $search ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$role_filter = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
		if ( in_array( $role_filter, array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ), true ) ) {
			$query_args['role__in'] = array( $role_filter );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user_query  = new WP_User_Query( $query_args );
		$total       = (int) $user_query->get_total();
		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		$items = array();
		foreach ( $user_query->get_results() as $user ) {
			$items[] = $this->prepare_member( $user );
		}

		$this->send_json(
			array(
				'data'  => $items,
				'total' => $total,
				'pages' => $total_pages,
			),
			200,
			array(
				'X-ALM-Total'      => $total,
				'X-ALM-TotalPages' => $total_pages,
			)
		);
	}

	/**
	 * Handle GET /almgr/v1/members/{id}/assets — assets currently held by a member.
	 *
	 * Returns the list of assets assigned to the given user (owner_id = user_id).
	 * Requires ALMGR_EDIT_ASSET capability.
	 *
	 * @param int $member_id WordPress user ID.
	 * @return void
	 */
	private function handle_get_member_assets( $member_id ) {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			$this->send_error( 'almgr_forbidden', __( 'You do not have permission to view member assets.', 'asset-lending-manager' ), 403 );
		}

		if ( $member_id <= 0 ) {
			$this->send_error( 'almgr_not_found', __( 'Member not found.', 'asset-lending-manager' ), 404 );
		}

		$user = get_userdata( $member_id );
		if ( ! $user ) {
			$this->send_error( 'almgr_not_found', __( 'Member not found.', 'asset-lending-manager' ), 404 );
		}

		$query = new WP_Query(
			array(
				'post_type'      => ALMGR_ASSET_CPT_SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- owner lookup via post meta, no alternative.
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

		$this->send_json(
			array(
				'member_id' => $member_id,
				'total'     => count( $items ),
				'data'      => $items,
			)
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

		$raw_components     = ALMGR_ACF_Asset_Adapter::get_custom_field( 'components', $post_id );
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
		$acf             = ALMGR_ACF_Asset_Adapter::get_custom_fields( $post_id );
		$public_acf_keys = array(
			'manufacturer',
			'model',
			'serial_number',
			'external_code',
			'location',
			'dimensions',
			'weight',
			'user_manual',
			'technical_data_sheet',
		);
		foreach ( $public_acf_keys as $key ) {
			$data[ $key ] = isset( $acf[ $key ] ) ? ( isset( $acf[ $key ]['value'] ) ? $acf[ $key ]['value'] : null ) : null;
		}

		// Operator-only fields: cost, purchase date, notes, and loan history.
		if ( $this->is_operator() ) {
			$operator_keys = array( 'cost', 'data_acquisto', 'notes' );
			foreach ( $operator_keys as $key ) {
				$data[ $key ] = isset( $acf[ $key ] ) ? ( isset( $acf[ $key ]['value'] ) ? $acf[ $key ]['value'] : null ) : null;
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
	 * Build the JSON-safe array representation of an ALM user.
	 *
	 * @param WP_User $user WordPress user object.
	 * @return array
	 */
	private function prepare_member( WP_User $user ) {
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

		return array(
			'id'                 => $user->ID,
			'display_name'       => $user->display_name,
			'email'              => $user->user_email,
			'almgr_roles'        => $almgr_roles,
			'active_loans_count' => $this->count_active_loans( $user->ID ),
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
			'external_code' => (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'external_code', $post_id ),
			'location'      => (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'location', $post_id ),
			'thumbnail_url' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : null,
			'permalink'     => get_permalink( $post_id ),
		);
	}

	// -------------------------------------------------------------------------
	// Response helpers
	// -------------------------------------------------------------------------

	/**
	 * Send a JSON response and terminate execution.
	 *
	 * @param array $data    Response data.
	 * @param int   $status  HTTP status code (default 200).
	 * @param array $headers Additional response headers as name => value pairs.
	 * @return void
	 */
	private function send_json( array $data, $status = 200, array $headers = array() ) {
		status_header( $status );
		header( 'Content-Type: application/json; charset=utf-8' );
		foreach ( $headers as $name => $value ) {
			header( esc_attr( $name ) . ': ' . esc_attr( (string) $value ) );
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode produces safe JSON output.
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Send a JSON error response and terminate execution.
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Human-readable error message.
	 * @param int    $status  HTTP status code.
	 * @return void
	 */
	private function send_error( $code, $message, $status ) {
		$this->send_json(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$status
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
	 * Return true when the given URI targets an ALM API route.
	 *
	 * @param string $uri Request URI.
	 * @return bool
	 */
	private function is_almgr_api_uri( $uri ) {
		return (bool) preg_match( '#/' . preg_quote( self::API_BASE, '#' ) . '(/|$)#', $uri );
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
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- active loan count by owner, no alternative.
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
