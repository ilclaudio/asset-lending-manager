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
class ALM_Autocomplete_Manager {

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
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'alm-asset-autocomplete',
			ALM_PLUGIN_URL . 'assets/js/alm-asset-autocomplete.js',
			array( 'jquery' ),
			ALM_VERSION,
			true
		);
		wp_enqueue_style(
			'alm-asset-autocomplete',
			ALM_PLUGIN_URL . 'assets/css/alm-asset-autocomplete.css',
			array(),
			ALM_VERSION
		);
		wp_localize_script(
			'alm-asset-autocomplete',
			'almAutocomplete',
			array(
				'restUrl'   => esc_url( rest_url( 'alm/v1/assets/autocomplete' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'minChars'  => 3,
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
				'permission_callback' => '__return_true',
				// 'permission_callback' => function() {
				// return current_user_can( ALM_VIEW_ASSETS );
				// },
				'args'                => array(
					'term' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && strlen( $param ) >= 3;
						},
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
					return is_user_logged_in() && current_user_can( ALM_EDIT_ASSET );
				},
				'args'                => array(
					'term' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && strlen( $param ) >= 3;
						},
					),
				),
			)
		);
	}

	/**
	 * Handle autocomplete request via POST.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_autocomplete( WP_REST_Request $request ) {
		// // Read nonce from POST.
		$nonce = $request->get_param( 'nonce' ) ?? '';
		$nonce = $nonce ? sanitize_text_field( $nonce ) : '';
		error_log( '*** Nonce in handle_autocomplete:' . $nonce );

		// Check nonce.
		// $nonce = $request->get_param( 'nonce' );
		// if ( ! wp_verify_nonce( $nonce, 'alm_autocomplete_nonce' ) ) {
		// return new WP_REST_Response(
		// array( 'error' => __( 'Invalid security token.', 'asset-lending-manager' ) ),
		// 403
		// );
		// }

		// // Check capability.
		// if ( ! current_user_can( ALM_VIEW_ASSETS ) ) {
		// return new WP_REST_Response(
		// array( 'error' => __( 'Insufficient permissions.', 'asset-lending-manager' ) ),
		// 403
		// );
		// }

		// Read search term.
		$term = $request->get_param( 'term' ) ?? '';
		$term = $term ? trim( wp_unslash( $term ) ) : '';
		// Require at least 3 characters.
		if ( strlen( $term ) < 3 ) {
			return rest_ensure_response( array() );
		}

		// Query assets.
		$query_args = array(
			'post_type'      => ALM_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => ALM_AUTOCOMPLETE_MAX_RESULTS,
		);
		$query      = new WP_Query( $query_args );
		$results    = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$wrapper = ALM_Asset_Manager::get_asset_wrapper( $post->ID );
				if ( ! $wrapper ) {
					continue;
				}
				$results[] = array(
					'id'          => $post->ID,
					'title'       => $wrapper->title,
					'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), ALM_AUTOCOMPLETE_DESC_LENGTH, '...' ),
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

		if ( strlen( $term ) < 3 ) {
			return rest_ensure_response( array() );
		}

		$users = get_users(
			array(
				'role__in' => array( ALM_MEMBER_ROLE, ALM_OPERATOR_ROLE ),
				'search'   => '*' . $term . '*',
				'number'   => ALM_AUTOCOMPLETE_MAX_RESULTS,
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$user_roles = (array) $user->roles;
			$role_label = in_array( ALM_OPERATOR_ROLE, $user_roles, true )
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
