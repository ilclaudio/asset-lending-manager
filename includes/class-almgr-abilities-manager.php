<?php
/**
 * WordPress Abilities API adapter.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the optional read-only Abilities API surface.
 *
 * This class contains only registration, permission checks and channel
 * projection. Domain reads are delegated to the shared asset services.
 */
class ALMGR_Abilities_Manager {

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
	 * Shared current-member asset service.
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
	 * Shared loan workflow service.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	private $loan_manager;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Asset_Read_Service         $asset_reads   Shared asset read service.
	 * @param ALMGR_Asset_Detail_Service       $asset_details Shared asset detail service.
	 * @param ALMGR_Asset_History_Service      $asset_history Shared asset history service.
	 * @param ALMGR_Member_Assets_Service      $member_assets Shared member asset service.
	 * @param ALMGR_Loan_Request_Query_Service $request_query Shared request query service.
	 * @param ALMGR_Access_Policy              $access_policy Shared access policy.
	 * @param ALMGR_Loan_Manager               $loan_manager  Shared loan workflow service.
	 */
	public function __construct( ALMGR_Asset_Read_Service $asset_reads, ALMGR_Asset_Detail_Service $asset_details, ALMGR_Asset_History_Service $asset_history, ALMGR_Member_Assets_Service $member_assets, $request_query = null, $access_policy = null, $loan_manager = null ) {
		$this->asset_reads   = $asset_reads;
		$this->asset_details = $asset_details;
		$this->asset_history = $asset_history;
		$this->member_assets = $member_assets;
		$this->request_query = $request_query instanceof ALMGR_Loan_Request_Query_Service ? $request_query : new ALMGR_Loan_Request_Query_Service();
		$this->access_policy = $access_policy instanceof ALMGR_Access_Policy ? $access_policy : new ALMGR_Access_Policy();
		$this->loan_manager  = $loan_manager instanceof ALMGR_Loan_Manager ? $loan_manager : null;
	}

	/**
	 * Register hooks only when the Abilities API is available.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the ALM category before abilities.
	 *
	 * @return void
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'almgr',
			array(
				'label'       => __( 'Asset Lending Manager', 'asset-lending-manager' ),
				'description' => __( 'Abilities for reading assets and loan information.', 'asset-lending-manager' ),
			)
		);
	}

	/**
	 * Register the read-only MVP abilities.
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$common_meta = array(
			'show_in_rest' => true,
			'mcp'          => array( 'public' => false ),
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		);

		wp_register_ability(
			'almgr/list-assets',
			array(
				'label'               => __( 'List assets', 'asset-lending-manager' ),
				'description'         => __( 'Returns the paginated asset catalog using the shared asset read service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_list_assets' ),
				'permission_callback' => array( $this, 'can_list_assets' ),
				'input_schema'        => $this->get_list_assets_input_schema(),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/get-asset',
			array(
				'label'               => __( 'Get asset', 'asset-lending-manager' ),
				'description'         => __( 'Returns one asset detail record using the shared asset detail service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_get_asset' ),
				'permission_callback' => array( $this, 'can_get_asset' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'asset_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Asset post ID.', 'asset-lending-manager' ),
						),
					),
					'required'   => array( 'asset_id' ),
				),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/get-asset-loan-history',
			array(
				'label'               => __( 'Get asset loan history', 'asset-lending-manager' ),
				'description'         => __( 'Returns visible loan history for one asset using the shared asset history service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_get_asset_history' ),
				'permission_callback' => array( $this, 'can_get_asset_history' ),
				'input_schema'        => $this->get_history_input_schema(),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/list-my-assets',
			array(
				'label'               => __( 'List my assets', 'asset-lending-manager' ),
				'description'         => __( 'Returns assets currently held by the authenticated user using the shared member asset service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_list_my_assets' ),
				'permission_callback' => array( $this, 'can_list_my_assets' ),
				'input_schema'        => $this->get_pagination_input_schema(),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/list-my-loan-requests',
			array(
				'label'               => __( 'List my loan requests', 'asset-lending-manager' ),
				'description'         => __( 'Returns the authenticated user loan requests using the shared request query service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_list_my_loan_requests' ),
				'permission_callback' => array( $this, 'can_list_my_loan_requests' ),
				'input_schema'        => $this->get_status_input_schema(),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/get-loan-request',
			array(
				'label'               => __( 'Get loan request', 'asset-lending-manager' ),
				'description'         => __( 'Returns one loan request visible to the authenticated user.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_get_loan_request' ),
				'permission_callback' => array( $this, 'can_get_loan_request' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'loan_request_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Loan request ID.', 'asset-lending-manager' ),
						),
					),
					'required'             => array( 'loan_request_id' ),
				),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/list-asset-loan-requests',
			array(
				'label'               => __( 'List asset loan requests', 'asset-lending-manager' ),
				'description'         => __( 'Returns requests visible for an owned asset using the shared request query service.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_list_asset_loan_requests' ),
				'permission_callback' => array( $this, 'can_list_asset_loan_requests' ),
				'input_schema'        => $this->get_asset_requests_input_schema(),
				'meta'                => $common_meta,
			)
		);

		wp_register_ability(
			'almgr/create-loan-request',
			array(
				'label'               => __( 'Create loan request', 'asset-lending-manager' ),
				'description'         => __( 'Creates a loan request using the shared loan workflow for the authenticated user.', 'asset-lending-manager' ),
				'category'            => 'almgr',
				'execute_callback'    => array( $this, 'execute_create_loan_request' ),
				'permission_callback' => array( $this, 'can_create_loan_request' ),
				'input_schema'        => $this->get_create_loan_request_input_schema(),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => false ),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Check catalog permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_list_assets( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSETS );
	}

	/**
	 * Check single-asset permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_get_asset( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Check history permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_get_asset_history( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Check current-member asset permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_list_my_assets( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Check current-user request permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_list_my_loan_requests( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Check single loan request visibility.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public function can_get_loan_request( $input = null ) {
		$input   = is_array( $input ) ? $input : array();
		$request = $this->request_query->get_by_id( isset( $input['loan_request_id'] ) ? $input['loan_request_id'] : 0 );

		return $this->access_policy->can_view_loan_request( get_current_user_id(), $request );
	}

	/**
	 * Check asset request permission through the shared access policy.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public function can_list_asset_loan_requests( $input = null ) {
		$input = is_array( $input ) ? $input : array();
		return $this->access_policy->can_view_asset_requests( get_current_user_id(), isset( $input['asset_id'] ) ? $input['asset_id'] : 0 );
	}

	/**
	 * Check create-loan-request permission.
	 *
	 * @param mixed $input Ability input, unused by this capability check.
	 * @return bool
	 */
	public function can_create_loan_request( $input = null ) {
		unset( $input );
		return current_user_can( ALMGR_VIEW_ASSET );
	}

	/**
	 * Execute asset catalog read.
	 *
	 * @param mixed $input Ability input.
	 * @return array
	 */
	public function execute_list_assets( $input = null ) {
		$input  = $this->normalize_input( $input );
		$result = $this->asset_reads->get_assets(
			array(
				'page'      => $input['page'],
				'per_page'  => $input['per_page'],
				'search'    => $input['search'],
				'state'     => $input['state'],
				'type'      => $input['type'],
				'structure' => $input['structure'],
				'owner'     => $input['owner'],
			)
		);

		return array(
			'data'  => array_map( array( $this, 'project_asset' ), $result->get_items() ),
			'total' => $result->get_total(),
			'pages' => $result->get_pages(),
		);
	}

	/**
	 * Execute single asset detail read.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_get_asset( $input = null ) {
		$input    = is_array( $input ) ? $input : array();
		$asset_id = isset( $input['asset_id'] ) ? absint( $input['asset_id'] ) : 0;
		if ( $asset_id <= 0 ) {
			return new WP_Error( 'almgr_missing_asset_id', __( 'An asset_id is required.', 'asset-lending-manager' ) );
		}

		$asset = $this->asset_details->get_asset_detail( $asset_id );
		if ( ! $asset ) {
			return new WP_Error( 'almgr_asset_not_found', __( 'Asset not found.', 'asset-lending-manager' ) );
		}

		return $this->project_asset( $asset, true );
	}

	/**
	 * Execute visible asset history read.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_get_asset_history( $input = null ) {
		$input = $this->normalize_history_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}
		$result = $this->asset_history->get_history( $input['asset_id'], get_current_user_id(), $input['per_page'], $input['page'] );

		return array(
			'data'  => array_map( array( $this, 'project_history' ), $result->get_items() ),
			'total' => $result->get_total(),
			'pages' => $result->get_pages(),
		);
	}

	/**
	 * Execute current-member asset read.
	 *
	 * The actor ID is always taken from the authenticated WordPress context;
	 * no caller-provided member ID is accepted.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_list_my_assets( $input = null ) {
		$input    = $this->normalize_input( $input );
		$actor_id = get_current_user_id();
		if ( $actor_id <= 0 ) {
			return new WP_Error( 'almgr_member_assets_forbidden', __( 'An authenticated user is required.', 'asset-lending-manager' ) );
		}

		$result = $this->member_assets->get_assets_for_member(
			$actor_id,
			$actor_id,
			array(
				'page'     => $input['page'],
				'per_page' => $input['per_page'],
			)
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'member_id' => $actor_id,
			'data'      => array_map( array( $this, 'project_member_asset' ), $result->get_items() ),
			'total'     => $result->get_total(),
			'pages'     => $result->get_pages(),
		);
	}

	/**
	 * Execute current-user loan request read.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_list_my_loan_requests( $input = null ) {
		$input  = $this->normalize_status_input( $input );
		$result = $this->request_query->get_for_user( get_current_user_id(), $input['status'], $input['page'], $input['per_page'] );

		return $this->project_request_collection( $result );
	}

	/**
	 * Execute single loan request read.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_get_loan_request( $input = null ) {
		$input      = is_array( $input ) ? $input : array();
		$request_id = isset( $input['loan_request_id'] ) ? absint( $input['loan_request_id'] ) : 0;
		if ( $request_id <= 0 ) {
			return new WP_Error( 'almgr_missing_loan_request_id', __( 'A positive integer loan_request_id is required.', 'asset-lending-manager' ) );
		}

		$request = $this->request_query->get_by_id( $request_id );
		if ( ! $request ) {
			return new WP_Error( 'almgr_loan_request_not_found', __( 'Loan request not found.', 'asset-lending-manager' ) );
		}

		if ( ! $this->access_policy->can_view_loan_request( get_current_user_id(), $request ) ) {
			return new WP_Error( 'almgr_loan_request_forbidden', __( 'You do not have permission to view this loan request.', 'asset-lending-manager' ) );
		}

		return $this->request_query->project( $request );
	}

	/**
	 * Execute asset loan request read.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_list_asset_loan_requests( $input = null ) {
		$input = $this->normalize_asset_requests_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$result = $this->request_query->get_for_asset( $input['asset_id'], $input['status'], $input['page'], $input['per_page'] );

		return $this->project_request_collection( $result );
	}

	/**
	 * Project a paginated loan request collection.
	 *
	 * @param ALMGR_Loan_Request_Query_Result $result Shared query result.
	 * @return array
	 */
	private function project_request_collection( $result ) {
		return array(
			'data'  => array_map( array( $this->request_query, 'project' ), $result->get_items() ),
			'total' => $result->get_total(),
			'page'  => $result->get_page(),
			'pages' => $result->get_pages(),
		);
	}

	/**
	 * Execute loan request creation through the shared loan workflow.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public function execute_create_loan_request( $input = null ) {
		if ( ! $this->loan_manager ) {
			return new WP_Error( 'almgr_not_initialized', __( 'The loan workflow is not initialized.', 'asset-lending-manager' ) );
		}

		$input = is_array( $input ) ? $input : array();
		if ( ! isset( $input['asset_id'] ) || absint( $input['asset_id'] ) <= 0 ) {
			return new WP_Error( 'almgr_missing_asset_id', __( 'A positive integer asset_id is required.', 'asset-lending-manager' ) );
		}
		if ( ! isset( $input['request_message'] ) || '' === trim( (string) $input['request_message'] ) ) {
			return new WP_Error( 'almgr_missing_request_message', __( 'A request_message is required.', 'asset-lending-manager' ) );
		}

		return $this->loan_manager->submit_loan_request(
			absint( $input['asset_id'] ),
			get_current_user_id(),
			sanitize_textarea_field( (string) $input['request_message'] )
		);
	}

	/**
	 * Normalize catalog input defaults.
	 *
	 * @param mixed $input Ability input.
	 * @return array
	 */
	private function normalize_input( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = array(
			'page'      => 1,
			'per_page'  => 20,
			'search'    => '',
			'state'     => '',
			'type'      => '',
			'structure' => '',
			'owner'     => 0,
		);

		return array_merge( $defaults, $input );
	}

	/**
	 * Normalize history input and validate its required ID.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	private function normalize_history_input( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! isset( $input['asset_id'] ) || ! is_int( $input['asset_id'] ) || $input['asset_id'] <= 0 ) {
			return new WP_Error( 'almgr_missing_asset_id', __( 'A positive integer asset_id is required.', 'asset-lending-manager' ) );
		}

		return array(
			'asset_id' => $input['asset_id'],
			'page'     => isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1,
			'per_page' => isset( $input['per_page'] ) ? max( 1, (int) $input['per_page'] ) : 20,
		);
	}

	/**
	 * Project a shared asset record for an Ability response.
	 *
	 * @param object $asset  Shared asset record.
	 * @param bool   $detail Include detail-only fields.
	 * @return array
	 */
	private function project_asset( $asset, $detail = false ) {
		$data = array(
			'id'             => (int) $asset->id,
			'code'           => (string) $asset->code,
			'title'          => (string) $asset->title,
			'permalink'      => (string) $asset->permalink,
			'thumbnail_url'  => $asset->thumbnail_url,
			'structure'      => $asset->structure_slugs,
			'type'           => $asset->type_slugs,
			'state'          => $asset->almgr_state_slugs,
			'level'          => $asset->level_slugs,
			'owner_id'       => (int) $asset->owner_id,
			'owner_name'     => (string) $asset->owner_name,
			'owner_username' => (string) $asset->owner_username,
		);

		if ( $detail ) {
			$data['content']     = wp_strip_all_tags( (string) $asset->content_html );
			$data['components']  = $asset->components;
			$data['parent_kits'] = $asset->parent_kits;
			$data['history']     = array();
			$data['state_label'] = (string) $asset->state_label;
			$data['acf_fields']  = $asset->acf_fields;
		}

		return $data;
	}

	/**
	 * Project a history row.
	 *
	 * @param object $row History row.
	 * @return array
	 */
	private function project_history( $row ) {
		$changed_by_user = get_userdata( (int) $row->changed_by );

		return array(
			'status'          => (string) $row->status,
			'changed_at'      => mysql2date( 'c', $row->changed_at ),
			'changed_by'      => (int) $row->changed_by,
			'changed_by_name' => $changed_by_user ? (string) $changed_by_user->display_name : '',
			'message'         => (string) $row->message,
		);
	}

	/**
	 * Return catalog input schema.
	 *
	 * @return array
	 */
	private function get_list_assets_input_schema() {
		return array(
			'type'       => 'object',
			'default'    => (object) array(),
			'properties' => array(
				'page'      => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'  => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
				'search'    => array(
					'type'    => 'string',
					'default' => '',
				),
				'state'     => array(
					'type'    => 'string',
					'default' => '',
				),
				'type'      => array(
					'type'    => 'string',
					'default' => '',
				),
				'structure' => array(
					'type'    => 'string',
					'default' => '',
				),
				'owner'     => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
			),
		);
	}

	/**
	 * Return history input schema.
	 *
	 * @return array
	 */
	private function get_history_input_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'asset_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
			),
			'required'   => array( 'asset_id' ),
		);
	}

	/**
	 * Return the common pagination schema for current-user reads.
	 *
	 * @return array
	 */
	private function get_pagination_input_schema() {
		return array(
			'type'       => 'object',
			'default'    => (object) array(),
			'properties' => array(
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
			),
		);
	}

	/**
	 * Return status input schema.
	 *
	 * @return array
	 */
	private function get_status_input_schema() {
		return array(
			'type'                 => 'object',
			'default'              => (object) array(),
			'additionalProperties' => false,
			'properties'           => array(
				'status'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
			),
		);
	}

	/**
	 * Return asset request input schema.
	 *
	 * @return array
	 */
	private function get_asset_requests_input_schema() {
		$schema               = $this->get_status_input_schema();
		$schema['properties'] = array_merge(
			array(
				'asset_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
			$schema['properties']
		);
		$schema['required']   = array(
			'asset_id',
		);

		return $schema;
	}

	/**
	 * Return loan request creation input schema.
	 *
	 * @return array
	 */
	private function get_create_loan_request_input_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'asset_id'        => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => __( 'Asset post ID for the requested loan.', 'asset-lending-manager' ),
				),
				'request_message' => array(
					'type'        => 'string',
					'description' => __( 'Message explaining why the asset is requested.', 'asset-lending-manager' ),
				),
			),
			'required'             => array( 'asset_id', 'request_message' ),
		);
	}

	/**
	 * Normalize a request status input.
	 *
	 * @param mixed $input Ability input.
	 * @return array
	 */
	private function normalize_status_input( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'status'   => isset( $input['status'] ) ? sanitize_key( $input['status'] ) : '',
			'page'     => isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1,
			'per_page' => isset( $input['per_page'] ) ? min( 100, max( 1, (int) $input['per_page'] ) ) : 20,
		);
	}

	/**
	 * Normalize an asset request input and validate its ID.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	private function normalize_asset_requests_input( $input ) {
		$input    = $this->normalize_status_input( $input ) + ( is_array( $input ) ? $input : array() );
		$asset_id = isset( $input['asset_id'] ) ? absint( $input['asset_id'] ) : 0;
		if ( $asset_id <= 0 ) {
			return new WP_Error( 'almgr_missing_asset_id', __( 'A positive integer asset_id is required.', 'asset-lending-manager' ) );
		}

		return array(
			'asset_id' => $asset_id,
			'status'   => $input['status'],
			'page'     => $input['page'],
			'per_page' => $input['per_page'],
		);
	}

	/**
	 * Project a member-held asset for the Ability response.
	 *
	 * @param object $asset Shared asset record.
	 * @return array
	 */
	private function project_member_asset( $asset ) {
		return array(
			'id'            => (int) $asset->id,
			'code'          => (string) $asset->code,
			'title'         => (string) $asset->title,
			'structure'     => $asset->structure_slugs,
			'type'          => $asset->type_slugs,
			'external_code' => (string) $asset->external_code,
			'location'      => (string) $asset->location,
			'thumbnail_url' => $asset->thumbnail_url,
			'permalink'     => (string) $asset->permalink,
		);
	}
}
