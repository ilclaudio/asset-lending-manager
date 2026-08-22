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
	 * Constructor.
	 *
	 * @param ALMGR_Asset_Read_Service    $asset_reads   Shared asset read service.
	 * @param ALMGR_Asset_Detail_Service  $asset_details Shared asset detail service.
	 * @param ALMGR_Asset_History_Service $asset_history Shared asset history service.
	 */
	public function __construct( ALMGR_Asset_Read_Service $asset_reads, ALMGR_Asset_Detail_Service $asset_details, ALMGR_Asset_History_Service $asset_history ) {
		$this->asset_reads   = $asset_reads;
		$this->asset_details = $asset_details;
		$this->asset_history = $asset_history;
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
}
