<?php
/**
 * Autocomplete manager for ALM assets.
 *
 * Handles REST API endpoint for frontend autocomplete search.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class that registers and manages the Autcomplete feature.
 */
class ALMGR_Autocomplete_Manager {

	/**
	 * Settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager $settings Plugin settings instance.
	 */
	public function __construct( ALMGR_Settings_Manager $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Register the REST route.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue autocomplete scripts.
	 *
	 * Loads assets only on ALM pages (archive, single, or shortcode pages)
	 * to avoid unnecessary JS/CSS on unrelated frontend pages.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_alm_page() ) {
			return;
		}
		if ( ! $this->can_current_user_access_assets_autocomplete() ) {
			return;
		}

		wp_enqueue_script(
			'almgr-asset-autocomplete',
			ALMGR_PLUGIN_URL . 'assets/js/alm-asset-autocomplete.js',
			array(),
			ALMGR_VERSION,
			true
		);
		wp_enqueue_style(
			'almgr-asset-autocomplete',
			ALMGR_PLUGIN_URL . 'assets/css/alm-asset-autocomplete.css',
			array(),
			ALMGR_VERSION
		);
		wp_localize_script(
			'almgr-asset-autocomplete',
			'almgrAutocomplete',
			array(
				'restUrl'   => esc_url( rest_url( 'alm/v1/assets/autocomplete' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'minChars'  => (int) $this->settings->get( 'autocomplete.min_chars', 3 ),
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'alm/v1',
			'/assets/autocomplete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_autocomplete' ),
				'permission_callback' => array( $this, 'can_access_assets_autocomplete_endpoint' ),
				'args'                => array(
					'term' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_autocomplete_term' ),
					),
				),
			)
		);

		register_rest_route(
			'alm/v1',
			'/users/autocomplete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_users_autocomplete' ),
				'permission_callback' => function () {
					return is_user_logged_in() && current_user_can( ALMGR_EDIT_ASSET );
				},
				'args'                => array(
					'term' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_autocomplete_term' ),
					),
				),
			)
		);
	}

	/**
	 * Validates autocomplete search term against configured minimum length.
	 *
	 * @param mixed $param Term parameter received by REST API.
	 * @return bool
	 */
	public function validate_autocomplete_term( $param ) {
		if ( ! is_string( $param ) ) {
			return false;
		}

		$term      = trim( $param );
		$min_chars = (int) $this->settings->get( 'autocomplete.min_chars', 3 );

		return strlen( $term ) >= $min_chars;
	}

	/**
	 * Checks whether unauthenticated access is allowed for the assets autocomplete endpoint.
	 *
	 * @return bool
	 */
	private function is_public_assets_endpoint_enabled() {
		return (bool) $this->settings->get( 'autocomplete.public_assets_endpoint_enabled', true );
	}

	/**
	 * Checks whether the current user can access the assets autocomplete endpoint.
	 *
	 * @return bool
	 */
	private function can_current_user_access_assets_autocomplete() {
		if ( $this->is_public_assets_endpoint_enabled() ) {
			return true;
		}

		return is_user_logged_in() && current_user_can( ALMGR_VIEW_ASSETS );
	}

	/**
	 * REST permission callback for assets autocomplete endpoint.
	 *
	 * @return true|WP_Error
	 */
	public function can_access_assets_autocomplete_endpoint() {
		if ( $this->can_current_user_access_assets_autocomplete() ) {
			return true;
		}

		return new WP_Error(
			'alm_assets_autocomplete_forbidden',
			__( 'You do not have permission to access asset autocomplete.', 'asset-lending-manager' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Check whether the current page is an ALM page.
	 *
	 * Returns true for asset archive, single asset, and pages containing
	 * the almgr_asset_list or almgr_asset_view shortcodes.
	 *
	 * @return bool
	 */
	private function is_alm_page() {
		if ( is_post_type_archive( ALMGR_ASSET_CPT_SLUG ) || is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
			return true;
		}

		global $post;
		if ( $post && ( has_shortcode( $post->post_content, 'almgr_asset_list' ) || has_shortcode( $post->post_content, 'almgr_asset_view' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle autocomplete request via POST.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_autocomplete( WP_REST_Request $request ) {
		// Read search term.
		$term = $request->get_param( 'term' ) ?? '';
		$term = $term ? trim( wp_unslash( $term ) ) : '';
		// Require minimum characters (from settings).
		$min_chars = (int) $this->settings->get( 'autocomplete.min_chars', 3 );
		if ( strlen( $term ) < $min_chars ) {
			return rest_ensure_response( array() );
		}

		// Query assets.
		$query_args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => (int) $this->settings->get( 'autocomplete.max_results', ALMGR_AUTOCOMPLETE_MAX_RESULTS ),
		);
		$query      = new WP_Query( $query_args );
		$results    = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$wrapper = ALMGR_Asset_Manager::get_asset_wrapper( $post->ID );
				if ( ! $wrapper ) {
					continue;
				}
				$results[] = array(
					'id'          => $post->ID,
					'title'       => $wrapper->title,
					'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), (int) $this->settings->get( 'autocomplete.description_length', ALMGR_AUTOCOMPLETE_DESC_LENGTH ), '...' ),
					'structure'   => ! empty( $wrapper->alm_structure ) ? implode( ', ', $wrapper->alm_structure ) : '',
					'type'        => ! empty( $wrapper->alm_type ) ? implode( ', ', $wrapper->alm_type ) : '',
					'permalink'   => $wrapper->permalink,
				);
			}
		}
		wp_reset_postdata();
		return rest_ensure_response( $results );
	}

	/**
	 * Handle user autocomplete request via POST.
	 *
	 * Returns ALM users (members and operators) matching the search term.
	 * Protected endpoint: requires operator capability.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_users_autocomplete( WP_REST_Request $request ) {
		$term = $request->get_param( 'term' ) ?? '';
		$term = trim( wp_unslash( (string) $term ) );

		$min_chars = (int) $this->settings->get( 'autocomplete.min_chars', 3 );
		if ( strlen( $term ) < $min_chars ) {
			return rest_ensure_response( array() );
		}

		$users = get_users(
			array(
				'role__in' => array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
				'search'   => '*' . $term . '*',
				'number'   => (int) $this->settings->get( 'autocomplete.max_results', ALMGR_AUTOCOMPLETE_MAX_RESULTS ),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$user_roles = (array) $user->roles;
			$role_label = in_array( ALMGR_OPERATOR_ROLE, $user_roles, true )
				? __( 'Operator', 'asset-lending-manager' )
				: __( 'Member', 'asset-lending-manager' );

			$results[] = array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'role'         => $role_label,
			);
		}

		return rest_ensure_response( $results );
	}
}
